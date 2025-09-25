<?php


namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\Helper;

/**Запрос на получение квитанций
 * Class ExportQuittanceRequest
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ExportQuittanceRequest implements RequestSender
{
    const URN = '395dcc'; // УРН отправителя
    const ROLE_TYPE = '3'; // Администратор доходов бюджета
    const IBLOCK_CODE = 'GisgmpRequestQuittance';

    private string $guid;
    
    private $elementId;
    // private $supplierBillID;

    private $startDate;
    private $endDate;

    function __construct(string $startDate = '2024-04-18T13:13:03', string $endDate = ''){

        //startDate
        $this->startDate = $startDate;

        //endDate
        if (empty($endDate)) {
            $this->endDate = date("Y-m-d\TH:s:i");
        } else {
            $this->endDate = $endDate;
        }

        $this->guid = 'G_'.\MyCompany\WebService\Helper::genUuid();
    }

    private function createRequest(){
        $request = '<?xml version="1.0" encoding="UTF-8"?>';
        ob_start();
        ?>
        <ns0:ExportQuittancesRequest xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/2.6.0" xmlns:qt="http://roskazna.ru/gisgmp/xsd/Quittance/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-quittances/2.6.0" Id="<?=$this->guid?>" timestamp="<?=date("Y-m-d\TH:s:i")?>" senderIdentifier="<?=static::URN?>" senderRole="<?=static::ROLE_TYPE?>">
            <sc:QuittancesExportConditions kind="QUITTANCE">
                <sc:TimeConditions>
                    <com:TimeInterval endDate="<?= $this->endDate ?>" startDate="<?= $this->startDate ?>" />
                </sc:TimeConditions>
            </sc:QuittancesExportConditions>
        </ns0:ExportQuittancesRequest>
        <?
        $request .= ob_get_clean();
        return $request;
    }

    /**Подготовить запрос по отправке начисления
     * @return string
     */
    function sendRequest(): string {
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

}
