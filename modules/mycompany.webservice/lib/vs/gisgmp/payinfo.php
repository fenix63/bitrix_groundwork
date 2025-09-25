<?php

namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper as RestHelper;
use MyCompany\Rest\Response;
use MyCompany\WebService\VS\Gisgmp\MatchEntity;

class PayInfo
{
    const IBLOCK_CODE = 'gis-gmp-PaymentInfo';
    const PAYMENTS_WORKFLOW_ID = 25;
	const WORKFLOW_TEMPLATE_NAME = 'Обработка запросов по платежам';
    const URN = '395dcc'; // УРН отправителя
    const ROLE_TYPE = '3'; // Администратор доходов бюджета
    const PAYER_STATUS_IBLOCK_CODE = 'gis-gmp-payer-status';

    public array $items;
    private PaymentOrg $paymentOrg;//Данные организации, принявшей платеж
    private Payer $payer;//Плательщик
    private Payee $payee;//Получатель
    private BudgetIndex $budgetIndex;//Доп. реквизиты платежа
    private Status $statusInfo;//Статус

    public $totalItemsCount;

    /**Получить доп реквизиты платежа
     * @return array
     */
    public function getBudgetIndexdata(): array
    {
        return $this->budgetIndex->get();
    }

    /**
     * Получить данные по плательщику
     */
    public function getPayerData(): array
    {
        return $this->payer->get();
    }

    /**
     * Предоставить данные по платежу
     * @return array
     */
    public function getEntityData(int $id): array
    {
        $ourData = [];
        foreach($this->items as $propKey => $propValue){
            $ourData[$propKey] = $propValue;
        }
        $ourData['paymentOrg'] = $this->getPaymentOrgData();
        $ourData['payer'] = $this->getPayerData();
        $ourData['payee'] = $this->getPayeeData();
        $ourData['budgetIndex'] = $this->getBudgetIndexdata();
        $ourData['status'] = $this->getStatusInfo();

        return $ourData;
    }

    /**TODO::Возможно этот метод и не нужен
     * Данные организации, принявшей платеж
     * @return array
     */
    public function getPaymentOrgData()
    {
        return $this->paymentOrg->get();
    }

    /**Получить данные по статусу
     * @return array
     */
    public function getStatusInfo(): array
    {
        return $this->statusInfo->get();
    }

    /**Получить данные по получаталю платежа
     * @return array
     */
    public function getPayeeData(): array
    {
        return $this->payee->get();
    }

    /**
     * @OA\Get(
     *   tags={"Payment"},
     *   path="/payment/payment/",
     *   summary="Получить информацию по платежам",
     *   @OA\Parameter(
     *     name="supplierbillid",
     *     in="query",
     *     required=false,
     *     example="",
     *     description="УИН",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="query",
     *     required=false,
     *     example="",
     *     description="id платежа",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="pagesize",
     *     in="query",
     *     required=false,
     *     example="5",
     *     description="Количество элементов на странице",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function get(array $filter, int $limit = 1000, int $offset = 0, array $sort = [], bool $isFromGrid = false)
    {
        $iblockId = Helper::getIblockIdByCode(self::IBLOCK_CODE);//37
        $iblockProps = Helper::getIblockProperties($iblockId);
        foreach ($iblockProps as $propCode => $propItem) {
            $iblockProps['PROPERTY_' . $propCode] = 'PROPERTY.PROPERTY_' . $propItem['ID'];
            $iblockPropsType['PROPERTY_' . $propCode] = $propItem['PROPERTY_TYPE'];
            unset($iblockProps[$propCode]);
        }
        $iblockPropsCodesList = array_keys($iblockProps);
        \Bitrix\Iblock\IblockElementPropertyTable::setProperties($iblockId);
        $select = array_merge(['ID', 'NAME', 'IBLOCK_ID', 'SORT', 'CREATED_BY', 'DATE_CREATE', 'TIMESTAMP_X'], $iblockProps);
        $sortField = array_key_first($sort);
        $sortCorrect = $sort;
        if (in_array('PROPERTY_' . $sortField, $iblockPropsCodesList)) {
            unset($sortCorrect);
            $sortCorrect['PROPERTY_' . $sortField] = $sort[$sortField];
        }
        if (!$isFromGrid)
            $offset = 0;

        if (!\CIBlockRights::userHasRightTo($iblockId, $iblockId, 'element_read')) {
            $this->items = [];
            return;
        }
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'order' => $sortCorrect,
            'select' => $select,
            'filter' => array_merge(['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'], $filter),
            'limit' => $limit,
            'offset' => $offset,
            'count_total' => 1,
            'runtime' => [
                'PROPERTY' => [
                    'data_type' => '\Bitrix\Iblock\IblockElementProperty',
                    'reference' => ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID']
                ]
            ]
        ]);
        $this->totalItemsCount = $dbItems->getCount();
        $items = [];
        while ($item = $dbItems->fetch()) {
            $item['PROPERTY_PaymentInfo_amount'] = number_format(floatval($item['PROPERTY_PaymentInfo_amount']),2,'.','');
            $items[] = $item;
        }
        $userIdList = array_unique(array_column($items,'CREATED_BY'));
        $authorInfo = Helper::getUserInfo($userIdList);
        $paymentAutoQuitEnumList = Helper::getEnumStringValue(
            Helper::getIblockIdByCode('gis-gmp-PaymentInfo'),
            'PAYMENT_IS_ACKNOWLEDGE',
            'yes'
        );
        foreach ($items as &$item) {
            $item['CREATED_BY_ID'] = $item['CREATED_BY'];
            $item['CREATED_BY'] = $authorInfo[$item['CREATED_BY']];
            if ($item['PROPERTY_PAYMENT_IS_ACKNOWLEDGE']) {
                $item['PROPERTY_PAYMENT_IS_ACKNOWLEDGE'] = $paymentAutoQuitEnumList[$item['PROPERTY_PAYMENT_IS_ACKNOWLEDGE']];
            }
        }

        //Если вызывается из грида - не примеять схемы
        if (!$isFromGrid)
            self::prepareElements($items, 'payment');

        //Если у платежа статус "Учтен" или "Учтен частично" - то нужно для этого платежа узнать дату, когда он был сквитирован - то есть
        //дату, когда он был учтен в каком-либо начислении
        //Смотрим в инфоблок "Квитовка". Ищем элемент этого инфоблока, у которого в свойстве PAYMENT (Платеж) стоит ID текущего платежа
        //И у найденного элемента "Квитовка" берём дату созданию - это и будет дата квитирования платежа
        //$paymentsIdList = array_column($items, 'id');
        self::getPaymentQuitDate($items);
        $this->items = $items;
    }

    public function getPaymentSum(int $paymentId)
    {
        $props = [];
        \CIBlockElement::GetPropertyValuesArray(
            $props,
            Helper::getIblockIdByCode(self::IBLOCK_CODE),
            ['ID' => $paymentId],
            ['CODE' => ['PaymentInfo_amount']]
        );

        return $props;
    }


    public static function getPaymentQuitDate(array &$paymentList)
    {
        $quitelements = [];//Квитовка
        $paymentIdList = array_column($paymentList,'id');
        $dbItems = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => Helper::getIblockIdByCode('match'), 'PROPERTY_PAYMENT' => $paymentIdList],
            false,
            false,
            ['ID','DATE_CREATE','PROPERTY_PAYMENT']
        );
        while($item = $dbItems->fetch()){
            $quitelements[$item['ID']] = $item;

        }

        foreach ($quitelements as $quiteItem) {
            foreach ($paymentList as &$paymentItem) {
                if ($paymentItem['id'] == (int)$quiteItem['PROPERTY_PAYMENT_VALUE']) {
                    $paymentItem['quiteDate'] = $quiteItem['DATE_CREATE'];
                }
            }
        }

    }


    /**
     * @OA\Post(
     *   tags={"Payment"},
     *   path="/payment/payment/",
     *   summary="Добавить платеж",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(
     *           property="paymentInfoDateStart",
     *           type="date",
     *           example="01.05.2024",
     *           description="Начальная дата"
     *         ),
     *         @OA\Property(
     *           property="paymentInfoDateEnd",
     *           type="date",
     *           example="08.10.2024",
     *           description="Конечная дата"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexStatus",
     *           type="number",
     *           example="2788",
     *           description="ID элемента инфоблока ГИС ГМП Статус платильщика"
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function addItem($params): array
    {

        $props = self::prepareProps($params);

        $elementId = self::add($props, $params["name"]);

        $object = new PayInfo();
        $object->get(['ID' => $elementId]);

        return ['results' => $object->items];
    }

    public static function add($props, $name = ''){
        $el = new \CIBlockElement;
        $guid = 'G_' . \MyCompany\WebService\Helper::genUuid();
        $props['RqID'] = $guid;
        $props['XML'] = self::generateXML($guid, $props["PaymentInfo_date_start"], $props["PaymentInfo_date_end"]);
        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(PayInfo::IBLOCK_CODE),
            'PROPERTY_VALUES' => $props,
            'NAME' => $guid,
            'ACTIVE' => 'Y'
        ];
        if($elementId = $el->Add($fields)) {
            $errors = [];
            $wfId = \CBPDocument::StartWorkflow(
                self::PAYMENTS_WORKFLOW_ID,
                ["iblock", "CIBlockDocument", $elementId],
                [],
                $errors
            );
            if (count($errors) > 0) {
                Response::createError('Для элемента ', $elementId . ' не удалось запустить бизнес-процесс');
            }
            return $elementId;
        }
        else
            Response::createError(
                'Ошибка добавления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );
    }

    public static function generateXML(string $guid, string $startDate, string $endDate): string
    {
        $request = '';
        $request = '<?xml version="1.0" encoding="UTF-8"?>';
        $request .= '<ns0:ExportPaymentsRequest xmlns:com="http://roskazna.ru/gisgmp/xsd/Common/2.6.0" xmlns:org="http://roskazna.ru/gisgmp/xsd/Organization/2.6.0" xmlns:sc="http://roskazna.ru/gisgmp/xsd/SearchConditions/2.6.0" xmlns:pmnt="http://roskazna.ru/gisgmp/xsd/Payment/2.6.0" xmlns:ns0="urn://roskazna.ru/gisgmp/xsd/services/export-payments/2.6.0" Id="';
        $request .= $guid . '" timestamp="' . date("Y-m-d\TH:s:i\.0") . '" senderIdentifier="' . static::URN . '" senderRole="' . static::ROLE_TYPE . '">';
        $request .= '<com:Paging pageNumber="1" pageLength="100"/>
            <sc:PaymentsExportConditions kind="PAYMENT">
            <sc:TimeConditions>
                    <com:TimeInterval ';
        $request .= 'endDate="' . $endDate . '" startDate="'.$startDate.'" />';
        $request .= '</sc:TimeConditions>
            </sc:PaymentsExportConditions>
        </ns0:ExportPaymentsRequest>';

        return $request;
    }

    /*
     * @OA\Put(
     *   tags={"Payment"},
     *   path="/payment/payment/{id}",
     *   summary="Обновить платеж",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(
     *           property="name",
     *           type="string",
     *           example="Новый платеж",
     *           description="Наименование платежа"
     *         ),
     *         @OA\Property(
     *           property="rqid",
     *           type="string",
     *           example="134686",
     *           description="ID запроса"
     *         ),
     *         @OA\Property(
     *           property="paymentid",
     *           type="string",
     *           example="789456",
     *           description="УПНО"
     *         ),
     *         @OA\Property(
     *           property="purpose",
     *           type="string",
     *           example="Назначение платежа",
     *           description="Назначение платежа"
     *         ),
     *         @OA\Property(
     *           property="amount",
     *           type="integer",
     *           example="540",
     *           description="Сумма возврата"
     *         ),
     *         @OA\Property(
     *           property="paymentdate",
     *           type="date",
     *           example="01.05.2024",
     *           description="Дата приема к исполнению распоряжения плательщика"
     *         ),
     *         @OA\Property(
     *           property="kbk",
     *           type="string",
     *           example="879879",
     *           description="КБК"
     *         ),
     *         @OA\Property(
     *           property="oktmo",
     *           type="string",
     *           example="8787",
     *           description="Код по ОКТМО, , указанный в извещении о зачислении"
     *         ),
     *         @OA\Property(
     *           property="transkind",
     *           type="string",
     *           example="Тестовый вид операции",
     *           description="Вид операции"
     *         ),
     *         @OA\Property(
     *           property="uin",
     *           type="string",
     *           example="456",
     *           description="УИН"
     *         ),
     *         @OA\Property(
     *           property="commeaning",
     *           type="string",
     *           example="Тестовый статус",
     *           description="Статус, отражающий изменение данных"
     *         ),
     *         @OA\Property(
     *           property="bik",
     *           type="string",
     *           example="6789",
     *           description="БИК ТОФК, структурного подразделения кредитной организации или подразделения Банка России, в котором открыт счет"
     *         ),
     *         @OA\Property(
     *           property="payername",
     *           type="string",
     *           example="Тестовый Плательщик",
     *           description="Плательщик"
     *         ),
     *         @OA\Property(
     *           property="payerIdentifier",
     *           type="string",
     *           example="23456",
     *           description="Идентификатор плательщика"
     *         ),
     *         @OA\Property(
     *           property="orgpayee_name",
     *           type="string",
     *           example="Тестовое наименование организации",
     *           description="Наименование организации"
     *         ),
     *         @OA\Property(
     *           property="inn",
     *           type="string",
     *           example="1234",
     *           description="ИНН организации"
     *         ),
     *         @OA\Property(
     *           property="kpp",
     *           type="string",
     *           example="56789",
     *           description="КПП организации"
     *         ),
     *         @OA\Property(
     *           property="accountnumber",
     *           type="string",
     *           example="Тестовый номер",
     *           description="Номер казначейского счета или номер счета получателя средств в банке получателя"
     *         ),
     *         @OA\Property(
     *           property="combank_bik",
     *           type="string",
     *           example="2345",
     *           description="БИК ТОФК, структурного подразделения кредитной организации или подразделения Банка России, в котором открыт счет"
     *         ),
     *         @OA\Property(
     *           property="correspondentbankaccount",
     *           type="string",
     *           example="5237464",
     *           description="Номер единого казначейского счета или корреспондентского счета кредитной организации, открытый в подразделении Банка России"
     *         ),
     *         @OA\Property(
     *           property="pmntBudgetIndex_status",
     *           type="string",
     *           example="Тестовый статус плательщика",
     *           description="Статус плательщика"
     *         ),
     *         @OA\Property(
     *           property="pmntBudgetIndex_paytReason",
     *           type="string",
     *           example="Тестовый показатель",
     *           description="Показатель основания платежа"
     *         ),
     *         @OA\Property(
     *           property="pmntBudgetIndex_taxPeriod",
     *           type="string",
     *           example="Тестовый показатель налогового периода",
     *           description="Показатель налогового периода или код таможенного органа, осуществляющего в соответствии с законодательством РФ функции по выработке государственной политики и нормативному регулированию, контролю и надзору в области таможенного дела"
     *         ),
     *         @OA\Property(
     *           property="pmntBudgetIndex_taxDocNumber",
     *           type="string",
     *           example="Тестовый показатель номера документа",
     *           description="Показатель номера документа"
     *         ),
     *         @OA\Property(
     *           property="pmntBudgetIndex_taxDocDate",
     *           type="string",
     *           example="Тестовый Показатель даты документа",
     *           description="Показатель даты документа"
     *         ),
     *         @OA\Property(
     *           property="IncomeInfo_receiptIncomeStatus",
     *           type="string",
     *           example="Тестовый Статус сопоставление платежа и зачислений",
     *           description="Статус сопоставление платежа и зачислений"
     *         ),
     *         @OA\Property(
     *           property="xml",
     *           type="string",
     *           example="xml",
     *           description="xml"
     *         ),
     *         @OA\Property(
     *           property="bank_bik",
     *           type="string",
     *           example="534468",
     *           description="БИК ТОФК, структурного подразделения кредитной организации или подразделения Банка России, в котором открыт счет"
     *         ),
     *         @OA\Property(
     *           property="payerName",
     *           type="string",
     *           example="Тестовый плательщик",
     *           description="Плательщик"
     *         ),
     *         @OA\Property(
     *           property="payer_payerIdentifier",
     *           type="string",
     *           example="647474",
     *           description="Идентификатор плательщика"
     *         ),
     *         @OA\Property(
     *           property="payee_name",
     *           type="string",
     *           example="ООО Рога и копыта",
     *           description="Наименование организации"
     *         ),
     *         @OA\Property(
     *           property="payee_inn",
     *           type="string",
     *           example="654786686",
     *           description="ИНН организации"
     *         ),
     *         @OA\Property(
     *           property="payee_kpp",
     *           type="string",
     *           example="65475",
     *           description="КПП организации"
     *         ),
     *         @OA\Property(
     *           property="orgaccount_accountnumber",
     *           type="string",
     *           example="8980878",
     *           description="Номер казначейского счета или номер счета получателя средств в банке получателя"
     *         ),
     *         @OA\Property(
     *           property="bank_correspondentbankaccount",
     *           type="string",
     *           example="02134",
     *           description="Номер единого казначейского счета или корреспондентского счета кредитной организации, открытый в подразделении Банка России"
     *         ),
     *         @OA\Property(
     *           property="budgetindex_status",
     *           type="string",
     *           example="Тестовый статус плательщика",
     *           description="Статус плательщика"
     *         ),
     *         @OA\Property(
     *           property="budgetindex_paytreason",
     *           type="string",
     *           example="Тестовый показатель основания платежа",
     *           description="Показатель основания платежа"
     *         ),
     *         @OA\Property(
     *           property="budgetindex_taxperiod",
     *           type="string",
     *           example="Тестовый Показатель налогового периода",
     *           description="Показатель налогового периода или код таможенного органа, осуществляющего в соответствии с законодательством РФ функции по выработке государственной политики и нормативному регулированию, контролю и надзору в области таможенного дела"
     *         ),
     *         @OA\Property(
     *           property="budgetindex_taxdocnumber",
     *           type="string",
     *           example="Тестовый Показатель номера документа",
     *           description="Показатель номера документа"
     *         ),
     *         @OA\Property(
     *           property="budgetindex_taxdocdate",
     *           type="string",
     *           example="Тестовый Показатель даты документа",
     *           description="Показатель даты документа"
     *         ),
     *         @OA\Property(
     *           property="paymentinfo_date_start",
     *           type="date",
     *           example="02.05.2024",
     *           description="Начальная дата"
     *         ),
     *         @OA\Property(
     *           property="paymentinfo_date_end",
     *           type="date",
     *           example="03.05.2024",
     *           description="Конечная дата"
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function updateItem(array $params, int $elementId): array
    {
        $el = new \CIBlockElement;
        $props = self::prepareProps($params);
        $fields = [
            'IBLOCK_ID' => Helper::getIblockIdByCode(static::IBLOCK_CODE),
            'NAME' => $params["name"]
        ];
        $res = $el->Update($elementId, $fields);//Обновляем поля
        \CIBlockElement::SetPropertyValuesEx($elementId, false, $props);//Обновляем свойства
        if ($res) {
            $object = new PayInfo();
            $object->get(['ID' => $elementId]);
        }
        else
            Response::createError(
                'Ошибка обновления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );

        return $object->items;
    }

    public static function updateItemBizProcess(array $params, int $elementId): array
    {
        $el = new \CIBlockElement;
        $props = self::prepareProps($params);
        $fields = [
            'IBLOCK_ID' => Helper::getIblockIdByCode(static::IBLOCK_CODE),
            'NAME' => $params["name"]
        ];
        $res = $el->Update($elementId, $fields);//Обновляем поля
        \CIBlockElement::SetPropertyValuesEx($elementId, false, $props);//Обновляем свойства
        if ($res) {
            //$object = new PayInfo();
            //$object->get(['ID' => $elementId]);
        }
        else
            Response::createError(
                'Ошибка обновления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );

        //return $object->items;
        return [];
    }

    /*
     * @OA\Delete(
     *   tags={"Payment"},
     *   path="/payment/payment/{id}",
     *   summary="Удалить элемент инфоблока",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function deleteItem(int $elementId): Response
    {
        global $DB;
        $DB->StartTransaction();
        if(!\CIBlockElement::Delete($elementId))
        {
            $DB->Rollback();
            $result =  Response::createError(
                'Ошибка удаления элемента инфоблока!',
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );
        }
        else {
            $DB->Commit();
            $result = Response::createSuccess('Элемент успешно удалён!');
        }

        return $result;
    }

    public static function prepareProps(array $input): array
    {
        $preparedProps = [];
        $schema = (RestHelper\Options::getMapFromOptions('schema_payment'));
        foreach ($input as $propItemKey => $propItem) {
            $preparedProps[$schema[$propItemKey]] = $propItem;
        }

        return $preparedProps;
    }

    public static function prepareElements(array &$items, string $schemaName)
    {
        $schema = array_flip(\MyCompany\Rest\Helper\Options::getMapFromOptions('schema_' . $schemaName));
        foreach ($items as &$item) {
            foreach ($item as $propCode => $property) {
                $curProperty = str_replace('PROPERTY_', '', $propCode);
                if (array_key_exists($curProperty, $schema))
                    $item[$schema[$curProperty]] = $property;

                unset($item[$propCode]);
            }
        }

        $payerStatusIdList = array_column($items, $schema['BudgetIndex_status']);
        $payerStatusDataList = self::getPayerStatusData($payerStatusIdList);
        foreach ($items as &$item) {
            $statusElementId = $item[$schema['BudgetIndex_status']];
            $item[$schema['BudgetIndex_status']] = $payerStatusDataList[$statusElementId];
        }
        $keysToConvert = ['ID'];
        foreach ($keysToConvert as $key) {
            if (array_key_exists($key, $schema))
                foreach ($items as &$item) {
                    $item[$schema[$key]] = (int)$item[$schema[$key]];
                }
        }

    }

    public static function getPayerStatusData(array $statusElementIdList): array
    {
        //Нужно получить title (PREVIEW_TEXT), id (ID), slug (NAME), code (CODE)
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'NAME', 'PREVIEW_TEXT', 'CODE'],
            'filter' => [
                'IBLOCK_ID' => Helper::getIblockIdByCode(self::PAYER_STATUS_IBLOCK_CODE),
                'ID' => $statusElementIdList
            ],
        ]);
        $statusData = [];
        while ($item = $dbItems->fetch()) {
            $statusData[$item['ID']]['title'] = $item['PREVIEW_TEXT'];
            $statusData[$item['ID']]['id'] = (int)$item['ID'];
            $statusData[$item['ID']]['slug'] = $item['NAME'];
            $statusData[$item['ID']]['code'] = $item['CODE'];
        }

        return $statusData;
    }

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

}
