<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService\VS;

use Exception;
use FFI\Exception as FFIException;

class GisGmp implements \MyCompany\WebService\VSRequestExternelService{
    const WORKFLOW_ID = '';
    const URN = '3eb646'; // УРН отправителя
    const URN_FA_QUITTANCE = '3eb551'; // УРН отправителя (погашение начисления)
    const URN_QUITTANCE = '3eacb7';//УРН отправителя (Предоставление информации о результатах квитирования)
    const ROLE_TYPE = '9'; // Администратор доходов бюджета
    const ROLE_TYPE_FA_QUITTANC = '3'; // Администратор доходов бюджета (погашение начисления)
    const ROLE_TYPE_QUITTANC = '1'; // Администратор доходов бюджета (Предоставление информации о результатах квитирования)
    const PAYMENT_ID = '10471020010005233009202100000001';//Для отладки
    const GIS_GMP_VERSION = '2.6.0';
    private $messageId;
    private $iblockID;


    private $requestBody;
    private $requestData;
    
    private $props;
    private $type;
    private $elementId;
    private $nodeValue;
    private $fieldType;//УИН или дата
    
    public function setData(array $request) {
        if (isset($request['type'])){
            //если запрос из нашей формы (форма)
            $this->setDataFromForm($request);
        } else {
            //если запрос из вне (xml)
            $requestData = $this->getNodeFromXmlArray($request, 'GetResponseResponse');
            $count = 0;
            $nodeCount = $this->getNodeCount($requestData,'PaymentInfo', $count);
            if($count==1)
                $this->setDataFromXml($request);
            else{
                $test = '123';
            }
        }
    }

    private function setDataFromForm($request){
        $this->type = $request['type'];
        $this->fieldType = $request['field_type'];
        $this->setPropsElement($request);
    }

    private function setDataFromXml($request){
        $this->requestData = $this->getNodeFromXmlArray($request, 'GetResponseResponse');
        //Берётся последний элемент, который лежит в узле ExportPaymentsResponse, например PaymentInfo,
        //и PaymentInfo записывается в $this->type
        //$count = 0;
        //$nodeCount = $this->getNodeCount($this->requestData,'PaymentInfo', $count);
        $this->type = array_key_last($this->getNodeFromXmlArray($this->requestData, 'ExportPaymentsResponse'));
        $this->messageId = $this->getNodeFromXmlArray($this->requestData, 'RqId');

        foreach ($this->getNodeFromXmlArray($this->requestData, $this->getType()) as $key => $block) {
            $this->setParams($key, $block, $this->getType());
        }
        
        $this->props['XML'] = [
            'TEXT' => file_get_contents('php://input'),
            'TYPE' => 'text'
        ];
         $test = '123';
    }

    public function getType(){
        if (!$this->type){
            $this->type = array_key_last($this->requestData);
        }
        return $this->type;
    }

    private function setParams($key, $block, $parent) {
        foreach ($block as $key_2 => $attr) {
            if (is_array($attr)) {
                $this->setParams($key_2, $attr, $key);
            } else {
                $this->props[$parent.'_'.$key_2] = $attr;
            }
        }
    }

    public function getIblockId(): int{
        if (!$this->iblockID){
            $code = 'gis-gmp-'.$this->getType();
            $res = \CIBlock::GetList(
                Array("SORT"=>"ASC"),
                Array(
                    '=CODE' => $code,
                    "CHECK_PERMISSIONS" => "N"
                ),
                false
            );
            if ($ar_res = $res->GetNext()){
                $this->iblockID = $ar_res['ID'];
            } else {
                throw new Exception("Не найден инфоблок по коду ".$code);
            }
        }
        
        return $this->iblockID;
    }

    public function add(): int{
        $this->props['RqID'] = 'G_'.\MyCompany\WebService\Helper::genUuid();

        //сохранили в ИБ в нужный раздел
        $params = [
            'IBLOCK_ID' => $this->getIblockId(),
            'NAME' => time(),
            'ACTIVE' => 'Y',
            "IBLOCK_SECTION_ID" => false,
            "PROPERTY_VALUES" => $this->props
        ];

        $el = new \CIBlockElement;
        $elemId = $el->Add($params);
		if ($elemId > 0) {
			// if ($workFlowId > 0) {
			// 	$workFlowId = self::startWorkflow($iblockId, $elemId, $workFlowId );
			// 	if (!$workFlowId) {
			// 		echo ('Ошибка исполнения бизнес-процесса ' . $iblockId . "\n" . $workFlowId);
			// 		die();
			// 	}
			// }
        } else {
            throw new \Exception ('Ошибка добавления элемента в инфоблок ' . $this->getIblockId() . "\n" . $el->LAST_ERROR);
        }
        return $elemId;
    }

    private function genFilter(string $uin, string $type)
    {
        return [
            'IBLOCK_ID' => $this->getIblockId(),
            //'=PROPERTY_RqID' => $this->messageId,
            '=PROPERTY_' . $type . '_SupplierBillID' => $uin
        ];
    }

    /* находим элемент ИБ, который обновляем */
    public function searchElement(){
        if ($this->props[$this->type . "_supplierBillID"]) {
            if (!$this->elementId) {
                $filter = $this->genFilter($this->props[$this->type . "_supplierBillID"], $this->type);

                $res = \CIBlockElement::GetList(
                    ['created_date' => 'desc'],
                    $filter,
                    false,
                    ['nTopCount' => 99999],
                    []
                );
        
                if($ob = $res->GetNext()){
                    $this->elementId = $ob['ID'];                
                } else {
                    return false;
                }
            }
            return $this->elementId;
        } else {
            return false;
        }
    }

    /* Устанавливаем свойства для записи элемета */
    public function setPropsElement($props){
        foreach ($props as $key => $prop){
            $this->props[$this->getType() . "_" . $key] = $prop;
        }
    }

    // Обновляем элемент в ИБ
    public function updateElement(){
        if ($this->elementId){
            //Обновить элемент
            \CIBlockElement::SetPropertyValuesEx(
                $this->elementId,
                $this->getIblockId(),
                $this->props,
            );
        } else {
            throw new Exception('Невозможно обновить элемент, которого нет');
        }
    }

    public function buildXMLString(string $type): string
    {
        $request = '<?xml version="1.0" encoding="UTF-8"?>';

        switch($type){
            case 'PaymentInfo':
                $request .= '<ns0:ExportPaymentsRequest ';
                $request .= 'xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/ ' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/ ' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/ ' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-payments/ ' . static::GIS_GMP_VERSION . '" ';
                $request .= 'Id="' . $this->props["RqID"] . '" ';
                $request .= 'timestamp="' . date("Y-m-d\TH:s:i\.0") . '" ';
                $request .= 'senderIdentifier="' . static::URN . '" ';
                $request .= 'senderRole="' . static::ROLE_TYPE . '">';
                $request .= '<com:Paging pageNumber="1" pageLength="100"/>';
                $request .= '<sc:PaymentsExportConditions kind="PAYMENT">';
                switch ($this->fieldType) {
                    case 'uin':
                        $request .=         '<sc:ChargesConditions>';
                        $request .=             '<sc:SupplierBillID>';
                        $request .=                 $this->props["PaymentInfo_SupplierBillID"];
                        $request .=             '</sc:SupplierBillID>';
                        $request .=         '</sc:ChargesConditions>';
                        break;
                    case 'date':
                        $request .=            '<sc:TimeConditions>';
                        $request .=             '<com:TimeInterval ';
                        $request .=             'endDate="' . $this->props["PaymentInfo_date_end"] . 'T23:59:59" ';
                        $request .=             'startDate="' . $this->props["PaymentInfo_date_start"] . 'T00:00:00" />';
                        $request .=            '</sc:TimeConditions>';
                        break;
                }
                $request .=         '</sc:PaymentsExportConditions>';
                $request .= '</ns0:ExportPaymentsRequest>';

                break;

            case 'faQuittance':
                $request .= '<fa:ForcedAcknowledgementRequest ';
                $request .= 'xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:fa="urn://roskazna.ru/gisgmp/xsd/services/forced-ackmowledgement/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'Id="' . $this->props["RqID"] . '" ';
                $request .= 'timestamp="' . date("Y-m-d\TH:s:i\.0") . '" ';
                $request .= 'senderIdentifier="' . static::URN_FA_QUITTANCE . '" ';
                $request .= 'senderRole="' . static::ROLE_TYPE_FA_QUITTANC . '">';
                $request .= '<fa:Reconcile supplierBillId="' . $this->props["faQuittance_SupplierBillID"] . '">';
                $request .= '<fa:PaymentId>' . static::PAYMENT_ID . '</fa:PaymentId>';
                $request .= '</fa:Reconcile>';
                $request .= '</fa:ForcedAcknowledgementRequest>';
                break;

            case 'Quittance':
                $request .= '<ns0:ExportQuittancesRequest ';
                $request .= 'xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:qt="http://roskazna.ru/gisgmp/xsd/Quittance/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-quittances/' . static::GIS_GMP_VERSION . '" ';
                $request .= 'Id="' . $this->props["RqID"] . '" ';
                $request .= 'timestamp="' . date("Y-m-d\TH:s:i\.0") . '" ';
                $request .= 'senderIdentifier="' . self::URN_QUITTANCE . '" ';
                $request .= 'senderRole="' . self::ROLE_TYPE_QUITTANC . '"> ';
                $request .= '<sc:QuittancesExportConditions kind="QUITTANCE">';
                $request .= '<sc:ChargesConditions>';
                $request .= '<sc:SupplierBillID>' . $this->props["Quittance_supplierBillID"] . '</sc:SupplierBillID>';
                $request .= '</sc:ChargesConditions>';
                $request .= '</sc:QuittancesExportConditions>';
                $request .= '</ns0:ExportQuittancesRequest>';
                break;
        }



        return $request;
    }

    //создаем запрос xml для soap запроса во внешний сервис
    public function createSoapResponseRequest()
    {
        $type = $this->getType();
        $request = '';
        $request = self::buildXMLString($type);
        //Работающий запрос
        /*
        $request = '<?xml version="1.0" encoding="UTF-8"?>';
        $request .= '<ns0:ExportPaymentsRequest xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-payments/2.6.0" Id="';
        $request .= $this->props["RqID"] . '" timestamp="' . date("Y-m-d\TH:s:i\.0") . '" senderIdentifier="' . static::URN . '" senderRole="' . static::ROLE_TYPE . '">';
        $request .= '<com:Paging pageNumber="1" pageLength="100"/>
            <sc:PaymentsExportConditions kind="PAYMENT">
                <sc:ChargesConditions>
                    <sc:SupplierBillID>';
        $request .= $this->props["PaymentInfo_SupplierBillID"];
        $request .= '</sc:SupplierBillID>
                </sc:ChargesConditions>
            </sc:PaymentsExportConditions>
        </ns0:ExportPaymentsRequest>';
        */


        /*$request = '';
        $request = '<?xml version="1.0" encoding="UTF-8"?>';
        $request .= '<ns0:ExportPaymentsRequest xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-payments/2.6.0" Id="';
        $request .= $this->props["RqID"] . '" timestamp="' . date("Y-m-d\TH:s:i\.0") . '" senderIdentifier="' . static::URN . '" senderRole="' . static::ROLE_TYPE . '">';
        $request .= '<com:Paging pageNumber="1" pageLength="100"/>
             <sc:PaymentsExportConditions kind="PAYMENT">
                <sc:TimeConditions>
                        <com:TimeInterval ';
                        $request .= 'endDate="' . date("Y-m-d\TH:s:i") . '" startDate="2024-04-18T13:13:03" />';
                    $request .= '</sc:TimeConditions>
             </sc:PaymentsExportConditions>
         </ns0:ExportPaymentsRequest>';
        */

        return $request;
    }    

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getWorkflowId(): int
    {
        return static::WORKFLOW_ID;
    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName): string|array
    {
        //var_dump(array_keys($xmlArray));
        foreach ($xmlArray as $key => $value) {
            if ($key === $nodeName) {
                $this->nodeValue = $value;
                   break;
            } else {
                if (gettype($value) == 'array') {
                    $this->getNodeFromXmlArray($value, $nodeName);
                }
            }
        }

        return $this->nodeValue;
    }

    /**
     * Метод подсчитывает количество узлов $nodeName
     * @param array $xmlArray
     * @param string $nodeName
     * @param $count
     * @return int
     */
    private function getNodeCount(array $xmlArray, string $nodeName, &$count): int
    {
        foreach ($xmlArray as $key => $value) {
            if ($key !== $nodeName) {
                if (gettype($value) == 'array') {
                    $this->getNodeCount($value, $nodeName, $count);
                }
            } else {
                if (!array_key_exists('@attributes', $xmlArray[$key]))
                    $count = count($xmlArray[$key]);
                else
                    $count = 1;
                break;
            }
        }

        return $count;
    }
}
