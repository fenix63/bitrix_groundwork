<?php
namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\RequestHandler;
use MyCompany\WebService\WebServiceAbstractFactory;
use MyCompany\WebService\Helper;

/**Отправка квитанции
 * Class ExportQuittanceRequest
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ExportQuittanceRequest implements RequestSender
{
	CONST QUITTANCE_IBLOCK_CODE = 'gis-gmp-Quittance';
	const IBLOCK_CODE = 'GisgmpRequestQuittance';

    private string $id;
    private \DateTime $requestDate;
    private string $rqId;
    private string $urn;
    private bool $isSelectionEnd;
    private Quittance $quittance;
	private string $guid;
    private $elementId;

	function __construct(string $startDate = '2024-04-18T13:13:03', string $endDate = ''){
        //startDate
        $this->startDate = $startDate;
        //endDate
        if (empty($endDate)) {
            $this->endDate = date("Y-m-d\TH:s:i");
        } else {
            $this->endDate = $endDate;
        }

    }

	/**Подготовить запрос по отправке начисления
     * @return string
     */
    function sendRequest(): string
    {
		self::dbgLog(__LINE__.' '.__FILE__,'_sendRequest_');
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

	public static function dbgLog($data, string $suffix = '_1')
	{
        if (
            !empty($suffix)
            &&
            preg_match('![^-0-9a-zA-Z_]+!', $suffix)
        ) {
            $suffix = '';
        }

		$fileName = $_SERVER["DOCUMENT_ROOT"]."/"."logs/DBG/dbg-" . date("Ymd") . $suffix . ".txt";
        $r = fopen($fileName, 'a');
        fwrite($r, PHP_EOL);
        fwrite($r, date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($r, print_r($data, 1));
        fwrite($r, PHP_EOL);
        fclose($r);
	}

    public static function parseXml()
    {
		self::dbgLog('test1','_test1_');

        $request = new RequestHandler([], file_get_contents('php://input'));
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

				//TODO: Костыль. Пока не нашёл решение как от него избавиться
                $exportQuittancesResponseNode = $xml->children()->Body->GetResponseResponse->ResponseMessage->Response->SenderProvidedResponseData->MessagePrimaryContent->ExportQuittancesResponse;
				foreach($exportQuittancesResponseNode->Quittance as $itemType => $item){
                    $props = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::quittance($item);
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

        return 'success';
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
