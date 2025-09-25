<?php


namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\RequestHandler;
use MyCompany\WebService\WebServiceAbstractFactory;
use MyCompany\WebService\Helper;

/**
 * Class ForcedAckmowledgement
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ForcedAcknowledgementResponse implements ResponseHandler
{
    const IBLOCK_CODE = 'gis-gmp-faQuittance';

    private string $id;
    private \DateTime $date;
    private string $urn;
    private string $memberPower;//Полномочия участника-отправителя
    private ImportedCharge $importedCharge;
    

    /**Подготовить запрос по отправке начисления
     * @return string
     */
    function prepareMessage(): string
    {
        return '';
    }

    public static function getRequestData()
    {
        $xml = RequestHandler::getRequestSoap(file_get_contents('php://input'));
        $json = json_encode($xml);
        $requestData = json_decode($json, true);

        return $requestData;
    }

    public static function parseXml()
    {
        $request = new RequestHandler([], file_get_contents('php://input'));
        $requestData = self::getRequestData();
        $messageId = $request->getNodeFromXmlArray($requestData, 'RqId');
        $xml = simplexml_load_string($request->requestBody);
        $props = [];
        $props['Response'] = $request->requestBody;

        if (isset($xml->children('fa', true)->Quittance)){
            $propsQuittance = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::quittance($xml->children('fa', true)->Quittance);
            $resQuittance = \CIBlockElement::GetList(
                Array("SORT"=>"ASC"),
                Array('PROPERTY_Quittance_supplierBillID' => $propsQuittance['Quittance_supplierBillID'], 'IBLOCK_CODE' => 'gis-gmp-Quittance'),
                false,
                false,
                Array('ID', 'IBLOCK_ID', 'IBLOCK_CODE', 'PROPERTY_RqId')
            );

            if ($quittance = $resQuittance->GetNext()){
                $props['QuittanceLink'] = $quittance['ID'];
                $props['StatusSended'] = 'Обработано';
            } else {
                $props['StatusSended'] = 'Квитанция не найдена';
            }
            
        } else {
            if ((string)$xml->children('ns0', true)->Done == 'true') {
                $props['StatusSended'] = 'Обработано';
            } else {
                $props['StatusSended'] = 'Ошибка обработки';
            }
        }

        //Обновляем элемент
        $res = \CIBlockElement::GetList(
            Array("SORT"=>"ASC"),
            Array('PROPERTY_RqId' => $messageId, 'IBLOCK_CODE' => self::IBLOCK_CODE),
            false,
            false,
            Array('ID', 'IBLOCK_ID', 'IBLOCK_CODE', 'PROPERTY_RqId')
        );

        if ($el = $res->GetNext()){
            \CIBlockElement::SetPropertyValuesEx(
                $el['ID'],
                Helper::getIblockIdByCode(self::IBLOCK_CODE),
                $props,
                array()
            );
        } else {
            new \Exception('Не найден элемент по $messageId = '.$messageId);
        }


        return 'success';
    }
}
