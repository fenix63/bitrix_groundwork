<?php


namespace MyCompany\WebService\VS\Gisgmp;

require_once  $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require_once  $_SERVER['DOCUMENT_ROOT'].'/vendor/dompdf/autoload.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/phpqrcode/qrlib.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use MyCompany\WebService\RequestHandler;
use MyCompany\WebService\WebServiceAbstractFactory;
use MyCompany\WebService\Helper;
use MyCompany\WebService\VS\Gisgmp\Bizproc\Common;
use MyCompany\WebService\VS\Gisgmp\ImportedCharge;
use MyCompany\WebService\VS\Gisgmp\Bizproc\CBPChargesActivity;

/**Получение ответа по отправке начисления
 * Class ImportChargesResponse
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ImportChargesResponse implements ResponseHandler
{
    const bankCorrespondentBankAccount = '03100643000000019500';
    const bankBik = '024501901';

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

    public static function buildPDF(int $chargeId, array $chargeData)
    {
        $text = '';
        $serviceData = 'ST0012';

        //PersonalAcc - идентификатор плательщика
        //Name - наименование компании (Получатель платежа)
        //PersonalAcc - Идентификатор плательщика (номер расчетного счета)
        //BankName = Название банка (Банк получателя платежа)
        //BIC - БИК Банка
        //CorrespAcc - Корр счет банка получателя платежа
        $requiredData = 'Name=Государственная корпорация по атомной энергии "MyCompany"|PersonalAcc=40102810045370000002|BankName=Операционный департамент банка России/Межрегиональное операционое УФК г.Москва|BIC=024501901|CorrespAcc=03100643000000019500';

        //Contract - не понятно где это брать
        //Category (код вида платежа, 28 знаков) - не понятно откуда это брать
        //PercAcc - непонятно отокуда брать
        $additionRequisites = 'Sum=' . $chargeData["PROPERTY_VALUES"]["importedCharge_TotalAmount"] . '|PayeeInn=7706413348';

        $text .= $serviceData . $requiredData . $additionRequisites;

        \QRcode::png($text, $_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . 'charge_' . $chargeId . '.png');



        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);

        $htmlFileName = 'template_portrait_editable.html';

        $html = self::editCustomHTML($_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . $htmlFileName, $chargeData, $chargeId);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();



        $pdfFileName = 'sample_charge_' . $chargeId . '_output.pdf';
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . $pdfFileName, $dompdf->output());


        $arFile = \CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . 'sample_charge_' . $chargeId . '_output.pdf');
        \CIBlockElement::SetPropertyValuesEx($chargeId, Helper::getIblockIdByCode('importedcharge'), ['Printed_form' => $arFile]);

        //Удаляем docx и удаляем html
        unlink($_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/template_portrait_editable_'.$chargeId . '.html');
        unlink($_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . 'finish_charge_' . $chargeId . '.docx');
    }

    public static function editCustomHTML(string $filename, array $data, int $chargeId)
    {
        //Сначала копируем HTML-шаблон
        $fileNameCopy = str_replace('.html', '_' . $chargeId . '.html', $filename);
        CopyDirFiles($filename, $fileNameCopy);

        $mode = 'r+';
        $file = fopen($fileNameCopy, $mode);
        $inputHTML = file_get_contents($fileNameCopy);
        $resultHTML = $inputHTML;

        $qrCodePath = $_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/charge_'.$chargeId.'.png';
        $qrCodeType = pathinfo($qrCodePath, PATHINFO_EXTENSION);
        $qrCodeData = file_get_contents($qrCodePath);
        $base64 = 'data:image/' . $qrCodeType . ';base64,' . base64_encode($qrCodeData);


        $resultHTML = str_replace('#QR_CODE#', '<img src="' . $base64 . '">', $resultHTML);
        $resultHTML = str_replace('#CHARGE_ID#', $data["importedChargeSupplierBillID"], $resultHTML);
        $resultHTML = str_replace('#PAYER_ID#', $data["payerPayerIdentifier"], $resultHTML);
        $resultHTML = str_replace('#PAYER_INN#', $data["lawEntityInn"], $resultHTML);
        $resultHTML = str_replace('#PAYER_KPP#', $data["lawEntityKpp"], $resultHTML);
        $resultHTML = str_replace('#COMPANY_NAME#', $data["payerPayerName"], $resultHTML);
        $resultHTML = str_replace('#RECEIVER_NAME#', '&nbsp;', $resultHTML);
        $resultHTML = str_replace('#CORR_NUMBER#', self::bankCorrespondentBankAccount, $resultHTML);
        $resultHTML = str_replace('#RECEIVER_BIC#', self::bankBik, $resultHTML);
        $resultHTML = str_replace('#PAYMENT_TARGET#', $data['rqName'], $resultHTML);
        $resultHTML = str_replace('#PAYER_NAME#', $data['rqName'], $resultHTML);

        $resultHTML = str_replace('#ADMIN_ACCOUNT_NUMBER#', $data['orgAccountAccountNumber'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_INN#', $data['payeeInn'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_KPP#', $data['payeeKpp'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_OKTMO#', $data['orgOktmo'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_KBK#', $data['importedChargeKbk'], $resultHTML);

        $payerStatusCode = Helper::getIblockElementCodeById('gis-gmp-payer-status', $data["budgetIndexStatus"]["id"]);
        $resultHTML = str_replace('#ADMIN_101#', $payerStatusCode, $resultHTML);
        $resultHTML = str_replace('#ADMIN_106#', $data['paymentBasis'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_108#', $data['docBasisNumber'], $resultHTML);

        $taxDocDateDateTime = new \DateTime($data['budgetIndexTaxDocDate']);
        $taxDocDate = \Bitrix\Main\Type\DateTime::createFromPhp($taxDocDateDateTime);
        $taxDocDateCorrect = $taxDocDate->format("d.m.Y H:i");
        //$resultHTML = str_replace('#ADMIN_109#', $data['budgetIndexTaxDocDate'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_109#', $taxDocDateCorrect, $resultHTML);

        $resultHTML = str_replace('#SUMM#', $data['importedChargeTotalAmount'], $resultHTML);
        //\MyCompany\Rest\Helper::formatDateTime($row['DATE_CREATE']);

        //$dateTime = new DateTime();
        //$date = \MyCompany\Rest\Helper::formatDateTime($data['importedChargeBillDate']);

        $phpDateTime = new \DateTime($data['importedChargeBillDate']);
        $dateTime = \Bitrix\Main\Type\DateTime::createFromPhp($phpDateTime);
        $dateTimeCorrect = $dateTime->format("d.m.Y H:i");
        //$resultHTML = str_replace('#CHARGE_DATE#', $dateTime, $resultHTML);
        $resultHTML = str_replace('#CHARGE_DATE#', $dateTimeCorrect, $resultHTML);

        fseek($file, 0); // Возвращаем указатель на начало файла
        fwrite($file, $resultHTML);

        return $resultHTML;
    }

    public static function parseXml()
    {
        $request = new RequestHandler([], file_get_contents('php://input'));

		$fileName = $_SERVER["DOCUMENT_ROOT"] . '/logs/importcharges/' . gmdate("Y-m-d\TH:i:s.\Z").'_'.time().'_'.rand(0, 9999999999).'.txt';
		file_put_contents($fileName, file_get_contents('php://input'), FILE_APPEND | LOCK_EX);

        $requestData = self::getRequestData();
        $messageId = $request->getNodeFromXmlArray($requestData, 'RqId');

        $fabric = new WebServiceAbstractFactory();

        //TODO $xml будет пустой, т.к. в $request->requestBody в тегах присутствуют неймспейсы.
        //Нужно сначала получить неймспейсы, а потом уже смотреть дальше
        $requestBody= self::clearXML($request->requestBody);
        $xml = simplexml_load_string($requestBody);
        $namespaces = $xml->getDocNamespaces(true);


        $chargesIblockResponseLogId = Helper::getIblockIdByCode('gis-gmp-ImportCharges-Response');
        $iblockLog = new \MyCompany\WebService\IblockLog($chargesIblockResponseLogId);

		$el = new \CIBlockElement;
        $responseOriginalMessageId = $request->getNodeFromXmlArray($requestData, 'OriginalMessageId');

        foreach ($namespaces as $namespaceKey => $namespaceData) {
            if ($namespaceData == 'urn://roskazna.ru/gisgmp/xsd/services/import-charges/2.6.0') {
                $responseId = $iblockLog->set($messageId, $request->requestBody);
                //Получаем атрибуты узла ImportProtocol
                $importProtocolCodeAttr = $request->getNodeFromXmlArray($requestData, 'code');
                $importProtocolDescriptionAttr = $request->getNodeFromXmlArray($requestData, 'description');
                $importProtocolEntityIdAttr = $request->getNodeFromXmlArray($requestData, 'entityID');

				//$props['RqID'] = $messageId;
				//$props['ResponseId'] = $responseId;

				$importProtocolCode = $request->getNodeFromXmlArray($requestData, 'code');

                //Обновляем элемент инфоблока Начисления (основной)
                $chargeIblockElementId = str_replace('I_', '', $importProtocolEntityIdAttr);
				$res = $el->Update($chargeIblockElementId, ['PREVIEW_TEXT' => '']);


				//5 - Предоставленные участником данные уже присутствуют в системе
                if($importProtocolCode==5 || $importProtocolCode==0 || $importProtocolCode==9){


                    //Надо как то отличать - ответ пришёл когда мы просто отправили начисление, или когда мы сделали аннулирование
                    //Надо у элемента $chargeIblockElementId посмотреть свойство RequestId, взять оттуда ID элемента, далее
                    //нужно взять этот элемент в инфоблоке [ImportChargesrequest] и посмотреть его OriginalMessageId в XML. Если они совпадают - значит это ответ на обычную
                    //отправку нчисления.
                    //Если они НЕ совпадают, то сделать тоже самое: взять свойство Аннулирование - RequestID, взять элемент инфоблока [ImportCharges] Request,
                    //посмотреть MessageId, посмотреть OriginalMessageId в пришедшем ответе в XML если они совпадают - значит это ответ на аннулирование

                    $propertyRequestId = Helper::getElementsByFilter(
                        ['RequestId'],
                        ['IBLOCK_ID' => Helper::getIblockIdByCode('importedcharge'),'ID' => $chargeIblockElementId],
                    )[$chargeIblockElementId]['RequestId']['VALUE'];


                    $requestElementMessageId = Helper::getElementsByFilter(
                        ['MessageId'],
                        [
                            'IBLOCK_ID' => Helper::getIblockIdByCode('ImportChargesRequest'),
                            'ID' => $propertyRequestId
                        ]
                    )[$propertyRequestId]['MessageId']['VALUE'];



                    if($requestElementMessageId == $responseOriginalMessageId){

                        //Обновляем RequestId и ResponseId свойства для обычной отправки начисления
                        $props['RqID'] = $messageId;
                        $props['ResponseId'] = $responseId;

                    }else{
                        //Обновляем Аннулирование - RequestID и Аннулирование - ResponseID свойства для аннулирования начисления


                        $propertyAnnulRequestId = Helper::getElementsByFilter(
                            ['ANN_REQUEST_ID'],
                            ['IBLOCK_ID' => Helper::getIblockIdByCode('importedcharge'),'ID' => $chargeIblockElementId],
                        )[$chargeIblockElementId]['ANN_REQUEST_ID']['VALUE'];


                        $requestElementMessageId = Helper::getElementsByFilter(
                            ['MessageId'],
                            [
                                'IBLOCK_ID' => Helper::getIblockIdByCode('ImportChargesRequest'),
                                'ID' => $propertyAnnulRequestId
                            ]
                        )[$propertyAnnulRequestId]['MessageId']['VALUE'];


						\MyCompany\WebService\Log::info(
							$_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/chargesCancel_Request/responseId_-' . date("j-n-Y_H_i_s") . '.txt',
							$responseId
						);


                        if($requestElementMessageId == $responseOriginalMessageId){
                            $props['ANN_RESPONSE_ID'] = $responseId;
                        }
                    }


					\MyCompany\WebService\Log::info(
                        $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/chargesCancel_Request/updateChargeProps_-' . date("j-n-Y_H_i_s") . '.txt',
                        $props
                    );


                    //Обновляем элемент инфоблока Начисления (основной)
                    //TODO посмотреть , в каком мы статусе находимся тут. Если это статус "Подготовлен.Отложено на 5 секунд" - то придумать
                    //каким образом переключить в статус "Импортировано_readonly"

                    //
                    \MyCompany\WebService\VS\Gisgmp\ImportedCharge::updateIblockItem($props, $chargeIblockElementId);
                    if ($importProtocolDescriptionAttr == 'Успешно') {
                        //Надо получить текщий статус
                        $count = 0;
                        do{
                            $currentBPStatus = \MyCompany\WebService\VS\Gisgmp\Bizproc\Common::getExecutionStatus(['elementid'=>$chargeIblockElementId])->getData();
                            //self::dbgLog($currentBPStatus,'_CurStatus_');
                            sleep(2);
                            $count++;
                            $isStatusHolding = stripos($currentBPStatus['result'][0]['statusName'],'Отложено');
                        }while($isStatusHolding!==false && $count<5);


                        //Двигаем статус БП на "Импортировано" (только если мы находимся в статусе "Отправлено")
                        $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument(['iblock', 'CIBlockDocument', $chargeIblockElementId]);
                        if ($workflowIds) {
                            Common::executeCommand(['elementid' => $chargeIblockElementId, 'commandname' => 'System_Успешно']);

                            //Тут надо добавить генерацию печатной формы
                            $object = new ImportedCharge();
                            $object->get(['ID' => $chargeIblockElementId]);
                            self::buildPDF($chargeIblockElementId, $object->items[0] );
                        } else {
                            Common::executeCommand(['elementid' => $chargeIblockElementId, 'commandname' => 'System_Ошибка']);
                        }
                    }
                } else {
                    \MyCompany\WebService\Log::info(
                        $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/chargesCancel_Request/importProtocolCodeError_-' . date("j-n-Y_H_i_s") . '.txt',
                        $importProtocolCode
                    );

                    $props['ResponseId'] = $responseId;
                    \MyCompany\WebService\VS\Gisgmp\ImportedCharge::updateIblockItem($props, $chargeIblockElementId);

                    //Двигаем статус БП на "Ошибка приёма получателем"
                    $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument(['iblock', 'CIBlockDocument', $chargeIblockElementId]);
                    if ($workflowIds) {
                        Common::executeCommand(['elementid' => $chargeIblockElementId, 'commandname' => 'System_Ошибка']);
                    }
                }



                //Обновляем текст превью - туда будет записываться информация с ответом, а именно код аннулирования
                //Потом в бизнес процессе, проверяя этот код, будет понятно, в какой статус двигать бизнес-процесс
                $res = $el->Update($chargeIblockElementId, ['PREVIEW_TEXT' => 'ImportProtocol code:'.$importProtocolCode]);

            }
        }
//        foreach ($namespaces as $namespaceKey => $namespaceData) {
//            if ($namespaceData == 'urn://roskazna.ru/gisgmp/xsd/services/import-charges/2.6.0') {
//                $responseId = $iblockLog->set($messageId, $request->requestBody);
//
//                //foreach ($xml->children('com', true) as $itemType => $item){
//                foreach ($xml->children('com', true) as $itemType => $item){
//                    $props = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::importProtocol($item);
//                    $props['RqID'] = $messageId;
//                    $props['ResponseId'] = $responseId;
//
//                    \MyCompany\WebService\VS\Gisgmp\ImportedCharge::updateIblockItem($props, str_replace('I_', '', $props['ImportProtocol_entityID']));
//                }
//            }
//        }


        return 'success';
    }

    public static function clearXML(string $xml): string
    {
        $input = $xml;
        //Очистка конкретных тегов - будет переписано
        $xmlRequest = $input;
        $xmlRequest = str_replace("<pmnt:", "<", str_replace("</pmnt:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<org:", "<", str_replace("</org:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<com:", "<", str_replace("</com:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<soap:", "<", str_replace("</soap:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<tns:", "<", str_replace("</tns:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<basic:", "<", str_replace("</basic:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<tns:", "<", str_replace("</tns:", "</", $xmlRequest ));
        for ($i = 0; $i < 100; $i++) {
            $xmlRequest = str_replace("<ns$i:", "<", str_replace("</ns$i:", "</", $xmlRequest));
            $xmlRequest = str_replace("<sb$i:", "<", str_replace("</sb$i:", "</", $xmlRequest));
            $xmlRequest = str_replace(array("<xz$i:"), "<", str_replace("</xz$i:", "</", $xmlRequest));
        }
        $cleanXml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xmlRequest);

        return $cleanXml;
    }
}
