<?php
namespace MyCompany\WebService\VS\Gisgmp;

use Cassandra\Date;
use Exception;
use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper\HlBlock;
use MyCompany\WebService\RequestHandler;

/**Запрос с начислением
 * Class ImportChargesRequest
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ImportChargesRequest implements RequestSender
{
	const URN = '395dcc'; // УРН отправителя
	const ROLE_TYPE = '3'; // Администратор доходов бюджета
    const IBLOCK_CODE = 'ImportChargesRequest';
    const ORIGINATOR_ID = '395dcc';
    const TAX_DOC_DATE = '0';
    const TAX_PERIOD = '0';
    const PAYEE_NAME = 'Государственная корпорация по атомной энергии MyCompany';
    const PAYT_REASON = '0';

    private ImportedCharge $importedCharge;

    private array $ids;
    private string $guid;
    
    private $elementId;

    function __construct(){
        //guid
        $this->guid = 'G_'.\MyCompany\WebService\Helper::genUuid();
        $this->ids = [];
    }

    public function addRequestItem($id){
        $this->ids[] = $id;
    }

    private function createRequestFromBizProcess()
    {
        if (!empty($this->ids)) {
            $importedChargeData = self::getChargeData($this->ids);
            if (empty($importedChargeData)){
                new \Exception('Не удалось найти данные');
            }else{
                $payReasonList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PaymentBase::get([]);
                $payerStatusList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PayerInfo::get([]);

                $request = '<?xml version="1.0" encoding="UTF-8"?>';
                $request .= '
                <req:ImportChargesRequest xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:chg="http://roskazna.ru/gisgmp/xsd/Charge/2.6.0" xmlns:pkg="http://roskazna.ru/gisgmp/xsd/Package/2.6.0" xmlns:req="urn://roskazna.ru/gisgmp/xsd/services/import-charges/2.6.0" xmlns:rfd="http://roskazna.ru/gisgmp/xsd/Refund/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" 
                    Id="' . $this->guid .'" timestamp="' . date("Y-m-d\TH:s:i") .'" senderIdentifier="' . Helper::CHARGES_ETALON_SENDER_IDENTIFIER .'" senderRole="' . Helper::CHARGES_ETALON_SENDER_ROLE .'">';
                foreach ($importedChargeData as $item) {
                    $paytReasonCode = "00";
                    foreach ($payReasonList as $payReasonItem) {
                        if ($payReasonItem['slug'] == $item['PROPERTY_BudgetIndex_paytReason']) {
                            $paytReasonCode = $payReasonItem['code'];
                        }
                    }

                    $payerStatus = '01';
                    foreach($payerStatusList as $payerStatusItem){
                        if($item['PROPERTY_BudgetIndex_status']==$payerStatusItem['id'])
                            $payerStatus = $payerStatusItem['code'];
                    }
                    //TODO Возможен баг, если сохранить в битриксе с меткой времени 00:00:00
                    //Корректный формат даты:  01.05.2024 14:05:00
                    //Дата начисления должна быть строкой
                    //$item[PROPERTY_ImportedCharge_billDate]

                    $formatDate = $item["PROPERTY_ImportedCharge_billDate"]->format(\DateTime::ATOM);
                    $taxDocDate = 0;
                    if(!empty($item["PROPERTY_BudgetIndex_taxDocDate"])){
                        $taxDocDate = $item["PROPERTY_BudgetIndex_taxDocDate"]->format('d.m.Y');
                    }

                    $request .= '
                        <pkg:ChargesPackage>
                            <pkg:ImportedCharge
                                Id="I_' . $item['ID'] .'"
                                originatorId="' . self::ORIGINATOR_ID .'"
                                supplierBillID="' . $item['PROPERTY_ImportedCharge_supplierBillID'] .'"
                                billDate="' . $formatDate .'"
                                totalAmount="' . intval(floatval($item['PROPERTY_ImportedCharge_totalAmount']) * 100) .'" 
                                purpose="' . $item['NAME'] .'" 
                                kbk="' . $item['PROPERTY_ImportedCharge_kbk'] .'" 
                                oktmo="' . $item['PROPERTY_ImportedCharge_oktmo'] .'"
                            >
                                <org:Payee 
                                    name="' . self::PAYEE_NAME .'"
                                    inn="' . $item['PROPERTY_Payee_inn'] .'"
                                    kpp="' . $item['PROPERTY_Payee_kpp'] .'"
                                    ogrn="' . $item['PROPERTY_Payee_ogrn'] .'">
                                    <com:OrgAccount 
                                        accountNumber="' . $item['PROPERTY_Bank_correspondentBankAccount'] .'">
                                        <com:Bank 
                                            name="' . $item['PROPERTY_Bank_name'] .'" 
                                            bik="' . $item['PROPERTY_Bank_bik'] .'" 
                                            correspondentBankAccount="' . $item['PROPERTY_OrgAccount_accountNumber'] .'"/>
                                    </com:OrgAccount>
                                </org:Payee>
                                <chg:Payer payerIdentifier="' . $item['PROPERTY_Payer_payerIdentifier_2_0'] .'" payerName="' . str_replace("\"","",$item['PROPERTY_Payer_payerName']) .'"/>
                                <chg:BudgetIndex 
                                    status="' . $payerStatus .'"
                                    paytReason="' . $paytReasonCode .'"
                                    taxPeriod="' . $item["PROPERTY_BudgetIndex_taxPeriod"] .'"
                                    taxDocNumber="' . $item["PROPERTY_BudgetIndex_taxDocNumber"] .'" 
                                    taxDocDate="' . $taxDocDate .'"
                                />
                            </pkg:ImportedCharge>
                        </pkg:ChargesPackage>';
                }
                $request .= '</req:ImportChargesRequest>';

                return $request;
            }
        } else {
            new \Exception('Нет данных для формирования  запроса');
        }
    }

    private function getChargeData(array $elementIdList = null, int $limit = 1000, int $offset = 0)
    {
        $chargeIblockId = Helper::getIblockIdByCode('importedcharge');
        $iblockData = [];

        $select = ['ID', 'NAME', 'IBLOCK_ID', 'SORT'];
        $dbItemsCharge = \Bitrix\Iblock\ElementTable::getList([
            'select' => $select,
            'filter' => ['IBLOCK_ID' => $chargeIblockId, 'ID' => $elementIdList],
            'count_total' => 1
        ]);
        while($item = $dbItemsCharge->fetch()){
            $iblockData[$item['ID']] = $item;
        }

        foreach ($elementIdList as $elementId) {
            $dbProperty = \CIBlockElement::GetProperty($chargeIblockId, $elementId, [], []);
            while ($propItem = $dbProperty->fetch()) {
                $iblockData[$elementId]['PROPERTY_' . $propItem['CODE']] = $propItem['VALUE'];
                if ($propItem['USER_TYPE'] == 'Date') {
                    if(!empty($propItem['VALUE']))
                        $iblockData[$elementId]['PROPERTY_' . $propItem['CODE']] = new \DateTime($propItem['VALUE']);
                    else
                        $iblockData[$elementId]['PROPERTY_' . $propItem['CODE']] = null;
                }
            }
        }

        return $iblockData;
    }

    private function createRequest(){
        if (!empty($this->ids)) {
            $importedChargeData = $this->getImportedChargeData($this->ids);
            if (empty($importedChargeData)){
                new \Exception('Не удалось найти данные');
            } else {

                //$item['PROPERTY_BudgetIndex_paytReason'] = cur_year
                $payReasonList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PaymentBase::get([]);
                $payerStatusList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PayerInfo::get([]);

                $request = '<?xml version="1.0" encoding="UTF-8"?>';
				$request .= '
                <req:ImportChargesRequest xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:chg="http://roskazna.ru/gisgmp/xsd/Charge/2.6.0" xmlns:pkg="http://roskazna.ru/gisgmp/xsd/Package/2.6.0" xmlns:req="urn://roskazna.ru/gisgmp/xsd/services/import-charges/2.6.0" xmlns:rfd="http://roskazna.ru/gisgmp/xsd/Refund/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" 
                    Id="' . $this->guid .'" timestamp="' . date("Y-m-d\TH:s:i") .'" senderIdentifier="' . Helper::CHARGES_ETALON_SENDER_IDENTIFIER .'" senderRole="' . Helper::CHARGES_ETALON_SENDER_ROLE .'">';
                     foreach ($importedChargeData as $item) {
                        $paytReasonCode = "00'";
                         foreach ($payReasonList as $payReasonItem) {
                             if ($payReasonItem['slug'] == $item['PROPERTY_BudgetIndex_paytReason']) {
                                 $paytReasonCode = $payReasonItem['code'];
                             }
                         }

                        $payerStatus = '01';
                        foreach($payerStatusList as $payerStatusItem){
                            if($item['PROPERTY_BudgetIndex_status']==$payerStatusItem['id'])
                                $payerStatus = $payerStatusItem['code'];
                        }
						 //TODO Возможен баг, если сохранить в битриксе с меткой времени 00:00:00
						//Корректный формат даты:  01.05.2024 14:05:00
						//Дата начисления должна быть строкой
						//$item[PROPERTY_ImportedCharge_billDate]
						
						$formatDate = $item["PROPERTY_ImportedCharge_billDate"]->format(\DateTime::ATOM);
						$request .= '
                        <pkg:ChargesPackage>
                            <pkg:ImportedCharge
                                Id="I_' . $item['ID'] .'"
                                originatorId="' . self::ORIGINATOR_ID .'"
                                supplierBillID="' . $item['PROPERTY_ImportedCharge_supplierBillID'] .'"
                                billDate="' . $formatDate .'"
                                totalAmount="' . intval(floatval($item['PROPERTY_ImportedCharge_totalAmount']) * 100) .'" 
                                purpose="' . $item['NAME'] .'" 
                                kbk="' . $item['PROPERTY_ImportedCharge_kbk'] .'" 
                                oktmo="' . $item['PROPERTY_ImportedCharge_oktmo'] .'"
                            >
                                <org:Payee 
                                    name="' . self::PAYEE_NAME .'"
                                    inn="' . $item['PROPERTY_Payee_inn'] .'"
                                    kpp="' . $item['PROPERTY_Payee_kpp'] .'"
                                    ogrn="' . $item['PROPERTY_Payee_ogrn'] .'">
                                    <com:OrgAccount 
                                        accountNumber="' . $item['PROPERTY_Bank_correspondentBankAccount'] .'">
                                        <com:Bank 
                                            name="' . $item['PROPERTY_Bank_name'] .'" 
                                            bik="' . $item['PROPERTY_Bank_bik'] .'" 
                                            correspondentBankAccount="' . $item['PROPERTY_OrgAccount_accountNumber'] .'"/>
                                    </com:OrgAccount>
                                </org:Payee>
                                <chg:Payer payerIdentifier="' . $item['PROPERTY_Payer_payerIdentifier_2_0'] .'" payerName="' . $item['PROPERTY_Payer_payerName'] .'"/>
                                <chg:BudgetIndex 
                                    status="' . $payerStatus .'"
                                    paytReason="' . $paytReasonCode .'"
                                    taxPeriod="' . self::TAX_PERIOD .'"
                                    taxDocNumber="' . $item['PROPERTY_BudgetIndex_taxDocNumber'] .'" 
                                    taxDocDate="' . self::TAX_DOC_DATE .'"
                                />
                            </pkg:ImportedCharge>
                        </pkg:ChargesPackage>';
                    }
                	$request .= '</req:ImportChargesRequest>';

                return $request;
            }
        } else {
            new \Exception('Нет данных для формирования  запроса');
        }
    }


    function buildUpdateChargeXml()
    {
        if (!empty($this->ids)) {
            $importedChargeData = $this->getImportedChargeData($this->ids);
            if (empty($importedChargeData)) {
                new \Exception('Не удалось найти данные');
            }else{
                $elementId = $this->ids[0];
                $this->elementId = $elementId;
                $uin = $importedChargeData[$elementId]['PROPERTY_ImportedCharge_supplierBillID'];
                $date = date('Y-m-d H:i:s');
                $date = str_replace(' ','T', $date);
                $request = '<?xml version="1.0" encoding="UTF-8"?>
			<req:ImportChargesRequest xmlns:chg="http://roskazna.ru/gisgmp/xsd/Charge/2.6.0" xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:pkg="http://roskazna.ru/gisgmp/xsd/Package/2.6.0" xmlns:req="urn://roskazna.ru/gisgmp/xsd/services/import-charges/2.6.0" Id="' . $this->guid . '" timestamp="'.$date.'" senderIdentifier="395dcc" senderRole="3">
				<pkg:ChargesPackage>
					<pkg:ImportedChange originatorId="395dcc" Id="I_' . $elementId . '">
						<pkg:SupplierBillId>' . $uin . '</pkg:SupplierBillId>
						<com:ChangeStatus>
							<com:Meaning>2</com:Meaning>
							<com:Reason>Основание изменения - любой текст</com:Reason>
							<com:ChangeDate>'.$date.'</com:ChangeDate>
						</com:ChangeStatus>
					</pkg:ImportedChange>
				</pkg:ChargesPackage>
			</req:ImportChargesRequest>';

                return $request;
            }
        }else{
            new \Exception('Нет данных для формирования  запроса');
        }
    }

	/**
     * Аннулировать начисление
     */
    function cancelCharge()
    {
        $request = $this->buildCancelChargeXml();

        $xmlDoc = new \DOMDocument();
        $xmlDoc->preserveWhiteSpace = false;
        $xmlDoc->formatOutput = false;
        $xmlDoc->loadXML($request);
        $soapResponseRequestMinify = trim($xmlDoc->SaveXML());


        $chargeIblockId = Helper::getIblockIdByCode('importedcharge');
        $requestElementId = Helper::getIblockElementProps($chargeIblockId, $this->ids[0], 'RequestId')['RequestId'];
        $chargesRequestIblockId = Helper::getIblockIdByCode('ImportChargesRequest');
        $messageId = Helper::getIblockElementProps($chargesRequestIblockId, $requestElementId, 'MessageId')['MessageId'];

        $gisgmpRequestSender = new GisgmpRequestSender();
        $result = $gisgmpRequestSender->sendRequest($request);


        $el = new \CIBlockElement;

        if ($result['httpCode'] != 200) {
            \MyCompany\WebService\Log::info(
                $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/chargesCancel_Request/Curl_Error-' . date("j-n-Y_H_i_s") . '.txt',
                var_export($result['response'], true)
            );
            print_r("Return code is {$result['httpCode']} \n" . $result['httpCode']);
            throw new \Exception('Ошибка отправки подтверждения.' . "Return code is {$result['httpCode']} \n" . $result['httpCode']);
            return false;
        }else{

            $messageId = explode("\r\n\r\n", $result['response'])[1];

            //надо создать новый элемент ГИС ГМП [ImportCharges] Request и привязать его к элементу начисления в свойство Аннулирование - RequestID
            $requestElementProps['MessageId'] = $messageId;
            $requestElementProps['StatusSended'] = 'Доставлено';
            $requestElementProps['Request'] = $request;
            $requestElementProps['GUID'] = $this->guid;

            $fields = [
                'IBLOCK_ID' => $chargesRequestIblockId,
                'NAME' => $this->guid,
                'PROPERTY_VALUES' => $requestElementProps
            ];
            if(!$elID = $el->Add($fields))
                \MyCompany\WebService\Log::info(
                    $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/chargesCancel_Request/ErrorAddElementRequest-' . date("j-n-Y_H_i_s") . '.txt',
                    var_export($el->LAST_ERROR, true)
                );
            else{
                $return = $result;


                //Обвляем свойство ANN_REQUEST_ID начисления
                \CIBlockElement::SetPropertyValuesEx($this->ids[0], false, ['ANN_REQUEST_ID' => $elID]);

                //Обнуляем превью анонса
                //$res = $el->Update($this->ids[0], ['PREVIEW_TEXT' => '']);
            }



        }

        \MyCompany\WebService\Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/chargesCancel_Request/CURL_response-' . date("j-n-Y_H_i_s") . '.txt',
            var_export($return, true)
        );


        return $return;

    }

    function buildCancelChargeXml()
    {
        if (!empty($this->ids)) {
            $importedChargeData = $this->getImportedChargeData($this->ids);
            if (empty($importedChargeData)) {
                new \Exception('Не удалось найти данные');
            } else {
                //подставить в XML ID элемента (Id="I_33809")
                //<pkg:SupplierBillId>72523537983235379833</pkg:SupplierBillId> - УИН
                $elementId = $this->ids[0];
                $this->elementId = $elementId;
                $uin = $importedChargeData[$elementId]['PROPERTY_ImportedCharge_supplierBillID'];

                $request = '<?xml version="1.0" encoding="UTF-8"?>
			<req:ImportChargesRequest xmlns:chg="http://roskazna.ru/gisgmp/xsd/Charge/2.6.0" xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:pkg="http://roskazna.ru/gisgmp/xsd/Package/2.6.0" xmlns:req="urn://roskazna.ru/gisgmp/xsd/services/import-charges/2.6.0" Id="G_302e3537-3035-4837-b030-203137333339" timestamp="2024-12-12T09:27:24" senderIdentifier="395dcc" senderRole="3">
				<pkg:ChargesPackage>
					<pkg:ImportedChange originatorId="395dcc" Id="I_' . $elementId . '">
						<pkg:SupplierBillId>' . $uin . '</pkg:SupplierBillId>
						<com:ChangeStatus>
							<com:Meaning>3</com:Meaning>
							<com:Reason>Причина аннулирования - любой текст</com:Reason>
							<com:ChangeDate>2024-12-12T09:27:24</com:ChangeDate>
						</com:ChangeStatus>
					</pkg:ImportedChange>
				</pkg:ChargesPackage>
			</req:ImportChargesRequest>';

                return $request;
            }
        } else {
            new \Exception('Нет данных для формирования  запроса');
        }
    }


    function sendRequest(bool $isUpdate = false): string
    {
        if(!$isUpdate)
            $request = $this->createRequestFromBizProcess();
        else
            $request = $this->buildUpdateChargeXml();

		\MyCompany\WebService\Log::info(
			$_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/DBG/Request_-' . date("j-n-Y_H_i_s") . '.txt',
            $request
        );

        $ib = new \CIBlockElement;
        //Сначала создаём элемент инфоблока [ImportCharges] Request
        $this->elementId = $ib->Add([
            'NAME' => $this->guid,
            'IBLOCK_ID' => Helper::getIblockIdByCode(static::IBLOCK_CODE),
            'PROPERTY_VALUES' => [
                'StatusSended' => 'Создано',
                'GUID' => $this->guid,
                'Request' => $request
            ]
        ]);

        //Затем нужно привязать созданный элемет инфоблока [ImportCharges] Request к элементу инфоблока [ImportCharges] Начисления
        $importedChargeData = $this->getChargeData($this->ids);
        foreach ($importedChargeData as $item){
            \MyCompany\WebService\VS\Gisgmp\ImportedCharge::updateIblockItem(
                [
                    'RequestId' => $this->elementId
                ],
                $item['ID']
            );
        }

        $gisgmpRequestSender = new GisgmpRequestSender();
        $result = $gisgmpRequestSender->sendRequest($request);

        $response = explode("\r\n\r\n", $result['response']);
        $messageId = $response[1];

        //Текущий статус элемента начисления нужно как то получить и записать в лог
        $currentChargeElementStatus = \MyCompany\WebService\VS\Gisgmp\Bizproc\Common::getExecutionStatus(['elementid'=>$this->ids[0]])->getData();
        \MyCompany\WebService\Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/DBG/ChargeItemStatus_-' . date("j-n-Y_H_i_s") . '.txt',
            $currentChargeElementStatus
        );

        if (($result['httpCode'] == 200) || ($result['httpCode'] == 100)) {
            \CIBlockElement::SetPropertyValuesEx(
                $this->elementId,
                Helper::getIblockIdByCode(static::IBLOCK_CODE),
                [
                    'StatusSended' => 'Доставлено',
                    'MessageId' => $messageId
                ]
            );

            //TODO::Тут нужно проверить, если в теле ответа есть вот такой узел:
            //<ns2:AsyncProcessingStatus>
            //<ns2:StatusDetails>Бизнес-данные сообщения не соответствуют схеме, зарегистрированной в СМЭВ. MessageId = 89ef3a3f-5625-11ef-8645-fa163e8fb9a5</ns2:StatusDetails>
            //</ns2:AsyncProcessingStatus>
            //То нужно бизнес-процесс пеерводить в статус "Ошибка приёма получателем" (213)

            return true;
        } else {
            \CIBlockElement::SetPropertyValuesEx(
                $this->elementId,
                Helper::getIblockIdByCode(static::IBLOCK_CODE),
                [
                    'StatusSended' => 'Ошибка'
                ]
            );

            return false;
        }
    }

    /**Подготовить запрос по отправке начисления
     * @return string
     */
    function sendRequestOld(): string
    {
        $request = $this->createRequest();

        $ib = new \CIBlockElement;
        //Сначала создаём элемент инфоблока [ImportCharges] Request
        $this->elementId = $ib->Add([
            'NAME' => $this->guid,
            'IBLOCK_ID' => Helper::getIblockIdByCode(static::IBLOCK_CODE),
            'PROPERTY_VALUES' => [
                'StatusSended' => 'Создано',
                'GUID' => $this->guid,
                'Request' => $request
            ]
        ]);

        //Затем нужно привязать созданный элемет инфоблока [ImportCharges] Request к элементу инфоблока [ImportCharges] Начисления
        $importedChargeData = $this->getImportedChargeData($this->ids);
        foreach ($importedChargeData as $item){
            \MyCompany\WebService\VS\Gisgmp\ImportedCharge::updateIblockItem(
                [
                    'RequestId' => $this->elementId
                ], 
                $item['ID']
            );
        }

        $gisgmpRequestSender = new GisgmpRequestSender();
        $result = $gisgmpRequestSender->sendRequest($request);

        $response = explode("\r\n\r\n", $result['response']);
        $messageId = $response[1];

        if (($result['httpCode'] == 200) || ($result['httpCode'] == 100)) {
            \CIBlockElement::SetPropertyValuesEx(
                $this->elementId,
                Helper::getIblockIdByCode(static::IBLOCK_CODE),
                [
                    'StatusSended' => 'Доставлено',
                    'MessageId' => $messageId
                ]
            );

            //TODO::Тут нужно проверить, если в теле ответа есть вот такой узел:
            //<ns2:AsyncProcessingStatus>
            //<ns2:StatusDetails>Бизнес-данные сообщения не соответствуют схеме, зарегистрированной в СМЭВ. MessageId = 89ef3a3f-5625-11ef-8645-fa163e8fb9a5</ns2:StatusDetails>
            //</ns2:AsyncProcessingStatus>
            //То нужно бизнес-процесс пеерводить в статус "Ошибка приёма получателем" (213)

            return true;
        } else {
            \CIBlockElement::SetPropertyValuesEx(
                $this->elementId,
                Helper::getIblockIdByCode(static::IBLOCK_CODE),
                [
                    'StatusSended' => 'Ошибка'
                ]
            );

            return false;
        }
    }

    /**Получить данные по начислению
     * @return array
     */
    public function getImportedChargeData($ids = null): array
    {
        $this->importedCharge = new ImportedCharge();
        $importedChargeData = $this->importedCharge->getImportedChargeData($ids);
        
        return $importedChargeData;
    }
}
