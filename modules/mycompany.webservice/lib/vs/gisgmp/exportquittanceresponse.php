<?php
namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\RequestHandler;
use MyCompany\WebService\WebServiceAbstractFactory;
use MyCompany\WebService\Helper;

/**Получение квитанций
 * Class ExportQuittanceResponse
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ExportQuittanceResponse implements ResponseHandler
{
    CONST QUITTANCE_IBLOCK_CODE = 'gis-gmp-Quittance';

    private string $id;
    private \DateTime $requestDate;
    private string $rqId;
    private string $urn;
    private bool $isSelectionEnd;
    private Quittance $quittance;

    public static function getRequestData()
    {
        $xml = RequestHandler::getRequestSoap(file_get_contents('php://input'));
        $json = json_encode($xml);
        $requestData = json_decode($json, true);

        return $requestData;
    }

    public static function clearXML(string $requestBody)
    {
        $cleanedXML = $requestBody;
        $cleanedXML = str_replace("<soap:", "<", str_replace("</soap:", "</", $cleanedXML ));
        $cleanedXML = str_replace("<tns:", "<", str_replace("</tns:", "</", $cleanedXML ));
        $cleanedXML = str_replace("<basic:", "<", str_replace("</basic:", "</", $cleanedXML ));
        $cleanedXML = str_replace("<ds:", "<", str_replace("</ds:", "</", $cleanedXML ));
        for ($i = 0; $i < 100; $i++) {
            $cleanedXML = str_replace("<ns$i:", "<", str_replace("</ns$i:", "</", $cleanedXML));
        }
        //Поиск и замена по регулярному выражению
        $cleanedXML = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $cleanedXML);

        return $cleanedXML;
    }


    public static function parseXml()
    {
        $request = new RequestHandler([], file_get_contents('php://input'));

		$fileName = $_SERVER["DOCUMENT_ROOT"] . '/logs/quittance/' . gmdate("Y-m-d\TH:i:s.\Z").'_'.time().'_'.rand(0, 9999999999).'.txt';
        file_put_contents($fileName, file_get_contents('php://input'), FILE_APPEND | LOCK_EX);

        $requestData = self::getRequestData();
        $messageId = $request->getNodeFromXmlArray($requestData, 'RqId');

        $fabric = new WebServiceAbstractFactory();
        $requestBody = self::clearXML($request->requestBody);
        $xml = simplexml_load_string($requestBody);
        $namespaces = simplexml_load_string($request->requestBody)->getNamespaces(true);

        $quittanceIblockId = Helper::getIblockIdByCode('gis-gmp-Quittance');
        $ib = new \CIBlockElement;
        $iblockLog = new \MyCompany\WebService\IblockLog(Helper::getIblockIdByCode('gis-gmp-Quittance-Response'));
        foreach ($namespaces as $namespaceKey => $namespaceData) {
            if ($namespaceData == 'urn://roskazna.ru/gisgmp/xsd/services/export-quittances/2.6.0') {
                //Предоставление информации о результатах квитирования
                $responseId = $iblockLog->set($messageId, $request->requestBody);
                $arr = Helper::parseXmlToArray($request->requestBody);
                $tags = array_column($arr,'tag');
                $quittanceNodeIndexList = array_keys($tags, strtoupper('Quittance'));
                $exportQuittancesResponseNode = $xml->children()->Body->GetResponseResponse->ResponseMessage->Response->SenderProvidedResponseData->MessagePrimaryContent->ExportQuittancesResponse;
                $addedValues = self::getAddedPropsValue($exportQuittancesResponseNode, 'Quittance_supplierBillID');

                //Элементы, в которых нашлись УИНы из XML
                $elements = self::getElementsByPropValues('Quittance_supplierBillID', $addedValues);

                foreach($exportQuittancesResponseNode->Quittance as $itemType => $item){
                    $props = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::quittance($item);
                    $props["Quittance_amountPayment"] = floatval($props["Quittance_amountPayment"]);
                    $props["Quittance_amountPayment"] = $props["Quittance_amountPayment"] / 100;
                    $props["Quittance_amountPayment"] = floatval(number_format($props["Quittance_amountPayment"],2,".",""));

                    $props["Quittance_totalAmount"] = floatval($props["Quittance_totalAmount"]);
                    $props["Quittance_totalAmount"] = $props["Quittance_totalAmount"] / 100;
                    $props["Quittance_totalAmount"] = floatval(number_format($props["Quittance_totalAmount"],2,".",""));

                    if (!in_array($props["Quittance_supplierBillID"], $elements)) {
                        $props['XML'] = $request->requestBody;
                        $props['RqID'] = $messageId;
                        $props['ResponseId'] = $responseId;

                        $elId = $ib->Add([
                            'NAME' => $messageId,
                            'IBLOCK_ID' => $quittanceIblockId,
                            'PROPERTY_VALUES' => $props
                        ]);
                    }
                }
            }
        }

        return 'success';
    }

    public static function getAddedPropsValue(\SimpleXMLElement $quittanceList, string $propCode): array
    {
        $props = [];
        foreach($quittanceList->Quittance as $itemType => $item){
            $props[] = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::quittanceByPropertyCode($item,$propCode);
        }

        return array_values($props);
    }

    /**Получить элементы с определенным значением свойства
     * @param string $propCode - код свойства
     * @param array $propValues - значения, по которым будут искаться элементы
     * @return array
     */
    public static function getElementsByPropValues(string $propCode, array $propValues): array
    {
        $quittanceIblockId = Helper::getIblockIdByCode(self::QUITTANCE_IBLOCK_CODE);
        $dbItems = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $quittanceIblockId,
                'PROPERTY_' . $propCode => $propValues
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'PROPERTY_' . $propCode]
        );
        $items = [];
        while ($item = $dbItems->fetch()) {
            $items[$item['ID']] = $item['PROPERTY_' . strtoupper($propCode) . '_VALUE'];
        }

        return $items;
    }

    public static function findIblockElements(string $messageId): array
    {
        $select = ['ID','PROPERTY_RqID'];
        $filter = ['PROPERTY_RqID' => $messageId, 'IBLOCK_CODE' => self::QUITTANCE_IBLOCK_CODE];
        $res = \CIBlockElement::GetList([], $filter, false, false, $select);
        $idList = [];
        while ($item = $res->fetch()) {
            $idList[] = $item['ID'];
        }

        return $idList;
    }

    /**Обработать запрос по получению квитанций
     * @return string
     */
    function prepareMessage(): string
    {
        return '';
    }

    function getQuittance(): array
    {
        $this->quittance = new Quittance();
        return $this->quittance->get();
    }
}
