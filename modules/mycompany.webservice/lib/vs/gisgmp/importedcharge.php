<?php


namespace MyCompany\WebService\VS\Gisgmp;

require_once  $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require_once  $_SERVER['DOCUMENT_ROOT'].'/vendor/dompdf/autoload.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/phpqrcode/qrlib.php';

\Bitrix\Main\Loader::includeModule('MyCompany.rest');

use MyCompany\WebService\Helper;
use MyCompany\Rest\Response;
use MyCompany\Rest\Helper as RestHelper;
use MyCompany\Rest\Helper\HlBlock;
use MyCompany\WebService\VS\Gisgmp\Dictionary\PayerInfo;
use MyCompany\Rest\Controller\Dictionary\PaymentBase;
use MyCompany\WebService\VS\Gisgmp\Dictionary\PaymentBase as PaymentBaseModel;
use MyCompany\WebService\VS\Gisgmp\Dictionary\Countries;
use MyCompany\WebService\VS\Gisgmp\Dictionary\IdentityCard;

use \PhpOffice\PhpWord\PhpWord;
use \PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Dompdf\Dompdf;
use Dompdf\Options;

/**Начисление
 * Class ImportedCharge
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ImportedCharge
{
    const CHARGE_WORKFLOW_ID = 26;
    const WORKFLOW_TEMPLATE_NAME = 'Обработка запросов по начислениям';
    const IBLOCK_CODE = 'importedcharge';
    const PAYER_STATUS_IBLOCK_CODE = 'gis-gmp-payer-status';

    const orgName = 'Государственная корпорация по атомной энергии "MyCompany"';
    const orgInn = '---------';
    const orgKpp = '---------';
    const orgOgrn = '---------';
    const orgOktmo = '---------';
    const bankCorrespondentBankAccount = '---------';
    const OrgAccountAccountNumber = '---------';
    const bankBik = '---------';
    const bankName = 'Операционный департамент банка России/Межрегиональное операционое УФК г.Москва';

    private $props;
    public array $items;
    public $totalItemsCount;
    private BudgetIndex $budgetIndex;

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
    /**Получить доп реквизиты платежа
     * @return array
     */
    public function getBudgetIndex(): array
    {
        $this->budgetIndex = new BudgetIndex();
        $budgetIndexData = $this->budgetIndex->get();

        return $budgetIndexData;
    }

    /**Получить данные по плательщику
     * @return array
     */
    public function getPayerData(): array
    {
        $payer = new Payer();
        $payerData = $payer->get();
        return $payerData;
    }

    /**Получить данные по получателю
     * @return array
     */
    public function getPayeeData(): array
    {
        $payee = new Payee();
        $payeeData = $payee->get();
        return $payeeData;
    }

    /**Предоставить данные по начислению
     * @return array
     */
    public function getImportedChargeData(array $elementIdList = null, int $limit = 1000, int $offset = 0): array
    {
        $iblockId = Helper::getIblockIdByCode(self::IBLOCK_CODE);
        $iblockProps = Helper::getIblockProperties($iblockId);
        $columns = array_keys($iblockProps);
        $iblockPropsValues = Helper::getPropValues($iblockId, $elementIdList, $columns);

        $select = ['ID', 'NAME', 'IBLOCK_ID', 'SORT'];

        $dbItemsCharge = \Bitrix\Iblock\ElementTable::getList([
            'select' => $select,
            'filter' => ['IBLOCK_ID' => $iblockId, 'ID' => $elementIdList],
            'limit' => $limit,
            'offset' => $offset,
            'count_total' => 1
        ]);


        $items = [];
        while($item = $dbItemsCharge->fetch()){
            $items[$item['ID']] = $item;
        }

        foreach ($iblockPropsValues as $elementId => $propItem) {
            foreach ($propItem as $propCode => $propValue) {
                $items[$elementId][$propCode] = $propValue;
            }
        }

        return $items;
    }

    /**
     * @OA\Get(
     *   tags={"Charge"},
     *   path="/charge/charge/",
     *   summary="Получить начисления",
     *   @OA\Parameter(
     *     name="id",
     *     in="query",
     *     required=false,
     *     description="ID начисления",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="supplierbillid",
     *     in="query",
     *     required=false,
     *     description="УИН",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function get(array $filter, int $limit = 1000, int $offset = 0, array $sort = [], bool $isFromGrid = false)
    {
        $iblockId = Helper::getIblockIdByCode(self::IBLOCK_CODE);
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
            'filter' => array_merge(['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'IBLOCK_SECTION_ID' => false], $filter),
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
            $items[] = $item;
        }
        $userIdList = array_unique(array_column($items,'CREATED_BY'));
        $authorInfo = Helper::getUserInfo($userIdList);
        foreach($items as &$item){
			$item['CREATED_BY_ID'] = $item['CREATED_BY'];
            $item['CREATED_BY'] = $authorInfo[$item['CREATED_BY']];
        }

        $schemaName = 'charge';

        if (!$isFromGrid) {
            if (isset($schemaName)) {
                self::prepareElements($items, $schemaName);

                //Добавляем вывод статуса для элемента инфоблока
                if (!empty($filter["ID"])) {
                    $docId = ['iblock', 'CIBlockDocument', $filter["ID"]];
                    $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument($docId);
                    $statusList = [];
                    if ($workflowIds) {
                        $i = 0;
                        foreach ($workflowIds as $workflowId) {
                            $stages = \CBPDocument::GetDocumentState($docId, $workflowId);
                            $statusList[$i]['workflowId'] = $workflowId;
                            $statusList[$i]['statusName'] = $stages[$workflowId]['STATE_TITLE'];
                            $actions = $stages[$workflowId]['STATE_PARAMETERS'];
                            $commands = null;
                            foreach ($actions as $actionItem) {
                                $commands[] = $actionItem['TITLE'];
                            }
                            $statusList[$i]['actions'] = $commands;
                            $i++;
                        }
                    }
                    $item['statusList'] = $statusList;
                }

                $payerStatusValues = self::getFieldValues($items, 'payerStatus');
                $paymentBasisValues = self::getFieldValues($items, 'paymentBasis');
                //Дополнительно запрашиваем данные по полям Статус плательщика и Основание платежа
                $payerStatuses = HlBlock::getHlItems(
                    'gisgmp_payerinfo',
                    ['UF_PAYERINFO_DESC', 'UF_XML_ID', 'ID'],
                    ['UF_XML_ID' => $payerStatusValues]
                );
                foreach ($payerStatuses as &$payerStatusItem) {
                    $payerStatusItem['ID'] = (int)$payerStatusItem['ID'];
                }

                $payerStatuses = self::prepareHlFields($payerStatuses, 'payer_status');
                $paymentBasis = HlBlock::getHlItems(
                    'gisgmp_paymentbasis',
                    ['UF_PAYMENTBASIS_DESC', 'UF_XML_ID', 'ID', 'UF_PAYMENTBASIS_CODE'],
                    ['UF_XML_ID' => $paymentBasisValues]
                );
                foreach ($paymentBasis as &$paymentBasisItem) {
                    $paymentBasisItem['ID'] = (int)$paymentBasisItem['ID'];
                }

                $paymentBasis = self::prepareHlFields($paymentBasis, 'payment_base');

                foreach ($items as &$item) {
                    if (!empty($item['payerStatus'])) {
                        $curPayerStatus = $item['payerStatus'];
                        $payerStatusIndex = self::findIndex($payerStatuses, $curPayerStatus, 'payer_status');
                        $item['payerStatus'] = $payerStatuses[$payerStatusIndex];
                        $item['budgetIndexStatus'] = $item['payerStatus'];
                    }

                    if (!empty($item['paymentBasis'])) {
                        $curPaymentBasis = $item['paymentBasis'];
                        $paymentBasisIndex = self::findIndex($paymentBasis, $curPaymentBasis, 'payment_base');
                        $item['paymentBasis'] = $paymentBasis[$paymentBasisIndex];
                        $item['budgetIndexPaytReason'] = $item['paymentBasis'];
                    }

                }

            }
        }
        
        $this->items = $items;
    }

    public static function findIndex(array $data, string $searchValue, string $schemaName): int
    {
        $index = -1;
        $schema = array_flip(RestHelper\Options::getMapFromOptions('schema_' . $schemaName));
        foreach ($data as $key => $dataItem) {
            if ($dataItem[$schema['UF_XML_ID']] == $searchValue)
                $index = $key;
        }
        return $index;
    }

    public static function getFieldValues(array $items, string $fieldName): array
    {
        $payerStatusValues = [];
        foreach($items as $item){
            if (!in_array($item[$fieldName], $payerStatusValues))
                $payerStatusValues[] = $item[$fieldName];
        }
        return $payerStatusValues;
    }

    /**
     * @OA\Post(
     *   tags={"Charge"},
     *   path="/charge/charge/",
     *   summary="Добавить начисление",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(
     *           property="chargename",
     *           type="string",
     *           example="Новое начисление",
     *           description="Название элемента инфоблока"
     *         ),
     *         @OA\Property(
     *           property="importedChargeOriginatorId",
     *           type="string",
     *           example="3eb5fa",
     *           description="УРН сформировавшего запрос"
     *         ),
     *         @OA\Property(
     *           property="importedChargeBillDate",
     *           type="date",
     *           example="01.05.2024 14:05:00",
     *           description="Дата начисления"
     *         ),
     *         @OA\Property(
     *           property="importedChargeTotalAmount",
     *           type="double",
     *           example="5000.07",
     *           description="Сумма начисления"
     *         ),
     *         @OA\Property(
     *           property="importedChargePurpose",
     *           type="string",
     *           example="Плата за предоставление сведений из Единого государственного реестра недвижимости",
     *           description="Назначение платежа"
     *         ),
     *         @OA\Property(
     *           property="importedChargeKbk",
     *           type="string",
     *           example="32111301031016000130",
     *           description="КБК"
     *         ),
     *         @OA\Property(
     *           property="importedChargeOktmo",
     *           type="string",
     *           example="45348000",
     *           description="ОКТМО"
     *         ),
     *         @OA\Property(
     *           property="payeeName",
     *           type="string",
     *           example="ФГБУ «ФКП Росреестра» по г Москва",
     *           description="Получатель - имя"
     *         ),
     *         @OA\Property(
     *           property="payeeInn",
     *           type="string",
     *           example="7705401341",
     *           description="Получатель - ИНН"
     *         ),
     *         @OA\Property(
     *           property="payeeKpp",
     *           type="string",
     *           example="770542151",
     *           description="Получатель - КПП"
     *         ),
     *         @OA\Property(
     *           property="payeeOgrn",
     *           type="string",
     *           example="7723819340452",
     *           description="Получатель - ОГРН"
     *         ),
     *         @OA\Property(
     *           property="bankName",
     *           type="string",
     *           example="ВТБ",
     *           description="Организация, принявшая платеж - Банк - Наименование"
     *         ),
     *         @OA\Property(
     *           property="bankBik",
     *           type="string",
     *           example="024501901",
     *           description="Организация, принявшая платеж - Банк - БИК"
     *         ),
     *         @OA\Property(
     *           property="bankCorrespondentBankAccount",
     *           type="string",
     *           example="40102810045370000002",
     *           description="Организация, принявшая платеж - Банк - Кор. счет"
     *         ),
     *         @OA\Property(
     *           property="payerPayerIdentifier",
     *           type="string",
     *           example="1220000000007712579832",
     *           description="Плательщик - идентификатор"
     *         ),
     *         @OA\Property(
     *           property="payerPayerName",
     *           type="string",
     *           example="Тестовый плательщик",
     *           description="Плательщик - наименование"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexStatus",
     *           type="number",
     *           example="08",
     *           description="Доп реквизиты платежа - Статус"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexPaytReason",
     *           type="number",
     *           example="1",
     *           description="Доп реквизиты платежа - Назначение"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexTaxPeriod",
     *           type="string",
     *           example="0",
     *           description="Доп реквизиты платежа - Налоговый период"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexTaxDocNumber",
     *           type="string",
     *           example="0",
     *           description="Доп реквизиты платежа - Номер документа"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexTaxDocDate",
     *           type="string",
     *           example="09.05.2024",
     *           description="Доп реквизиты платежа - Дата документа"
     *         ),
     *         @OA\Property(
     *           property="orgAccountAccountNumber",
     *           type="string",
     *           example="42354467",
     *           description="Организация, принявшая платеж - номер счета"
     *         ),
     *         @OA\Property(
     *           property="importProtocolEntityID",
     *           type="string",
     *           example="7575",
     *           description="(Ответ) ImportProtocol_entityID"
     *         ),
     *         @OA\Property(
     *           property="importProtocolCode",
     *           type="string",
     *           example="21345",
     *           description="(Ответ) ImportProtocol_code"
     *         ),
     *         @OA\Property(
     *           property="importProtocolDescription",
     *           type="string",
     *           example="Описание",
     *           description="(Ответ) ImportProtocol_description"
     *         ),
     *         @OA\Property(
     *           property="rqID",
     *           type="string",
     *           example="646446gdgery47",
     *           description="(Ответ) RqID"
     *         ),
     *         @OA\Property(
     *           property="rqName",
     *           type="string",
     *           example="Тестовое наименование начисления",
     *           description="Наименование начисления"
     *         ),
     *         @OA\Property(
     *           property="payerType",
     *           type="string",
     *           example="Тестовый тип плательщика",
     *           description="Тип плательщика"
     *         ),
     *         @OA\Property(
     *           property="relevantUntil",
     *           type="date",
     *           example="05.05.2024",
     *           description="Дополнительно - актуально до"
     *         ),
     *         @OA\Property(
     *           property="clarifyDate",
     *           type="date",
     *           example="07.05.2024",
     *           description="Дополнительно - дата уточнения"
     *         ),
     *         @OA\Property(
     *           property="changeBasis",
     *           type="string",
     *           example="Тестовое основание изменения",
     *           description="Основание изменения"
     *         ),
     *         @OA\Property(
     *           property="docBasisNumber",
     *           type="string",
     *           example="134567843",
     *           description="Дополнительно - номер документа-основания"
     *         ),
     *         @OA\Property(
     *           property="orgName",
     *           type="string",
     *           example="ООО Рога и копыта",
     *           description="Вкладка организация - Наименование организации"
     *         ),
     *         @OA\Property(
     *           property="orgInn",
     *           type="string",
     *           example="1234676",
     *           description="Вкладка организация - ИНН"
     *         ),
     *         @OA\Property(
     *           property="orgKpp",
     *           type="string",
     *           example="987654",
     *           description="Вкладка организация - КПП"
     *         ),
     *         @OA\Property(
     *           property="orgOgrn",
     *           type="string",
     *           example="456789",
     *           description="Вкладка организация - ОГРН"
     *         ),
     *         @OA\Property(
     *           property="orgOktmo",
     *           type="string",
     *           example="784512",
     *           description="Вкладка организация - ОКТМО"
     *         ),
     *         @OA\Property(
     *           property="docAuthor",
     *           type="integer",
     *           example="58",
     *           description="Служебная информация - автор документа (ID пользователя)"
     *         ),
     *         @OA\Property(
     *           property="chargeAuthor",
     *           type="integer",
     *           example="15",
     *           description="Служебная информация - начисление выставил (ID пользователя)"
     *         ),
     *         @OA\Property(
     *           property="chargeEditor",
     *           type="integer",
     *           example="18",
     *           description="Служебная информация - начисление отредактировал (ID пользователя)"
     *         ),
     *         @OA\Property(
     *           property="accessGroup",
     *           type="string",
     *           example="Тестовая группа доступа",
     *           description="Служебная информация - группа доступа"
     *         ),
     *         @OA\Property(
     *           property="rqIdPackage",
     *           type="string",
     *           example="gdg123abcd",
     *           description="Служебная информация - идентификатор начисления в пакете"
     *         ),
     *         @OA\Property(
     *           property="externalSourceType",
     *           type="string",
     *           example="Тип внешнего источника",
     *           description="Тип внешнего источника"
     *         ),
     *         @OA\Property(
     *           property="caseNumber",
     *           type="string",
     *           example="55346746",
     *           description="Реестр начислений - номер дела или материалов"
     *         ),
     *         @OA\Property(
     *           property="itemCode",
     *           type="string",
     *           example="1324654",
     *           description="Реестр начислений - Код предмета исполнения"
     *         ),
     *         @OA\Property(
     *           property="placeViolation",
     *           type="string",
     *           example="Тестовое место",
     *           description="Реестр начислений - место нарушения"
     *         ),
     *         @OA\Property(
     *           property="debtorBirthday",
     *           type="date",
     *           example="01.02.2001",
     *           description="Реестр начислений - Дата рождения должника"
     *         ),
     *         @OA\Property(
     *           property="periodType",
     *           type="string",
     *           example="Тестовый тип",
     *           description="Реестр начислений - тип периода срока предьявления исполнительного документа"
     *         ),
     *         @OA\Property(
     *           property="placeOfHearing",
     *           type="string",
     *           example="Тестовое место",
     *           description="Реестр начислений - место рассмотрения дела"
     *         ),
     *         @OA\Property(
     *           property="effectiveDate",
     *           type="date",
     *           example="05.04.2022",
     *           description="Реестр начислений - дата вступления решения в законную силу"
     *         ),
     *         @OA\Property(
     *           property="executiveDocumentCompanyAddress",
     *           type="string",
     *           example="г.Москва",
     *           description="Реестр начислений - Адрес органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorAddressActual",
     *           type="string",
     *           example="г.Санкт-Петербург",
     *           description="Реестр начислений - Адрес должника фактический"
     *         ),
     *         @OA\Property(
     *           property="debtorType",
     *           type="string",
     *           example="тип1",
     *           description="Реестр начислений - Тип должника"
     *         ),
     *         @OA\Property(
     *           property="materialsLink",
     *           type="string",
     *           example="Ссылка",
     *           description="Реестр начислений - ссылка на фото/видео материалы фиксации нарушения"
     *         ),
     *         @OA\Property(
     *           property="OkvedCode",
     *           type="string",
     *           example="534567564",
     *           description="Реестр начислений - код по ОКВЭД"
     *         ),
     *         @OA\Property(
     *           property="subjectExecution",
     *           type="string",
     *           example="Предмет",
     *           description="Реестр начислений - предмет исполнения"
     *         ),
     *         @OA\Property(
     *           property="violationDatetime",
     *           type="datetime",
     *           example="07.08.2010 13:15",
     *           description="Реестр начислений - дата и время нарушения"
     *         ),
     *         @OA\Property(
     *           property="submissionDeadline",
     *           type="date",
     *           example="07.08.2010",
     *           description="Реестр начислений - Срок предъявления исполнительного документа к исполнению"
     *         ),
     *         @OA\Property(
     *           property="decisionDate",
     *           type="date",
     *           example="07.08.2010",
     *           description="Реестр начислений - Дата принятия решения по делу"
     *         ),
     *         @OA\Property(
     *           property="issuingCompanyCode",
     *           type="string",
     *           example="5566",
     *           description="Реестр начислений - Код подразделения органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorAddress",
     *           type="string",
     *           example="г.Москва, ул.Ленина, д.12",
     *           description="Реестр начислений - Адрес должника"
     *         ),
     *         @OA\Property(
     *           property="ExecutiveDocumentCompanySubdivision",
     *           type="string",
     *           example="г.Москва, ул.Ленина, д.12",
     *           description="Реестр начислений - Наименование подразделения уполномоченного органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorRegistryAddress",
     *           type="string",
     *           example="г.Москва, ул.Ленина, д.12",
     *           description="Реестр начислений - Место регистрации должника - индивидуального предпринимателя"
     *         ),
     *         @OA\Property(
     *           property="positionCode",
     *           type="string",
     *           example="5678",
     *           description="Реестр начислений - Код должности лица, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorHomeland",
     *           type="string",
     *           example="г.Самара",
     *           description="Реестр начислений - Место рождения должника"
     *         ),
     *         @OA\Property(
     *           property="executiveDocumentNumber",
     *           type="string",
     *           example="54346",
     *           description="Реестр начислений - Номер исполнительного документа, присвоенный органом, выдавшим исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="classifierCode",
     *           type="string",
     *           example="5353",
     *           description="Реестр начислений - код по Общероссийскому классификатору органов государственной власти и управления органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="execDocDateIssue",
     *           type="date",
     *           example="01.02.2018",
     *           description="Реестр начислений - Дата выдачи исполнительного документа"
     *         ),
     *         @OA\Property(
     *           property="execDocUserPosition",
     *           type="string",
     *           example="Должность",
     *           description="Реестр начислений - Должность лица, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="subdivisionData",
     *           type="string",
     *           example="Данные",
     *           description="Реестр начислений - Данные о подразделении, вынесшем постановление"
     *         ),
     *         @OA\Property(
     *           property="fsspNotifyDate",
     *           type="date",
     *           example="05.05.2024",
     *           description="Реестр начислений - Дата уведомления ФССП России о неуплате штрафа в установленный законом"
     *         ),
     *         @OA\Property(
     *           property="execDocViewCode",
     *           type="string",
     *           example="54bce",
     *           description="Реестр начислений - Код вида исполнительного документа"
     *         ),
     *         @OA\Property(
     *           property="violationSign",
     *           type="string",
     *           example="gdgdgd",
     *           description="Реестр начислений - признак административного правонарушения"
     *         ),
     *         @OA\Property(
     *           property="debtorGender",
     *           type="string",
     *           example="М",
     *           description="Реестр начислений - пол должника"
     *         ),
     *         @OA\Property(
     *           property="violationArticle",
     *           type="string",
     *           example="342",
     *           description="Реестр начислений - статья нарушения"
     *         ),
     *         @OA\Property(
     *           property="claimantAddress",
     *           type="string",
     *           example="г. Екатеринбург",
     *           description="Реестр начислений - адрес взыскателя"
     *         ),
     *         @OA\Property(
     *           property="physicalDocId",
     *           type="number",
     *           example="1",
     *           description="Физ лицо - номер удостоверяющего документа из справочника"
     *         ),
     *         @OA\Property(
     *           property="physicalDocNumber",
     *           type="number",
     *           example="1234567890",
     *           description="номер паспорта"
     *         ),
     *         @OA\Property(
     *           property="physicalCountry",
     *           type="number",
     *           example="2",
     *           description="Физ лицо - страна"
     *         ),
     *         @OA\Property(
     *           property="foreignEntityInn",
     *           type="string",
     *           example="12356578",
     *           description="Иностранное юр лицо - ИНН"
     *         ),
     *         @OA\Property(
     *           property="foreignEntityKio",
     *           type="string",
     *           example="987564",
     *           description="Иностранное юр лицо - КИО"
     *         ),
     *         @OA\Property(
     *           property="foreignEntityKpp",
     *           type="string",
     *           example="5671321",
     *           description="Иностранное юр лицо - КПП"
     *         ),
     *         @OA\Property(
     *           property="payerPayerIdentifier_2_0",
     *           type="string",
     *           example="425465",
     *           description="Плательщик - идентификатор (2.0)"
     *         ),
     *         @OA\Property(
     *           property="additionComment",
     *           type="string",
     *           example="",
     *           description="Дополнительно - комментарий"
     *         ),
     *          @OA\Property(
     *           property="taxCustomPayments",
     *           type="boolean",
     *           example="false",
     *           description="Проведение налоговых и таможенных платежей"
     *         ),
     *         @OA\Property(
     *           property="participantUrn",
     *           type="string",
     *           example="56746744",
     *           description="УРН участника"
     *         ),
     *         @OA\Property(
     *           property="foreignKey",
     *           type="string",
     *           example="328",
     *           description="Ключ во внешней системе"
     *         ),
     *         @OA\Property(
     *           property="loadedFromOutside",
     *           type="boolean",
     *           example="true",
     *           description="Загружено из внешней системы"
     *         ),
     *         @OA\Property(
     *           property="importDate",
     *           type="date",
     *           example="01.05.2024",
     *           description="Дата импорта"
     *         ),
     *         @OA\Property(
     *           property="importSuccessDate",
     *           type="date",
     *           example="05.05.2024",
     *           description="Дата последнего успешного импорта"
     *         ),
     *         @OA\Property(
     *           property="lawEntityInn",
     *           type="string",
     *           example="",
     *           description="Юридическое лицо - ИНН"
     *         ),
     *         @OA\Property(
     *           property="lawEntityKpp",
     *           type="string",
     *           example="",
     *           description="Юридическое лицо - КПП"
     *         ),
     *         @OA\Property(
     *           property="individualEmployerInn",
     *           type="string",
     *           example="",
     *           description="Индивидуальный предприниматель - ИНН"
     *         ),
     *         @OA\Property(
     *           property="chargeId",
     *           type="string",
     *           example="",
     *           description="ID начисления"
     *         ),
     *         @OA\Property(
     *           property="chargeSet",
     *           type="string",
     *           example="",
     *           description="Начисление выставил"
     *         ),
     *         @OA\Property(
     *           property="orgCode",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно - организация - код"
     *         ),
     *         @OA\Property(
     *           property="contractorCode",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно, контрагент - код"
     *         ),
     *         @OA\Property(
     *           property="contractorName",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно, контрагент - наименование"
     *         ),
     *         @OA\Property(
     *           property="additionOrgNaming",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно - организация - наименование"
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

        $props = self::prepareProps($params,'charge');

        //Тестовый комментарий
        //Создаём элемент инфоблока [ImportCharges] Начисления
        if (empty($props['NAME']))
            $props['NAME'] = $props['RqName'];
        $elementId = self::add($props, $props["NAME"]);

        //TODO:тут нужно прописать запуск бизнес-процесса, т.к. выяснилось, что автоматически
        //бизнес-процесс запускается только если вручную создать элемент инфоблока из админки
        //Если элемент создаётся с помощью API из кода, то почему то для созданного
        //элемента инфоблока бизнес-процесс не запускатся, хотя в настройках указан автозапуск

        $object = new ImportedCharge();
        $object->get(['ID'=>$elementId]);

        return $object->items;
    }

    public static function generateUIN(string $kbk, string $chargeData, int $elementId): string
    {
        $blockA = $kbk[0] . $kbk[1] . $kbk[2] . $kbk[0] . $kbk[1] . $kbk[2];//Например 725725
		//$blockB = self::generateUINBlockB();
		$blockB = self::generateUINBlockBCustom($chargeData, $elementId);
        $blockC = self::generateUINBlockC($blockA . $blockB);
        $uin = $blockA . $blockB . $blockC;

        return $uin;
    }

    public static function checkElementByUIN(string $supplierBillId): bool
    {
        $props = [];
        \CIBlockElement::GetPropertyValuesArray(
            $props,
            Helper::getIblockIdByCode(self::IBLOCK_CODE),
            ['PROPERTY_ImportedCharge_supplierBillID' => $supplierBillId],
            ['CODE' => ['ImportedCharge_supplierBillID']]
        );
        if(empty($props))
            return false;

        return true;
    }

	/**Новый метод генерации части УИН
     * @param string $chargeDate
     * @param int $elementId
     * @return string
     */
    public static function generateUINBlockBCustom(string $chargeDate, int $elementId): string
    {
        $dataInfo = explode('.', $chargeDate);
        $year = $dataInfo[2][2] . $dataInfo[2][3];
        $month = $dataInfo[1];
        $day = $dataInfo[0];
        $data = $year . $month . $day;

        $elementIdStr = (string)$elementId;
        if (strlen($elementIdStr) >= 7) {
            $elementIdStr = substr($elementIdStr, 0, 7);
        } else {
            $zeroCountToAdd = 7 - strlen($elementIdStr);//Количество нулей, которое нужно добавить перед ID элемента
            $zeroStringToAdd = '';
            for ($i = 0; $i < $zeroCountToAdd; $i++) {
                $zeroStringToAdd .= '0';
            }
            $elementIdStr = $zeroStringToAdd . $elementIdStr;
        }

        return $data . $elementIdStr;
    }

    public static function generateUINBlockB(): string
    {
        $value = mt_rand(10000000, 99999999);//генерируем произвольное число длиной 8 символов
        $valueStr = strval($value);
        $valueStr .= $valueStr;//Доводим длину до 16

        return $valueStr;
    }

    public static function generateUINBlockC(string $str): string
    {
        $numbersList = [];
        $weightListFirst = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $weightListSecond = [3, 4, 5, 6, 7, 8, 9, 10, 1, 2];
        $multiplication = [];
        $sum = 0;
        //19 символов
        for ($i = 0; $i < strlen($str); $i++) {
            $numbersList[$i]['number'] = (int)$str[$i];
            if ($i <= 9)
                $numbersList[$i]['weight'] = $weightListFirst[$i];
            else {
                $weightIndex = (int)strval($i)[1];
                $numbersList[$i]['weight'] = $weightListFirst[$weightIndex];
            }
            $multiplication[$i] = $numbersList[$i]['number'] * $numbersList[$i]['weight'];
        }

        foreach ($multiplication as $multiItem) {
            $sum += $multiItem;
        }

        $result = $sum % 11;
        if ($result >= 0 && $result <= 9) {
            return $result;
        } else {
            //Применяем вторую последовательность весов
            $multiplication = [];
            foreach ($numbersList as $key => &$numberListItem) {
                if ($key <= 9)
                    $numberListItem['weight'] = $weightListSecond[$key];
                else {
                    $weightIndex = (int)strval($key)[1];
                    $numberListItem['weight'] = $weightListSecond[$weightIndex];
                }
                $multiplication[] = $numberListItem['number'] * $numberListItem['weight'];

            }
            $sum = 0;
            foreach ($multiplication as $multiItem) {
                $sum += $multiItem;
            }
            $result = $sum % 11;
            if ($result >= 0 && $result <= 9)
                return $result;
            else
                return 0;
        }

    }

    public static function add(&$props, $name = ''){
        $el = new \CIBlockElement;
        //Пока есть элементы с таким УИН - будем генерировать новый УИН

        $payerStatusesList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PayerInfo::getElementsFromIblock([]);
        $payerStatusElementId = self::findElementIdByCode($props["BudgetIndex_status"], $payerStatusesList);
        $props["BudgetIndex_status"] = $payerStatusElementId;
        $props["ImportedCharge_totalAmount"] = (float)$props["ImportedCharge_totalAmount"];
        $props["Paid"] = 0.00;
        $props["Balance"] = $props["ImportedCharge_totalAmount"];
        $props["Gis_Gmp_Balance"] = $props["ImportedCharge_totalAmount"];
		$physicalLookup = \MyCompany\Rest\Controller\Dictionary\IdentityCard::get([]);
        $physicalLookupInfo = $physicalLookup->getData()['results'];
        foreach ($physicalLookupInfo as $physicalLookupInfoItem) {
            if ($physicalLookupInfoItem['id'] == $props['physicalDocId']) {
                $props['Physical_docName'] = $physicalLookupInfoItem['slug'];
            }
        }
        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(ImportedCharge::IBLOCK_CODE),
            'PROPERTY_VALUES' => $props,
            'NAME' => $name ? $name : $props['RqID'],
            'ACTIVE' => 'Y'
        ];

        if($elementId = $el->Add($fields)) {
            //для нового элемента инфоблока нужно запустить бизнес-процесс
            $errors = [];

			do {
                $uin = self::generateUIN($props["ImportedCharge_kbk"], $props["ImportedCharge_billDate"], $elementId );
                //Теперь надо проверить, нет ли в нфоблоке элементов с таким УИН
                $isElementExist = self::checkElementByUIN($uin);
            } while ($isElementExist);

            //$props['ImportedCharge_supplierBillID'] = $uin;
            \CIBlockElement::SetPropertyValuesEx($elementId, false, ['ImportedCharge_supplierBillID' => $uin]);

            $workflowTemplateId = self::getWorkflowTemplateIdByTemplateName(self::WORKFLOW_TEMPLATE_NAME);

            $wfId = \CBPDocument::StartWorkflow(
                $workflowTemplateId,
                ["iblock", "CIBlockDocument", $elementId],
                [],
                $errors
            );
            if (count($errors) > 0) {
                Response::createError('Для элемента ', $elementId . ' не удалось запустить бизнес-процесс');
            }

            //Печатная форма
            //self::buildPDF($elementId, $fields);

            return $elementId;
        }
        else
            Response::createError(
                'Ошибка добавления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );
    }

    public static function buildPDF(int $chargeId, array $chargeData)
    {
        $text = '';
        $serviceData = 'ST0012';

        $requiredData = 'Name=Государственная корпорация по атомной энергии "MyCompany"|PersonalAcc=000000000000|BankName=Операционный департамент банка России/Межрегиональное операционое УФК г.Москва|BIC=---------|CorrespAcc=---------';


        $additionRequisites = 'Sum=' . $chargeData["PROPERTY_VALUES"]["ImportedCharge_totalAmount"] . '|PayeeInn=7706413348';

        $text .= $serviceData . $requiredData . $additionRequisites;

        \QRcode::png($text, $_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . 'charge_' . $chargeId . '.png');

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        $htmlFileName = 'template_portrait_editable.html';


        $html = self::editCustomHTML($_SERVER["DOCUMENT_ROOT"] . '/upload/pdf_templates/' . $htmlFileName, $chargeData["PROPERTY_VALUES"], $chargeId);
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

    public static function editHTML(string $filename): string
    {
        $mode = 'r+';
        $file = fopen($filename, $mode);
        $inputHTML = file_get_contents($filename);
        $resultHTML = $inputHTML;

        $styleEndTag = '</style>';
        $insertedHTML= '.no-top-bottom {
    border-bottom-color: transparent !important;
    border-top-color: transparent !important;
}

.no-bottom {
    border-bottom-color: transparent !important;
}

.no-top {
    border-top-color: transparent !important;
}

.no-left{
    border-left-color: transparent !important;
}

.no-right{
    border-right-color: transparent !important;
}

.no-left-right{
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(1) td:nth-child(2){
    border-bottom-color: transparent !important;
}

tr:nth-child(1) td:nth-child(3){
    border-bottom-color: transparent !important;
}

tr:nth-child(1) td:nth-child(4){
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(1) td:nth-child(5){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(1) td:nth-child(6){
    border-left-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(1) td:nth-child(1){
    border-bottom-color: transparent !important;
}

tr:nth-child(2) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(2) td:nth-child(2){
    border-top-color: transparent !important;
}

tr:nth-child(2) td:nth-child(3){
    border-top-color: transparent !important;
}

tr:nth-child(2) td:nth-child(4){
    border-right-color: transparent !important;
    border-top-color: transparent !important;
}

tr:nth-child(2) td:nth-child(5){
    border-top-color: transparent !important;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(2) td:nth-child(6){
    border-top-color: transparent !important;
    border-left-color: transparent !important;
}

tr:nth-child(3) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(3) td:nth-child(2){
    border-bottom-color: transparent !important;
    vertical-align: bottom;
}



tr:nth-child(3) td:nth-child(5){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(3) td:nth-child(6){
    border-left-color: transparent !important;
}

tr:nth-child(4) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(4) td:nth-child(2){
    vertical-align: top;
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}


tr:nth-child(4) td:nth-child(5){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(4) td:nth-child(6){
    border-left-color: transparent !important;
}

tr:nth-child(5) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(5) td:nth-child(2){
    border-top-color: transparent !important;
}


tr:nth-child(5) td:nth-child(4){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(5) td:nth-child(5){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(5) td:nth-child(6){
    border-left-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(5) td:nth-child(7){
    border-bottom-color: transparent !important;
}

tr:nth-child(6) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}



tr:nth-child(6) td:nth-child(2)
{
    border-bottom-color: transparent !important;
}

tr:nth-child(6) td:nth-child(3){
    word-wrap: break-word;
}

tr:nth-child(7) td:nth-child(2){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}


tr:nth-child(6) td:nth-child(4){
    border-top-color: transparent !important;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(6) td:nth-child(5){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(6) td:nth-child(6){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
    border-left-color: transparent !important;
}

tr:nth-child(7) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}



tr:nth-child(7) td:nth-child(3){
    border-right-color: transparent !important;
}

tr:nth-child(7) td:nth-child(4){
    border-left-color: transparent !important;
}

tr:nth-child(7) td:nth-child(5){
    border-top-color: transparent !important;
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(7) td:nth-child(6){
    border-top-color: transparent !important;
    border-left-color: transparent !important;
}

tr:nth-child(8) td:nth-child(1){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(8) td:nth-child(2){
    border-bottom-color: transparent !important;
    border-top-color: transparent !important;
}

tr:nth-child(8) td:nth-child(3){
    border-right-color: transparent !important;
}

tr:nth-child(8) td:nth-child(4){
    border-left-color: transparent !important;
}

tr:nth-child(8) td:nth-child(5){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(8) td:nth-child(6){
    border-left-color: transparent !important;
    border-right-color: transparent !important;
}

tr:nth-child(8) td:nth-child(1), 
tr:nth-child(9) td:nth-child(1),
tr:nth-child(10) td:nth-child(1),
tr:nth-child(11) td:nth-child(1),
tr:nth-child(12) td:nth-child(1),
tr:nth-child(13) td:nth-child(1),
tr:nth-child(14) td:nth-child(1),
tr:nth-child(15) td:nth-child(1),
tr:nth-child(16) td:nth-child(1),
tr:nth-child(17) td:nth-child(1),

tr:nth-child(19) td:nth-child(1),
tr:nth-child(20) td:nth-child(1),
tr:nth-child(21) td:nth-child(1)
{
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(18) td:nth-child(1){
    border-top-color: transparent !important;
}

tr:nth-child(9) td:nth-child(2){
    border-top: transparent;
}

tr:nth-child(15) td:nth-child(2){
    border-top: transparent;
}

tr:nth-child(10) td:nth-child(2)
{
    border-bottom-color: transparent !important;
    vertical-align: bottom;
}

tr:nth-child(11) td:nth-child(2){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
    vertical-align: top;
}

tr:nth-child(12) td:nth-child(2)
{
    border-top-color: transparent !important;
    border-bottom: transparent;
}

tr:nth-child(13) td:nth-child(2),
tr:nth-child(14) td:nth-child(2)
{
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(16) td:nth-child(2){
    border-bottom-color: transparent !important;
}

tr:nth-child(17) td:nth-child(2){
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(19) td:nth-child(3)
{
    border-left-color: transparent !important;
    border-bottom-color: transparent !important;
    border-top: transparent;
}

tr:nth-child(18) td:nth-child(2){
    border-top: transparent;
}

tr:nth-child(19) td:nth-child(2)
{
    border-top-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(20) td:nth-child(2),
tr:nth-child(21) td:nth-child(2)
{
    border-right-color: transparent !important;
    border-bottom-color: transparent !important;
}

tr:nth-child(20) td:nth-child(2),
tr:nth-child(21) td:nth-child(2){
    border-top-color: transparent !important;
}

tr:nth-child(19) td:nth-child(3),
tr:nth-child(20) td:nth-child(3),
tr:nth-child(21) td:nth-child(3)
{
    border-bottom-color: transparent !important;
    border-left-color: transparent !important;
}

tr:nth-child(20) td:nth-child(3),
tr:nth-child(21) td:nth-child(3){
    border-top: transparent;
}

table td, table td p{
    font-size: 11px;
    padding:0;
    margin: 0;
}

table tr td p img{
    width: 100px !important;
    height: 100px !important;
}
</style>';

        $bodyTagStart = '<body>';
        $bodyTagEnd = '</body>';

        //$bodyStartPosition = strpos();

        $pos = strpos($inputHTML, $styleEndTag);
        if ($pos !== false) {
            //Вставляем новые стили в HTML файл
            $resultHTML = (string) str_replace($styleEndTag, $insertedHTML, $inputHTML);
        }

        fseek($file, 0); // Возвращаем указатель на начало файла
        fwrite($file, $resultHTML);

        return $resultHTML;
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
        $resultHTML = str_replace('#CHARGE_ID#', $data['ImportedCharge_supplierBillID'], $resultHTML);
        $resultHTML = str_replace('#PAYER_ID#', $data['Payer_payerIdentifier'], $resultHTML);
        $resultHTML = str_replace('#PAYER_INN#', $data['Law_entity_inn'], $resultHTML);
        $resultHTML = str_replace('#PAYER_KPP#', $data['Law_entity_kpp'], $resultHTML);
        $resultHTML = str_replace('#COMPANY_NAME#', $data['Payer_payerName'], $resultHTML);
        $resultHTML = str_replace('#RECEIVER_NAME#', 'Вставить значение', $resultHTML);
        $resultHTML = str_replace('#CORR_NUMBER#', $data['Bank_correspondentBankAccount'], $resultHTML);
        $resultHTML = str_replace('#RECEIVER_BIC#', $data['Bank_bik'], $resultHTML);
        $resultHTML = str_replace('#PAYMENT_TARGET#', $data['RqName'], $resultHTML);
        $resultHTML = str_replace('#PAYER_NAME#', $data['RqName'], $resultHTML);

        $resultHTML = str_replace('#ADMIN_ACCOUNT_NUMBER#', $data['OrgAccount_accountNumber'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_INN#', $data['Payee_inn'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_KPP#', $data['Payee_kpp'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_OKTMO#', $data['Org_oktmo'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_KBK#', $data['ImportedCharge_kbk'], $resultHTML);

        $payerStatusCode = Helper::getIblockElementCodeById('gis-gmp-payer-status', $data["BudgetIndex_status"]);
        $resultHTML = str_replace('#ADMIN_101#', $payerStatusCode, $resultHTML);
        $resultHTML = str_replace('#ADMIN_106#', $data['Payment_basis_xml'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_108#', $data['BudgetIndex_taxDocNumber'], $resultHTML);
        $resultHTML = str_replace('#ADMIN_109#', $data['BudgetIndex_taxDocDate'], $resultHTML);

        $resultHTML = str_replace('#SUMM#', $data['ImportedCharge_totalAmount'], $resultHTML);
        $resultHTML = str_replace('#CHARGE_DATE#', $data['ImportedCharge_billDate'], $resultHTML);

        fseek($file, 0); // Возвращаем указатель на начало файла
        fwrite($file, $resultHTML);

        return $resultHTML;
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

    public static function getPayerStatusCodeById(int $elementId): string
    {
        $code = '';


        return $code;
    }

    public static function prepareProps(array $input, string $schemaName): array
    {
        $preparedProps = [];
        $iblockPropTypes = Helper::getIblockProperties(Helper::getIblockIdByCode(self::IBLOCK_CODE));
        $schema = RestHelper\Options::getMapFromOptions('schema_' . $schemaName);
        $propsListItems = [];
        foreach ($input as $propItemKey => $propItem) {
            $propertyType = $iblockPropTypes[$schema[$propItemKey]]['PROPERTY_TYPE'];
            if ($propertyType != 'L')
                $preparedProps[$schema[$propItemKey]] = $propItem;
            else {
                $propsListItems[$schema[$propItemKey]] = $propItem;
            }
        }

        $propsCodes = array_keys($propsListItems);
        $enumProps = Helper::getIblockPropertiesByPropCode(Helper::getIblockIdByCode(self::IBLOCK_CODE), $propsCodes);
        foreach ($propsListItems as $propCode => $propListItem) {
            foreach ($enumProps[$propCode]['ENUM'] as $enumValueId => $enumValueItem) {
                $enumXmlId = $enumValueItem['XML_ID'];
                $requestPropValue = $propsListItems[$propCode] ? 'true' : 'false';
                if ($enumXmlId === $requestPropValue) {
                    $preparedProps[$propCode] = $enumValueId;
                }
            }
        }

        //В physicalDocId нужно сохранять UF_XML_ID элемента справочника
		$preparedProps['physicalDocId'] = $input['physicalDocId'];
        if ($preparedProps["BudgetIndex_taxDocDate"] == 0) {
            $preparedProps["BudgetIndex_taxDocDate"] = null;
        }

        return $preparedProps;
    }

    public static function prepareHlFields(array $items, string $schemaName): array
    {
        $preparedProps = [];
        $schema = array_flip(RestHelper\Options::getMapFromOptions('schema_' . $schemaName));
        $i = 0;
        foreach ($items as $item) {
            foreach ($item as $fieldKey => $fieldCodeValue) {
                $preparedProps[$i][$schema[$fieldKey]] = $fieldCodeValue;
            }
            $i++;
        }

        return $preparedProps;
    }

    public static function prepareElements(array &$items, string $schemaName)
    {
        $sumProps = ['Balance', 'Paid', 'Gis_Gmp_Balance'];
        $iblockPropTypes = Helper::getIblockProperties(Helper::getIblockIdByCode(self::IBLOCK_CODE));
        $schema = array_flip(\MyCompany\Rest\Helper\Options::getMapFromOptions('schema_' . $schemaName));
        $propertyEnumsList = [];
        foreach ($items as $itemKey => &$item) {
            foreach ($item as $propCode => $property) {
                $curProperty = str_replace('PROPERTY_', '', $propCode);
                if (array_key_exists($curProperty, $schema)) {
                    switch($iblockPropTypes[$curProperty]['PROPERTY_TYPE']){
                        case "L":
                            $propertyEnumsList[$itemKey][$curProperty] = $property;
                            //$item[$schema[$curProperty]] = $property;
                            break;

                        case "S":
                            $item[$schema[$curProperty]] = $property;
                            if ($iblockPropTypes[$curProperty]["USER_TYPE"] == "Date") {
                                $item[$schema[$curProperty]] = RestHelper::formatDate($property);//Преобразовать дату к формату 2024-08-20 00:00:00 (ISO)
                            }
                            break;

                        default:
                            $item[$schema[$curProperty]] = $property;
                            break;
                    }
                }

                if (in_array($curProperty, $sumProps)) {
                    $item[$schema[$curProperty]] = substr($property, 0, -2);
                }

                unset($item[$propCode]);
            }
        }

        //Списочные значение нужно преобразовать в читаемые
        if(is_array($propertyEnumsList[0])){
            $propsCodes = array_keys($propertyEnumsList[0]);
            $enumProps = Helper::getIblockPropertiesByPropCode(Helper::getIblockIdByCode(self::IBLOCK_CODE), $propsCodes);
            foreach ($propertyEnumsList as $elementIndex => $propList) {
                foreach ($propList as $propCode => $propValue) {
                    $items[$elementIndex][$schema[$propCode]] = null;
                    switch ($enumProps[$propCode]['ENUM'][$propValue]['XML_ID']) {
                        case 'true':
                            $items[$elementIndex][$schema[$propCode]] = true;
                            break;
                        case 'false':
                            $items[$elementIndex][$schema[$propCode]] = false;
                            break;
                    }
                }
            }
        }


        //Статус плательщика
        $payerStatusIdList = array_column($items, $schema['BudgetIndex_status']);
        $payerStatusDataList = self::getPayerStatusData($payerStatusIdList);
        foreach ($items as &$item) {
            $statusElementId = $item[$schema['BudgetIndex_status']];
            $item[$schema['BudgetIndex_status']] = $payerStatusDataList[$statusElementId];
        }


        $keysToConvert = ['ID', 'id', 'ResponseId', 'RequestId', 'ImportedCharge_totalAmount', 'Payer_payerIdentifier'];
        foreach ($keysToConvert as $key) {
            if (array_key_exists($key, $schema))
                foreach ($items as &$item) {
                    if ($iblockPropTypes[$key]['PROPERTY_TYPE'] == 'N')
                        if(is_int($item[$schema[$key]]))
                            $item[$schema[$key]] = (int)$item[$schema[$key]];
                        else
                            $item[$schema[$key]] = (double)$item[$schema[$key]];
                }
        }


        $budgetIndexPaymentBaseList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PaymentBase::get([]);
        $paymentBaseXmlIdList = array_column($budgetIndexPaymentBaseList, 'slug');
        foreach ($items as &$item) {
            $xmlIdIndex = array_search($item["budgetIndexPaytReason"], $paymentBaseXmlIdList);
            if ($xmlIdIndex !== false) {
                $item['budgetIndexPaytReason'] = $budgetIndexPaymentBaseList[$xmlIdIndex];
            }
        }

        $physicalLookup = \MyCompany\Rest\Controller\Dictionary\IdentityCard::get([]);
        $physicalLookupInfo = $physicalLookup->getData()['results'];
        foreach ($items as &$item) {
            $item["physicalCountry"] = Countries::getByFilter(['ID' => $item["physicalCountry"]])[0];
            //$item["budgetIndexStatus"] = PayerInfo::getByFilter(['ID' => $item["budgetIndexStatus"]])[0];
            //unset($item["budgetIndexStatus"]["code"]);
            $item["budgetIndexPaytReason"] = PaymentBaseModel::getByFilter(['ID' => $item["budgetIndexPaytReason"]])[0];
            $executiveDocumentUser = IdentityCard::getByFilter(['ID' => $item["executiveDocumentUser"]]);
            if (!empty($executiveDocumentUser)) {
                $item["executiveDocumentUser"] = $executiveDocumentUser;
            }

            unset($item['orgName']);
            unset($item['orgInn']);
            unset($item['orgKpp']);
            unset($item['orgOgrn']);
            unset($item['orgOktmo']);
            unset($item['bankCorrespondentBankAccount']);
            unset($item['orgAccountAccountNumber']);
            unset($item['bankBik']);
            unset($item['bankName']);

            $item[$schema['ID']] = (int)$item[$schema['ID']];

            foreach ($physicalLookupInfo as $physicalLookupInfoItem) {
                if ($item['physicalDocName'] == $physicalLookupInfoItem['slug']) {
                    $item['physicalDocId'] = $physicalLookupInfoItem;
                }
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

    /**
     * @OA\POST(
     *   tags={"Charge"},
     *   path="/charge/charge/{id}/",
     *   summary="Обновить начисление",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     example="1901",
     *     description="id элемента",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(
     *           property="chargename",
     *           type="string",
     *           example="Обновленное название начисления",
     *           description="Новое название элемента инфоблока"
     *         ),
     *         @OA\Property(
     *           property="importedChargeOriginatorId",
     *           type="string",
     *           example="3eb5fa",
     *           description="УРН сформировавшего запрос"
     *         ),
     *         @OA\Property(
     *           property="importedChargeBillDate",
     *           type="date",
     *           example="01.05.2024 14:05:00",
     *           description="Дата начисления"
     *         ),
     *         @OA\Property(
     *           property="importedChargeTotalAmount",
     *           type="integer",
     *           example="500000",
     *           description="Сумма начисления"
     *         ),
     *         @OA\Property(
     *           property="importedChargePurpose",
     *           type="string",
     *           example="Плата за предоставление сведений из Единого государственного реестра недвижимости",
     *           description="Назначение платежа"
     *         ),
     *         @OA\Property(
     *           property="importedChargeKbk",
     *           type="string",
     *           example="32111301031016000130",
     *           description="КБК"
     *         ),
     *         @OA\Property(
     *           property="importedChargeOktmo",
     *           type="string",
     *           example="45348000",
     *           description="ОКТМО"
     *         ),
     *         @OA\Property(
     *           property="payeeName",
     *           type="string",
     *           example="ФГБУ «ФКП Росреестра» по г Москва",
     *           description="Получатель - имя"
     *         ),
     *         @OA\Property(
     *           property="payeeInn",
     *           type="string",
     *           example="7705401341",
     *           description="Получатель - ИНН"
     *         ),
     *         @OA\Property(
     *           property="payeeKpp",
     *           type="string",
     *           example="770542151",
     *           description="Получатель - КПП"
     *         ),
     *         @OA\Property(
     *           property="payeeOgrn",
     *           type="string",
     *           example="7723819340452",
     *           description="Получатель - ОГРН"
     *         ),
     *         @OA\Property(
     *           property="bankName",
     *           type="string",
     *           example="ВТБ",
     *           description="Организация, принявшая платеж - Банк - Наименование"
     *         ),
     *         @OA\Property(
     *           property="bankBik",
     *           type="string",
     *           example="024501901",
     *           description="Организация, принявшая платеж - Банк - БИК"
     *         ),
     *         @OA\Property(
     *           property="bankCorrespondentBankAccount",
     *           type="string",
     *           example="40102810045370000002",
     *           description="Организация, принявшая платеж - Банк - Кор. счет"
     *         ),
     *         @OA\Property(
     *           property="payerPayerIdentifier",
     *           type="string",
     *           example="1220000000007712579832",
     *           description="Плательщик - идентификатор"
     *         ),
     *         @OA\Property(
     *           property="payerPayerName",
     *           type="string",
     *           example="Тестовый плательщик",
     *           description="Плательщик - наименование"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexStatus",
     *           type="string",
     *           example="01",
     *           description="Доп реквизиты платежа - Статус"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexPaytReason",
     *           type="string",
     *           example="0",
     *           description="Доп реквизиты платежа - Назначение"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexTaxPeriod",
     *           type="string",
     *           example="0",
     *           description="Доп реквизиты платежа - Налоговый период"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexTaxDocNumber",
     *           type="string",
     *           example="0",
     *           description="Доп реквизиты платежа - Номер документа"
     *         ),
     *         @OA\Property(
     *           property="budgetIndexTaxDocDate",
     *           type="string",
     *           example="02.05.2024",
     *           description="Доп реквизиты платежа - Дата документа"
     *         ),
     *         @OA\Property(
     *           property="orgAccountAccountNumber",
     *           type="string",
     *           example="42354467",
     *           description="Организация, принявшая платеж - номер счета"
     *         ),
     *         @OA\Property(
     *           property="importProtocolEntityID",
     *           type="string",
     *           example="7575",
     *           description="(Ответ) ImportProtocol_entityID"
     *         ),
     *         @OA\Property(
     *           property="importProtocolCode",
     *           type="string",
     *           example="21345",
     *           description="(Ответ) ImportProtocol_code"
     *         ),
     *         @OA\Property(
     *           property="importProtocolDescription",
     *           type="string",
     *           example="Описание",
     *           description="(Ответ) ImportProtocol_description"
     *         ),
     *         @OA\Property(
     *           property="rqID",
     *           type="string",
     *           example="646446gdgery47",
     *           description="(Ответ) RqID"
     *         ),
     *         @OA\Property(
     *           property="rqName",
     *           type="string",
     *           example="Тестовое наименование начисления",
     *           description="Наименование начисления"
     *         ),
     *         @OA\Property(
     *           property="payerType",
     *           type="string",
     *           example="Тестовый тип плательщика",
     *           description="Тип плательщика"
     *         ),
     *         @OA\Property(
     *           property="relevantUntil",
     *           type="date",
     *           example="05.05.2024",
     *           description="Дополнительно - актуально до"
     *         ),
     *         @OA\Property(
     *           property="clarifyDate",
     *           type="date",
     *           example="07.05.2024",
     *           description="Дополнительно - дата уточнения"
     *         ),
     *         @OA\Property(
     *           property="changeBasis",
     *           type="string",
     *           example="Тестовое основание изменения",
     *           description="Основание изменения"
     *         ),
     *         @OA\Property(
     *           property="docBasisNumber",
     *           type="string",
     *           example="134567843",
     *           description="Дополнительно - номер документа-основания"
     *         ),
     *         @OA\Property(
     *           property="orgName",
     *           type="string",
     *           example="ООО Рога и копыта",
     *           description="Вкладка организация - Наименование организации"
     *         ),
     *         @OA\Property(
     *           property="orgInn",
     *           type="string",
     *           example="1234676",
     *           description="Вкладка организация - ИНН"
     *         ),
     *         @OA\Property(
     *           property="orgKpp",
     *           type="string",
     *           example="987654",
     *           description="Вкладка организация - КПП"
     *         ),
     *         @OA\Property(
     *           property="orgOgrn",
     *           type="string",
     *           example="456789",
     *           description="Вкладка организация - ОГРН"
     *         ),
     *         @OA\Property(
     *           property="orgOktmo",
     *           type="string",
     *           example="784512",
     *           description="Вкладка организация - ОКТМО"
     *         ),
     *         @OA\Property(
     *           property="docAuthor",
     *           type="integer",
     *           example="58",
     *           description="Служебная информация - автор документа (ID пользователя)"
     *         ),
     *         @OA\Property(
     *           property="chargeAuthor",
     *           type="integer",
     *           example="15",
     *           description="Служебная информация - начисление выставил (ID пользователя)"
     *         ),
     *         @OA\Property(
     *           property="chargeEditor",
     *           type="integer",
     *           example="18",
     *           description="Служебная информация - начисление отредактировал (ID пользователя)"
     *         ),
     *         @OA\Property(
     *           property="accessGroup",
     *           type="string",
     *           example="Тестовая группа доступа",
     *           description="Служебная информация - группа доступа"
     *         ),
     *         @OA\Property(
     *           property="rqIdPackage",
     *           type="string",
     *           example="gdg123abcd",
     *           description="Служебная информация - идентификатор начисления в пакете"
     *         ),
     *         @OA\Property(
     *           property="externalSourceType",
     *           type="string",
     *           example="Тип внешнего источника",
     *           description="Тип внешнего источника"
     *         ),
     *         @OA\Property(
     *           property="caseNumber",
     *           type="string",
     *           example="55346746",
     *           description="Реестр начислений - номер дела или материалов"
     *         ),
     *         @OA\Property(
     *           property="itemCode",
     *           type="string",
     *           example="1324654",
     *           description="Реестр начислений - Код предмета исполнения"
     *         ),
     *         @OA\Property(
     *           property="placeViolation",
     *           type="string",
     *           example="Тестовое место",
     *           description="Реестр начислений - место нарушения"
     *         ),
     *         @OA\Property(
     *           property="debtorBirthday",
     *           type="date",
     *           example="01.02.2001",
     *           description="Реестр начислений - Дата рождения должника"
     *         ),
     *         @OA\Property(
     *           property="periodType",
     *           type="string",
     *           example="Тестовый тип",
     *           description="Реестр начислений - тип периода срока предьявления исполнительного документа"
     *         ),
     *         @OA\Property(
     *           property="placeOfHearing",
     *           type="string",
     *           example="Тестовое место",
     *           description="Реестр начислений - место рассмотрения дела"
     *         ),
     *         @OA\Property(
     *           property="effectiveDate",
     *           type="date",
     *           example="05.04.2022",
     *           description="Реестр начислений - дата вступления решения в законную силу"
     *         ),
     *         @OA\Property(
     *           property="executiveDocumentUser",
     *           type="string",
     *           example="Иванов Иван Иванович",
     *           description="Реестр начислений - ФИО должностного лица, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="executiveDocumentCompanyAddress",
     *           type="string",
     *           example="г.Москва",
     *           description="Реестр начислений - Адрес органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorAddressActual",
     *           type="string",
     *           example="г.Санкт-Петербург",
     *           description="Реестр начислений - Адрес должника фактический"
     *         ),
     *         @OA\Property(
     *           property="debtorType",
     *           type="string",
     *           example="тип1",
     *           description="Реестр начислений - Тип должника"
     *         ),
     *         @OA\Property(
     *           property="materialsLink",
     *           type="string",
     *           example="Ссылка",
     *           description="Реестр начислений - ссылка на фото/видео материалы фиксации нарушения"
     *         ),
     *         @OA\Property(
     *           property="OkvedCode",
     *           type="string",
     *           example="534567564",
     *           description="Реестр начислений - код по ОКВЭД"
     *         ),
     *         @OA\Property(
     *           property="subjectExecution",
     *           type="string",
     *           example="Предмет",
     *           description="Реестр начислений - предмет исполнения"
     *         ),
     *         @OA\Property(
     *           property="violationDatetime",
     *           type="datetime",
     *           example="07.08.2010 13:15",
     *           description="Реестр начислений - дата и время нарушения"
     *         ),
     *         @OA\Property(
     *           property="submissionDeadline",
     *           type="date",
     *           example="07.08.2010",
     *           description="Реестр начислений - Срок предъявления исполнительного документа к исполнению"
     *         ),
     *         @OA\Property(
     *           property="decisionDate",
     *           type="date",
     *           example="07.08.2010",
     *           description="Реестр начислений - Дата принятия решения по делу"
     *         ),
     *         @OA\Property(
     *           property="issuingCompanyCode",
     *           type="string",
     *           example="5566",
     *           description="Реестр начислений - Код подразделения органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorAddress",
     *           type="string",
     *           example="г.Москва, ул.Ленина, д.12",
     *           description="Реестр начислений - Адрес должника"
     *         ),
     *         @OA\Property(
     *           property="ExecutiveDocumentCompanySubdivision",
     *           type="string",
     *           example="г.Москва, ул.Ленина, д.12",
     *           description="Реестр начислений - Наименование подразделения уполномоченного органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorRegistryAddress",
     *           type="string",
     *           example="г.Москва, ул.Ленина, д.12",
     *           description="Реестр начислений - Место регистрации должника - индивидуального предпринимателя"
     *         ),
     *         @OA\Property(
     *           property="positionCode",
     *           type="string",
     *           example="5678",
     *           description="Реестр начислений - Код должности лица, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="debtorHomeland",
     *           type="string",
     *           example="г.Самара",
     *           description="Реестр начислений - Место рождения должника"
     *         ),
     *         @OA\Property(
     *           property="executiveDocumentNumber",
     *           type="string",
     *           example="54346",
     *           description="Реестр начислений - Номер исполнительного документа, присвоенный органом, выдавшим исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="classifierCode",
     *           type="string",
     *           example="5353",
     *           description="Реестр начислений - код по Общероссийскому классификатору органов государственной власти и управления органа, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="execDocDateIssue",
     *           type="date",
     *           example="01.02.2018",
     *           description="Реестр начислений - Дата выдачи исполнительного документа"
     *         ),
     *         @OA\Property(
     *           property="execDocUserPosition",
     *           type="string",
     *           example="Должность",
     *           description="Реестр начислений - Должность лица, выдавшего исполнительный документ"
     *         ),
     *         @OA\Property(
     *           property="subdivisionData",
     *           type="string",
     *           example="Данные",
     *           description="Реестр начислений - Данные о подразделении, вынесшем постановление"
     *         ),
     *         @OA\Property(
     *           property="fsspNotifyDate",
     *           type="date",
     *           example="05.05.2024",
     *           description="Реестр начислений - Дата уведомления ФССП России о неуплате штрафа в установленный законом"
     *         ),
     *         @OA\Property(
     *           property="execDocViewCode",
     *           type="string",
     *           example="54bce",
     *           description="Реестр начислений - Код вида исполнительного документа"
     *         ),
     *         @OA\Property(
     *           property="violationSign",
     *           type="string",
     *           example="gdgdgd",
     *           description="Реестр начислений - признак административного правонарушения"
     *         ),
     *         @OA\Property(
     *           property="debtorGender",
     *           type="string",
     *           example="М",
     *           description="Реестр начислений - пол должника"
     *         ),
     *         @OA\Property(
     *           property="violationArticle",
     *           type="string",
     *           example="342",
     *           description="Реестр начислений - статья нарушения"
     *         ),
     *         @OA\Property(
     *           property="claimantAddress",
     *           type="string",
     *           example="г. Екатеринбург",
     *           description="Реестр начислений - адрес взыскателя"
     *         ),
     *         @OA\Property(
     *           property="physicalDocId",
     *           type="string",
     *           example="паспорт",
     *           description="Физ лицо - ID документа из справочника"
     *         ),
     *         @OA\Property(
     *           property="physicalCountry",
     *           type="string",
     *           example="РОССИЯ",
     *           description="Физ лицо - страна"
     *         ),
     *         @OA\Property(
     *           property="foreignEntityInn",
     *           type="string",
     *           example="12356578",
     *           description="Иностранное юр лицо - ИНН"
     *         ),
     *         @OA\Property(
     *           property="foreignEntityKio",
     *           type="string",
     *           example="987564",
     *           description="Иностранное юр лицо - КИО"
     *         ),
     *         @OA\Property(
     *           property="foreignEntityKpp",
     *           type="string",
     *           example="5671321",
     *           description="Иностранное юр лицо - КПП"
     *         ),
     *         @OA\Property(
     *           property="payerPayerIdentifier_2_0",
     *           type="string",
     *           example="425465",
     *           description="Плательщик - идентификатор (2.0)"
     *         ),
     *         @OA\Property(
     *           property="additionComment",
     *           type="string",
     *           example="",
     *           description="Дополнительно - комментарий"
     *         ),
     *          @OA\Property(
     *           property="taxCustomPayments",
     *           type="boolean",
     *           example="false",
     *           description="Проведение налоговых и таможенных платежей"
     *         ),
     *         @OA\Property(
     *           property="participantUrn",
     *           type="string",
     *           example="56746744",
     *           description="УРН участника"
     *         ),
     *         @OA\Property(
     *           property="foreignKey",
     *           type="string",
     *           example="328",
     *           description="Ключ во внешней системе"
     *         ),
     *         @OA\Property(
     *           property="loadedFromOutside",
     *           type="boolean",
     *           example="true",
     *           description="Загружено из внешней системы"
     *         ),
     *         @OA\Property(
     *           property="importDate",
     *           type="date",
     *           example="01.05.2024",
     *           description="Дата импорта"
     *         ),
     *         @OA\Property(
     *           property="importSuccessDate",
     *           type="date",
     *           example="05.05.2024",
     *           description="Дата последнего успешного импорта"
     *         ),
     *         @OA\Property(
     *           property="lawEntityInn",
     *           type="string",
     *           example="",
     *           description="Юридическое лицо - ИНН"
     *         ),
     *         @OA\Property(
     *           property="lawEntityKpp",
     *           type="string",
     *           example="",
     *           description="Юридическое лицо - КПП"
     *         ),
     *         @OA\Property(
     *           property="individualEmployerInn",
     *           type="string",
     *           example="",
     *           description="Индивидуальный предприниматель - ИНН"
     *         ),
     *         @OA\Property(
     *           property="chargeId",
     *           type="string",
     *           example="",
     *           description="ID начисления"
     *         ),
     *         @OA\Property(
     *           property="chargeSet",
     *           type="string",
     *           example="",
     *           description="Начисление выставил"
     *         ),
     *         @OA\Property(
     *           property="orgCode",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно - организация - код"
     *         ),
     *         @OA\Property(
     *           property="contractorCode",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно, контрагент - код"
     *         ),
     *         @OA\Property(
     *           property="contractorName",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно, контрагент - наименование"
     *         ),
     *         @OA\Property(
     *           property="additionOrgNaming",
     *           type="string",
     *           example="",
     *           description="Вкладка дополнительно - организация - наименование"
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */


    /**TODO: для PUT-запроса локально возникает ошибка Constant STOP_STATISTICS already defined
     * Пробовал отключать объявление константы в файле /rest/gis-gmp/index.php - но без толку. На tpgu такой ошибки нет
     * @param array $params
     * @param int $elementId
     * @return array
     */
    public static function updateItem(array $params, int $elementId): array
    {
        $el = new \CIBlockElement;
        $props = self::prepareProps($params, 'charge');
        $payerStatusesList = \MyCompany\WebService\VS\Gisgmp\Dictionary\PayerInfo::getElementsFromIblock([]);
        $payerStatusElementId = self::findElementIdByCode($props["BudgetIndex_status"], $payerStatusesList);
        $props["BudgetIndex_status"] = $payerStatusElementId;


        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(ImportedCharge::IBLOCK_CODE),
            'NAME' => $params["chargename"]
        ];
        $res = $el->Update($elementId, $fields);//Обновляем поля
        \CIBlockElement::SetPropertyValuesEx($elementId, false, $props);//Обновляем свойства
        if ($res) {
            //$result = ['result' => 'success'];
            $object = new ImportedCharge();
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

    public static function updateIblockItem($props, $id){
        \CIBlockElement::SetPropertyValuesEx(
            $id,
            Helper::getIblockIdByCode(self::IBLOCK_CODE),
            $props,
            array()
        );
    }

    /*
     * @OA\Delete(
     *   tags={"Charge"},
     *   path="/charge/charge/{id}",
     *   summary="Удалить начисление",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     example="1882",
     *     description="id элемента для удаления",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */

    /**TODO: Локально возникает ошибка Constant STOP_STATISTICS already defined
     * Пробовал отключать объявление константы в файле /rest/gis-gmp/index.php - но без толку. На tpgu такой ошибки нет
     * @param int $elementId
     * @return Response
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
}
