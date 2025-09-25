<?php
namespace MyCompany\WebService\VS\Gisgmp;

\Bitrix\Main\Loader::includeModule('mycompany.rest');

use MyCompany\WebService\RequestHandler;
use MyCompany\WebService\WebServiceAbstractFactory;
use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper\Options;
use MyCompany\WebService\VS\Gisgmp\Bizproc\Common;

/**Получение ответа с выгрузкой платежей
 * Class ExportPaymentResponse
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ExportPaymentResponse implements ResponseHandler
{
    const PAYER_STATUS_IBLOCK_CODE = 'gis-gmp-payer-status';
    const CHARGES_IBLOCK_CODE = 'importedcharge';

    private string $id;
    private \DateTime $requestDate;
    private string $rqId;
    private string $urn;
    private bool $isEndSelection;
    private PayInfo $payInfo;
    public array $paymentsInfo;

    /**
     * Обработать запрос по получению платежей
     * @return array
     */
    public function prepareMessage(): string
    {
        return '';
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
    public static function getRequestData()
    {
        $xml = RequestHandler::getRequestSoap(file_get_contents('php://input'));
        $json = json_encode($xml);
        $requestData = json_decode($json, true);

        return $requestData;
    }

    public function parseXml()
    {
        $request = new RequestHandler([], file_get_contents('php://input'));

		$fileName = $_SERVER["DOCUMENT_ROOT"] . '/logs/payments/' . gmdate("Y-m-d\TH:i:s.\Z").'_'.time().'_'.rand(0, 9999999999).'.txt';
        file_put_contents($fileName, file_get_contents('php://input'), FILE_APPEND | LOCK_EX);

        $requestData = self::getRequestData();
        $messageId = $request->getNodeFromXmlArray($requestData, 'RqId');
        $fabric = new WebServiceAbstractFactory();
		$requestBody = self::clearXML($request->requestBody);
		$xml = simplexml_load_string($requestBody);
		$namespaces = simplexml_load_string($request->requestBody)->getNamespaces(true);

        $paymentIblockId = Helper::getIblockIdByCode('gis-gmp-PaymentInfo');
        $chargesIblockId = Helper::getIblockIdByCode(self::CHARGES_IBLOCK_CODE);
        $ib = new \CIBlockElement;
        $iblockLog = new \MyCompany\WebService\IblockLog(Helper::getIblockIdByCode('gis-gmp-PaymentInfo-Response'));
		$arr = Helper::parseXmlToArray($request->requestBody);
        $payerStatusesList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PayerInfo::getElementsFromIblock([]);

        //ID свойства автоквитированного платежа
        $propAcknowledgeEnumValueId = Helper::getPropertyEnumValue($paymentIblockId,'PAYMENT_IS_ACKNOWLEDGE','yes');
        //Получаем email'ы пользователей группы epgu_operator (Оператор ЕПГУ)
        $usersEmailsFromGroup = Helper::getUsersStringFromGroup('epgu_operator');

        $this->paymentsInfo['paymentIblockId'] = $paymentIblockId;
        $this->paymentsInfo['chargesIblockId'] = $chargesIblockId;
        $this->paymentsInfo['propAcknowledgeEnumValueId'] = $propAcknowledgeEnumValueId;
        $this->paymentsInfo['usersEmailsFromGroup'] = $usersEmailsFromGroup;

        foreach ($namespaces as $namespaceKey => $namespaceData) {
            if ($namespaceData == 'urn://roskazna.ru/gisgmp/xsd/services/export-payments/2.6.0') {
                //Создается элемент инфоблока ГИС ГМП [PaymentInfo] Response
                $responseId = $iblockLog->set($messageId, $request->requestBody);
				$arr = Helper::parseXmlToArray($request->requestBody);
                $tags = array_column($arr,'tag');
                $paymentNodeIndexList = array_keys($tags, strtoupper('Payment'));
                $exportPaymentsResponseNode = $xml->children()->Body->GetResponseResponse->ResponseMessage->Response->SenderProvidedResponseData->MessagePrimaryContent->ExportPaymentsResponse;
                $addedValues = self::getAddedPropsValue($exportPaymentsResponseNode, 'PaymentInfo_paymentId');//УПНО
                //Элементы, в которых нашлись УПНО (PaymentInfo_paymentId) из XML
                $elements = self::getElementsByPropValues('PaymentInfo_paymentId', $addedValues);

                foreach ($exportPaymentsResponseNode->PaymentInfo as $itemType => $item) {
                    //Свойства отдельно взятой ноды
                    $props = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::paymentInfo($item, $namespaces);
                    $payerStatusElementId = self::findElementIdByCode($props["BudgetIndex_status"], $payerStatusesList);
                    if (!in_array($props["PaymentInfo_paymentId"], $elements)) {
                        $props['XML'] = $request->requestBody;
                        $props['RqID'] = $messageId;
                        $props['ResponseId'] = $responseId;
                        if ($payerStatusElementId != -1)
                            $props['BudgetIndex_status'] = $payerStatusElementId;

                        //Копейки в рубли
                        if (!empty($props["PaymentInfo_amount"])) {
                            $props["PaymentInfo_amount"] = (double)$props["PaymentInfo_amount"];
                            $props["PaymentInfo_amount"] = $props["PaymentInfo_amount"] / 100;
                        }


                        $elId = $ib->Add([
                            'NAME' => explode('.000', str_replace('T', ' ', $props['PaymentInfo_paymentDate']))[0] . ' ' . $props['PaymentInfo_amount'] . ' руб. ' . $props['Payer_payerName'] . ' ' . $props['PaymentInfo_supplierBillID'],
                            'IBLOCK_ID' => $paymentIblockId,
                            'PROPERTY_VALUES' => $props
                        ]);

                        $bizProcTemplateId = Helper::getWorkFlowTemplateIdByTitle('Обработка запросов по платежам');
                        $wfId = \CBPDocument::StartWorkflow(
                            $bizProcTemplateId,
							["iblock", "CIBlockDocument", $elId],
							[],
							$errors
						);
                        $this->paymentsInfo['elements'][] = [
                            'props' => $props,
                            'id' => $elId
                        ];
                        //self::autoQuit($elId, $paymentIblockId, $chargesIblockId, $props, $propAcknowledgeEnumValueId, $usersEmailsFromGroup);
                    }

                }

            }
        }

        $ib = new \CIBlockElement;
        foreach ($namespaces as $namespaceKey => $namespaceData) {
            if ($namespaceData == 'urn://roskazna.ru/gisgmp/xsd/services/export-payments/2.6.0') {
                $iblockLog = new \MyCompany\WebService\IblockLog(Helper::getIblockIdByCode(''));
                $responseId = $iblockLog->set($messageId, $request->requestBody);

                foreach ($xml->children($namespaceData) as $item) {
                    $props = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::PaymentInfo($item, $namespaces);
                    $props['XML'] = $request->requestBody;
                    $props['RqID'] = $messageId;
                    $props['ResponseId'] = $responseId;

                    \MyCompany\WebService\VS\Gisgmp\payinfo::add($props, $messageId);
                }
            }
        }

        //return 'success';
    }

    /**На разных стендах отличаются ID шаблонов бизнес-процессов. Поэтому по названию шаблона получаем его номер
     * @param string $workflowTemplateName
     * @return int
     */
    public static function getWorkflowTemplateIdByTemplateName(string $workflowTemplateName): int
    {
        global $DB;
        $results = $DB->Query("SELECT ID,NAME FROM b_bp_workflow_template WHERE NAME='".$workflowTemplateName."'");
        $workFlowTemplateId = -1;
        if($row = $results->fetch()){
            $workFlowTemplateId = $row['ID'];
        }

        return $workFlowTemplateId;
    }

    /**Автоквитирование с созданием сущности "Квитовка" и с использованием бизнес-процесса сущности "Квитовка"
     * @param int $paymentElementId
     * @param int $paymentIblockId
     * @param int $chargesIblockId
     * @param array $elementProps
     * @param int $propAcknowledgeEnumValueId
     * @param string $usersEmailList
     */
    public static function autoQuitByBizProcess(int $paymentElementId, int $paymentIblockId, int $chargesIblockId ,array $elementProps, int $propAcknowledgeEnumValueId, string $usersEmailList)
    {
        $newElementUin = $elementProps["PaymentInfo_supplierBillID"];//УИН только что добавленного элемента
        if ($newElementUin != 0) {
            /*$charges = Helper::getElementsByFilter(
                ['ImportedCharge_supplierBillID', 'ImportedCharge_totalAmount', 'Balance'],
                ['IBLOCK_ID' => $chargesIblockId, 'PROPERTY_ImportedCharge_supplierBillID' => $newElementUin]
            );*/
            $charges = Helper::getElements(
                ['ID', 'PROPERTY_ImportedCharge_supplierBillID', 'PROPERTY_ImportedCharge_totalAmount', 'PROPERTY_Balance'],
                ['IBLOCK_ID' => $chargesIblockId, "SHOW_NEW"=>"Y",'PROPERTY_ImportedCharge_supplierBillID' => $newElementUin]
            );
            if (!empty($charges)) {
                //Надо создать элемент инфоблока квитовка, и заполнить в нём свойства ID платежа, ID начисления,
                //Учесть, Оплачено, Остаток, Идентификатор начисления
                $el = new \CIBlockElement;
                $matchIblockId = Helper::getIblockIdByCode('match');
                $chargeId = array_key_first($charges);
                $chargeObject = new ImportedCharge();
                $chargeObject->get(['ID' => $chargeId]);
                $chargeProps = $chargeObject->items[0];
                $paymentAccountValue = '';
                $chargeOstat = floatval($chargeProps['balance']);//У начисления берём Остаток

                //Сколько денег учесть из платежа. У платежа берём сумму платежа, а далее уже ведём расчёт.
                $paymentSumToTake = $elementProps["PaymentInfo_amount"];
                if($elementProps["PaymentInfo_amount"] > $chargeOstat){
                    $paymentSumToTake = $chargeOstat;
                }

                $fields = [
                    'IBLOCK_ID' => $matchIblockId,
                    'NAME' => 'Квитовка_Авто',
                    'PROPERTY_VALUES' => [
                        'PAYMENT' => $paymentElementId,
                        'CHARGE' => $chargeId,
                        'CHARGE_PAYED' => $chargeProps['paid'],//Оплачено (Оплачено из начисления)
                        'PAYMENT_ACCOUNT' => $paymentSumToTake,//Учесть (Если сумма платежа > Остаток из начисления, то записываем сюда Остаток из начисления, если платеж=начислению или меньше, то записываем сюда сумму платежа)
                        'CHARGE_BALANCE' => $chargeProps['balance']//Остаток (Остаток из начисления)
                    ]
                ];

                $matchElementId = $el->Add($fields);

                //После добавление элемента Квитовка нужно запуститьт бизнес-процесс для этого элемента
                $workflowTemplateId = self::getWorkflowTemplateIdByTemplateName('Квитовка');
                $errors = [];
                if ($matchElementId) {
                    $wfId = \CBPDocument::StartWorkflow(
                        $workflowTemplateId,
                        ["iblock", "CIBlockDocument", $matchElementId],
                        [],
                        $errors
                    );
                }

            }
        }
    }

    /**
     * @param int $paymentElementId
     * @param int $paymentIblockId
     * @param int $chargesIblockId
     * @param array $elementProps
     * @param string $workFlowId
     */
    public static function autoQuit(int $paymentElementId, int $paymentIblockId, int $chargesIblockId ,array $elementProps, int $propAcknowledgeEnumValueId, string $usersEmailList)
    {
        $newElementUin = $elementProps["PaymentInfo_supplierBillID"];//УИН только что добавленного элемента
        if ($newElementUin != 0) {
            $charges = Helper::getElementsByFilter(
                ['ImportedCharge_supplierBillID', 'ImportedCharge_totalAmount', 'Balance'],
                ['IBLOCK_ID' => $chargesIblockId, 'PROPERTY_ImportedCharge_supplierBillID' => $newElementUin]
            );
            if (!empty($charges)) {
                //Сумма платежа в платеже
                $paymentSum = (int)$elementProps["PaymentInfo_amount"];
                //Сумма начисления в начислении
                $chargeId = array_key_first($charges);
                $chargeItem = array_shift($charges);
                $chargeSum = (int)$chargeItem["ImportedCharge_totalAmount"]["VALUE"];
                $currentChargeBalance = (int)$chargeItem["Balance"]["VALUE"];

                //Сначала обновляем остаток начисления, потом уже выполняем команду "Link_Учесть платеж"

                \CIBlockElement::SetPropertyValuesEx(
                    $chargeId,
                    $chargesIblockId,
                    [
                        'Balance' => $currentChargeBalance - $paymentSum
                    ]
                );

                $chargeExecutionResult = Common::executeCommand(['elementid' => $chargeId, 'commandname' => 'Link_Учесть платеж']);;
                if ($chargeSum - $paymentSum > 0) {
                    //Переводим платеж в статус "Учтен"
                    $paymentCommandExecutionResult = Common::executeCommand(['elementid' => $paymentElementId, 'commandname' => 'Учесть платеж']);
                    \CIBlockElement::SetPropertyValuesEx(
                        $paymentElementId,
                        $paymentIblockId,
                        [
                            'PAYMENT_IS_ACKNOWLEDGE' => $propAcknowledgeEnumValueId
                        ]
                    );

                } else {
                    if ($chargeSum - $paymentSum == 0) {
                        //Переводим платеж в статус "Учтен"
                        $paymentCommandExecutionResult = Common::executeCommand(['elementid' => $paymentElementId, 'commandname' => 'Учесть платеж']);
                        \CIBlockElement::SetPropertyValuesEx(
                            $paymentElementId,
                            $paymentIblockId,
                            [
                                'PAYMENT_IS_ACKNOWLEDGE' => $propAcknowledgeEnumValueId
                            ]
                        );

                    } else {

                        //В свойство платежа "Частичный платеж: Сумма остатка платежа" заносим остаток.
                        $balance = $paymentSum - $chargeSum;
                        \CIBlockElement::SetPropertyValuesEx(
                            $paymentElementId,
                            $paymentIblockId,
                            [
                                'PartialPayt_sumResidualPayt' => $balance,
                                'PAYMENT_IS_ACKNOWLEDGE' => $propAcknowledgeEnumValueId
                            ]
                        );

                        //Переводим платеж в статус "Учтен частично"
                        $paymentCommandExecutionResult = Common::executeCommand(['elementid' => $paymentElementId, 'commandname' => 'Учесть платеж']);

                        $eventFields = [
                            '#PAYMENT_NUMBER#' => $paymentElementId,
                            '#PAYMENT_SUM#' => $paymentSum,
                            '#CHARGE_ID#' => $chargeId,
                            '#RELATED_CHARGE_SUM#' => $chargeSum,
                            '#ADMIN_EMAIL#' => $usersEmailList
                        ];
                        \CEvent::Send("GIS_GMP_ACKNOWLEDGEMENT", 's1', $eventFields);
                    }
                }

            }
        }
    }

    public static function getChargeBalance(int $iblockId, array $propCodeList)
    {
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'select' => $propCodeList,
            'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
            'runtime' => [
                'PROPERTY' => [
                    'data_type' => '\Bitrix\Iblock\IblockElementProperty',
                    'reference' => ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID']
                ]
            ]
        ]);
    }

    public static function findElementIdByCode(string $elementCode, array $items):int
    {
        $index = -1;
        foreach ($items as $key => $item) {
            if ($item['code'] == $elementCode)
                $index = $item['id'];
        }

        return $index;
    }

    /**Получить элементы с определенным значением свойства
     * @param string $propCode - код свойства
     * @param array $propValues - значения, по которым будут искаться элементы
     * @return array
     */
    public static function getElementsByPropValues(string $propCode, array $propValues): array
    {
        $paymentIblockId = Helper::getIblockIdByCode('gis-gmp-PaymentInfo');
        $dbItems = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $paymentIblockId,
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

    public static function getAddedPropsValue(\SimpleXMLElement $paymentsList, string $propCode): array
    {
        $props = [];
        foreach ($paymentsList->PaymentInfo as $itemType => $item) {
            $props[] = \MyCompany\WebService\VS\Gisgmp\ParserResponseGisGmp::paymentInfoByPropertyCode($item, $propCode);
        }

        return array_values($props);
    }
}
