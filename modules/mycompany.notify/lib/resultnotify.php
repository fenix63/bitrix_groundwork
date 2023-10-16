<?php

namespace MyCompany\Notify;

use MyCompany\Notify\IblockHelper;
use Bitrix\Main\Localization\Loc;

class ResultNotify
{
    /**TODO:метод в разработке
     * Сначала берём инфоблок "Результаты ФП". Фильтруем его по номеру проекта. Получаем отфильтрованные элементы.
     * Берём ID отфильтрованных элементов и фильтруем инфоблок "Плановые значения результатов", по полю "Результат".
     * Получаем отфильтрованные элементы инфоблока "Плановые значения результатов".
     * Далее берём ID отфильтрованных элементов инфоблока "Плановые значения результатов"
     * И подставляем их в инфоблок "Плановые значения результатов, детализация",
     * в поле "Плановое значение результата".
     * И вот уже на этом этапе мы получаем элементы, отфильтрованные по проекту.
     * Далее в инфоболке "Плановые значения результатов, детализация" смотрим поля "Дата" и "Факт".
     * Значение поля "Дата" должно быть меньше чем текущая дата + 3 месяца.
     * Смотрим поле "Факт" - оно должно быть не заполнено.
     * В выборку попадают элементы, у которых Дата <= текущая дата + 3 месяца и "Факт" - не заполнено.
     * Получили элементы. Далее надо сформировать уведомления.
     * Проходимся по каждому элементу, достаём у него ID из свойства "Плановое значения результата"
     * В плановых значениях результата достаём ID из свойства "Результат" + достаём ID из свойства "Ответственный за отчётность".
     * В результатах достаём NAME и может быть ID.
     * Формируем данные для уведомления (для записи в HL уведомлений)
     * @param $HLBlockItem
     * @throws \Exception
     */
    const DAY_TO_ADD_TO_DEBUG = 3;
    const HL_BLOCK_NOTIFY_CODE = 'Notify';

    public static function createResultNotify($HLBlockItem)
    {
        $notifyRuleElementId = $HLBlockItem['ID'];
        $intervalDayCount = (int)$HLBlockItem['UF_INTERVAL'];
        $projectId = $HLBlockItem['UF_PROJECT'][0];
        $notifyTopicId = $HLBlockItem['UF_NOTIFY_TOPIC'];
        $isEveryDayNotify = $HLBlockItem['UF_ADDITION_NOTIFY'] == 0 ? false : true;
        $managersListId = $HLBlockItem['UF_MANAGERS_LIST'];
        $managersListId = array_filter($managersListId, function ($var) {
            if ($var == '0')
                return false;
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        $userCategoryToNotify = $HLBlockItem['UF_USER_GROUPS'];
        $curDate = date('Y-m-d');
        $finish = date('Y-m-d', strtotime("+3 month"));
        $entityIblockId = $HLBlockItem['UF_NOTIFY_IBLOCK_ID'];
        $select = ['ID', 'NAME', 'PROPERTY_PROJECT'];
        $filter = [
            'IBLOCK_ID' => $entityIblockId,
            'PROPERTY_PROJECT' => $projectId,
            [
                'LOGIC' => 'OR',
                ['PROPERTY_REMOVED_VALUE' => 'Нет'],
                ['PROPERTY_REMOVED_VALUE' => false]
            ]
        ];
        //Результаты ФП
        $resultElements = IblockHelper::getIblockElementsWithid($select, $filter);
        $resultElementsIdList = [];
        foreach ($resultElements as $resultItemKey => $resultItem) {
            $resultElementsIdList[] = $resultItemKey;
        }

        //Плановые значения результатов
        $resultPlanValuesIblockId = IblockHelper::getIblockIdByCode('planres');
        $resultPlanValuesFilter = ['IBLOCK_ID' => $resultPlanValuesIblockId, 'PROPERTY_RESULT_LOOKUP' => $resultElementsIdList];
        $resultPlanValues = IblockHelper::getElementPropertiesByFilter(
            'planres',
            $resultPlanValuesFilter,
            ['CODE' => ['RESULT_LOOKUP', 'ACCOUNTABLE_OI']]
        );

        $resultPlanValuesIdList = [];
        foreach ($resultPlanValues as $resultPlanValuesItemKey => $resultPlanValuesItem) {
            $resultPlanValuesIdList[] = $resultPlanValuesItemKey;
        }

        //Плановые значения результатов, детализация
        $resultPlanDetailIblockId = IblockHelper::getIblockIdByCode('detailplanres');
        $resultPlanDetailElements = IblockHelper::getIblockElements(
            ['ID', 'NAME', 'PROPERTY_RESULT_PLAN_LOOKUP', 'PROPERTY_DATE_DETAIL_PLAN', 'PROPERTY_DETAIL_FACT'],
            [
                'IBLOCK_ID' => $resultPlanDetailIblockId,
                'PROPERTY_RESULT_PLAN_LOOKUP' => $resultPlanValuesIdList,
                '<=PROPERTY_DATE_DETAIL_PLAN' => $finish,
                '>=PROPERTY_DATE_DETAIL_PLAN' => $curDate,
                'PROPERTY_DETAIL_FACT' => false
            ]
        );

        $resultPlanDetailItems = self::prepareEntityDataArray(
            $resultPlanDetailElements,
            $intervalDayCount,
            $isEveryDayNotify,
            $resultPlanValues,
            $resultElements,
            $managersListId
        );
        if (!empty($resultPlanDetailItems)) {
            $notifyRows = self::prepareResultNotifyRows($resultPlanDetailItems, $notifyRuleElementId, $userCategoryToNotify);
            self::addNotifyElement($notifyRows);
        }

    }

    public static function prepareEntityDataArray(array $entityElements, int $intervalDayCount,
                                                  bool $isEveryDayNotify, array $resultPlanValues,
                                                  array $resultElements, array $managersListId): array
    {
        $preparedData = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->format('Y-m-d');
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');
        foreach ($entityElements as $entityElementKey => $entityElementItem) {
            //Контрольная дата
            $curResultDetailPlanDate = new \DateTime($entityElementItem['PROPERTY_DATE_DETAIL_PLAN_VALUE']);
            $curResultDetailPlanFirstDayNotify = clone $curResultDetailPlanDate;
            $curResultDetailPlanFirstDayNotify->modify('-' . $intervalDayCount . ' day');
            //Если наступила первая дата для оповещений - то формируем массив с данными.
            //Если текущая дата больше, чем 1-й день для оповещений, то также проверяем нужно ли уведомлять каждый день
            if ($curDate == $curResultDetailPlanFirstDayNotify ||
                ($curDate > $curResultDetailPlanFirstDayNotify && $isEveryDayNotify) ||
                ($curDate > $curResultDetailPlanDate)
            ) {
                $preparedData[$entityElementKey] = $entityElementItem;
                $preparedData[$entityElementKey]['dateToNotify'] = $curDate;

                $parentId = $entityElementItem["PROPERTY_RESULT_PLAN_LOOKUP_VALUE"];
                $grandParentId = $resultPlanValues[$parentId]['RESULT_LOOKUP']['VALUE'];
                //Ответственный за отчётность
                $preparedData[$entityElementKey]['ACCOUNTABLE_OI'] =
                    $resultPlanValues[$parentId]['ACCOUNTABLE_OI']['VALUE'];

                //Результат
                $preparedData[$entityElementKey]['RESULT_LOOKUP'] =
                    $resultPlanValues[$parentId]['RESULT_LOOKUP']['VALUE'];
                //Проект
                $preparedData[$entityElementKey]['PROJECT_ID'] =
                    $resultElements[$grandParentId]['PROPERTY_PROJECT_VALUE'];
                $preparedData[$entityElementKey]['users'] = [];
            }

            //Если текущая дата больше чем контрольная дата - то дополнительно нужно оповещать
            //ещё и вышестоящих руководителей (ID задаются в самом правиле)
            if ($curDate > $curResultDetailPlanDate) {
                $preparedData[$entityElementKey]['users'] = $managersListId;
            }
        }


        return $preparedData;
    }

    public static function prepareResultNotifyRows(array $entityElements, int $notifyRuleId, int $usersCategory): array
    {
        foreach ($entityElements as $entityElementKey => $entityElementItem) {
            $elementsIdList[] = $entityElementKey;
            $fpIdList[$entityElementKey] = $entityElementItem["PROJECT_ID"];
            $resultIdList[$entityElementKey] = $entityElementItem["RESULT_LOOKUP"];
        }

        self::getUsersByCategory($entityElements, $usersCategory, $notifyRuleId);
        $fpNames = IblockHelper::getSectionNameById($fpIdList);
        $resultNames = IblockHelper::getResultNames('resproekt', $resultIdList);
        $i = 0;
        $notifyList = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');
        $notifyTypesInfo = HLblockHelper::getUserFieldEnumList('Notify', 'UF_NOTIFY_TYPE');
        $notifyTypeId = self::getNotifyTypeIdByValue($notifyTypesInfo, 'Уведомление');
        foreach ($entityElements as $elementItemKey => &$elementItem) {
            if (!empty($elementItem['users'])) {
                foreach ($elementItem['users'] as $userId) {
                    $notifyList[$i]['userId'] = $userId;
                    $notifyList[$i]['entityId'] = $elementItemKey;
                    $notifyList[$i]['resultId'] = $elementItem['RESULT_LOOKUP'];
                    $notifyList[$i]['resultFinishDate'] = $elementItem["PROPERTY_DATE_DETAIL_PLAN_VALUE"];
                    $notifyList[$i]['dateToSendNotify'] = $elementItem['dateToNotify'];
                    $notifyList[$i]['resultName'] = $elementItem["NAME"];
                    $notifyList[$i]['fpName'] = $fpNames[$fpIdList[$elementItemKey]];
                    $notifyList[$i]['notifyRuleId'] = $notifyRuleId;
                    $notifyList[$i]['dates'][] = $curDate;
                    $notifyList[$i]['text'] = Loc::getMessage('RESULT_DATE_FINISH',
                        [
                            '#RESULT_NUMBER#' => $notifyList[$i]['resultId'],
                            '#RESULT_NAME#' => $notifyList[$i]['resultName'],
                            '#FP_NAME#' => $notifyList[$i]['fpName']
                        ]
                    );
                    $notifyList[$i]['notifyType'] = $notifyTypeId;
                    $i++;
                }
            }

        }


        return $notifyList;
    }

    public static function getNotifyTypeIdByValue(array $notifyTypes, string $value)
    {
        foreach ($notifyTypes as $notifyTypeKey => $notifyType) {
            if ($notifyType['VALUE'] == $value)
                return $notifyTypeKey;
        }

        return false;
    }

    public static function addNotifyElement($notifyRows)
    {
        $hlBlockid = HLblockHelper::getHLblockIdByCode(self::HL_BLOCK_NOTIFY_CODE);
        \CModule::IncludeModule('highloadblock');
        $entityDataClass = HLblockHelper::getEntityDataClass($hlBlockid);
        $notifyStatusEnumId = HLblockHelper::getUserFieldEnumId(self::HL_BLOCK_NOTIFY_CODE,
            'UF_NOTIFY_STATUS', 'Не прочитано');
        foreach ($notifyRows as $notifyItemKey => $notifyItem) {
            foreach ($notifyItem['dates'] as $dateItem) {
                $result = $entityDataClass::add([
                    'UF_NOTIFY_DATETIME' => $dateItem->format('d.m.Y'),//Дата для отправки
                    'UF_RULE_ID' => $notifyItem["notifyRuleId"],//ID правила уведомлений
                    'UF_NOTIFY_STATUS' => $notifyStatusEnumId,//Статус уведомления (ушло/ не ушло)
                    'UF_NOTIFY_USER' => $notifyItem["userId"],//Пользователь, которому надо отпарвить уведомление
                    'UF_NOTIFY_TEXT' => $notifyItem["text"],
                    'UF_NOTIFY_TYPE' => $notifyItem["notifyType"]
                ]);
            }

        }

    }

    public static function getUsersByCategory(array &$iblockElements, int $usersCategory, int $HLBlockElementId)
    {
        $userCategoryInfo = HLblockHelper::getUserFieldEnumList('NotifyRules', 'UF_USER_GROUPS');
        foreach ($iblockElements as &$elementItem) {
            switch ($userCategoryInfo[$usersCategory]['VALUE']) {
                case 'Ответственные исполнители':
                    $elementItem['users'] = array_merge($elementItem['users'], $elementItem["ACCOUNTABLE_OI"]);
                    break;
                case 'Администраторы федеральных проектов':
                    //Надо получить проект и вытянуть оттуда администраторов ФП
                    $projectId = $elementItem["PROJECT_LOOKUP"]["VALUE"];
                    $elementItem['users'] = array_merge($elementItem['users'],
                        HLblockHelper::getSectionUserFieldValueById('Project',
                            $projectId, 'UF_VERIFICATION_FP'));
                    break;
                case 'Все':
                    $elementItem['users'] = array_merge($elementItem['users'], $elementItem["ACCOUNTABLE_OI"]["VALUE"]);
                    $projectId = $elementItem["PROJECT_LOOKUP"]["VALUE"];
                    $elementItem['users'] = array_merge(
                        $elementItem['users'],
                        HLblockHelper::getSectionUserFieldValueById('Project',
                            $projectId, 'UF_VERIFICATION_FP')
                    );
                    break;
                case 'Не направлять':
                    //Смотрим поле Дополнительно оповещаемые пользователи
                    $elementItem['users'] = array_merge($elementItem['users'],
                        HLblockHelper::getUserFieldValue('NotifyRules',
                            $HLBlockElementId, 'UF_ADDITION_USERS'));
                    break;
                default:
                    $elementItem['users'] = [];
                    break;
            }
        }
    }
}