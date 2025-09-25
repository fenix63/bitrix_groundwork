<?php


namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper as RestHelper;
use MyCompany\Rest\Response;

/**Запрос на принудительное квитирование извещения о начислении с извещениями о приеме к исполнению распоряжений
 * Class ForcedAcknowledgment
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ForcedAcknowledgmentCancel implements RequestSender
{
    const URN = '3eb646'; // УРН отправителя
    const ROLE_TYPE = '9'; // Администратор доходов бюджета
    const IBLOCK_CODE = 'gis-gmp-faQuittance'; 

    const TYPE = 'Отмена принудительного квитирование';

    private string $guid;
    private string $paymentId;
    private string $supplierBillId;
    
    private $elementId;

    // private ImportedCharge $importedCharge;

    function __construct($paymentId, $supplierBillId){
        //guid
        $this->guid = 'G_' . \MyCompany\WebService\Helper::genUuid();
        $this->paymentId = $paymentId;
        $this->supplierBillId = $supplierBillId;
        
    }

    private function createRequest(){
        $request = '';
        $request .= '<?xml version="1.0" encoding="UTF-8"?>';
        $request .= '<ns0:ForcedAcknowledgementRequest xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0"
            xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0"
            xmlns:qt="http://roskazna.ru/gisgmp/xsd/Quittance/2.6.0"
            xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0"
            xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/forced-ackmowledgement/2.6.0"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            Id="'.$this->guid.'" 
            timestamp="' . date("Y-m-d\TH:s:i\.0") . '" 
            senderIdentifier="'. static::URN . '" 
            senderRole="' . static::ROLE_TYPE . '"
        >
            <ns0:AnnulmentReconcile supplierBillId="'.$this->supplierBillId.'">
                <ns0:PaymentId>'.$this->paymentId.'</ns0:PaymentId>
            </ns0:AnnulmentReconcile>
        </ns0:ForcedAcknowledgementRequest>';

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
            'NAME' => static::TYPE .' '. $this->guid,
            'IBLOCK_ID' => Helper::getIblockIdByCode(static::IBLOCK_CODE),
            'PROPERTY_VALUES' => [
                'type' => static::TYPE,
                'StatusSended' => 'Создано',
                'RqID' => $this->guid,
                'Request' => $request,
                'faQuittance_SupplierBillID' => $this->supplierBillId,
                'faQuittance_paymentId' => $this->paymentId
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
}
