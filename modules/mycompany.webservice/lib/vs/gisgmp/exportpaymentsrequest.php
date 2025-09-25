<?php


namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper as RestHelper;
use MyCompany\Rest\Response;

/**Запрос на получение платежей
 * Class ExportPaymentsRequest
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ExportPaymentsRequest implements RequestSender
{
    const URN = '395dcc'; // УРН отправителя
    const ROLE_TYPE = '3'; // Администратор доходов бюджета
    const IBLOCK_CODE = 'PaymentInfoRequest';//Инфоблок ГИС ГМП [PaymentInfo] Request

    private string $startDate;
    private string $endDate;
    private string $guid;
    
    private $elementId;

    // private ImportedCharge $importedCharge;

    function __construct(string $startDate = '2024-04-18T13:13:03', string $endDate = ''){
        //guid
        $this->guid = 'G_' . \MyCompany\WebService\Helper::genUuid();

        //startDate
        $this->startDate = $startDate;

        //endDate
        if (empty($endDate)) {
            $this->endDate = date("Y-m-d\TH:s:i");
        } else {
            $this->endDate = $endDate;
        }
    }

    private function createRequest(){
        $request = '';
        $request = '<?xml version="1.0" encoding="UTF-8"?>';
        $request .= '<ns0:ExportPaymentsRequest xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-payments/2.6.0" Id="';
        $request .= $this->guid . '" timestamp="' . date("Y-m-d\TH:s:i\.0") . '" senderIdentifier="' . static::URN . '" senderRole="' . static::ROLE_TYPE . '">';
        $request .= '<com:Paging pageNumber="1" pageLength="100"/>
            <sc:PaymentsExportConditions kind="PAYMENT">
            <sc:TimeConditions>
                    <com:TimeInterval ';
                    $request .= 'endDate="' . $this->endDate . '" startDate="'.$this->startDate.'" />';
                $request .= '</sc:TimeConditions>
            </sc:PaymentsExportConditions>
        </ns0:ExportPaymentsRequest>';

        return $request;
    }

    /**Подготовить запрос по отправке начисления
     * @return string
     */
    function sendRequest(): string
    {
        $request = $this->createRequest();

        $ib = new \CIBlockElement;
        $this->elementId = $ib->Add([
            'NAME' => $this->guid,
            'IBLOCK_ID' => Helper::getIblockIdByCode(static::IBLOCK_CODE),
            'PROPERTY_VALUES' => [
                'StatusSended' => 'Создано',
                'GUID' => $this->guid,
                'Request' => $request
            ]
        ]);

        $gisgmpRequestSender = new GisgmpRequestSender();
        $result = $gisgmpRequestSender->sendRequest($request);
        
        if (($result['httpCode'] == 200) || ($result['httpCode'] == 100)) {
            \CIBlockElement::SetPropertyValuesEx(
                $this->elementId,
                Helper::getIblockIdByCode(static::IBLOCK_CODE),
                [
                    'StatusSended' => 'Доставлено'
                ]
            ); 

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

    //заккоментировал т.к. для отправки запроса нам не нужны никакие данные кроме промежутка даты
    /**Получить данные по начислению
     * @return array
     */
    // public function getImportedCharge(): array
    // {
    //     $this->importedCharge = new ImportedCharge();
    //     $importedChargeData = $this->importedCharge->getImportedChargeData();

    //     return $importedChargeData;
    // }
}
