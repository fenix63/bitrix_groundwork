<?php

namespace MyCompany\Notify;

use Bitrix\Main\Localization\Loc;
use MyCompany\Notify\Helpers\NotifyHelper;
use MyCompany\Notify\HLblockHelper;

class FinanceNotify
{
    const FIN_PLAN_IBLOCK_CODE = 'planphinans';
    const FIN_PLAN_DETAIL_IBLOCK_CODE = 'detail_finance_plan';
    const INDICATOR_IBLOCK_CODE = 'pokazatel';
    const INDICATOR_DETAIL_IBLOCK_CODE = 'indicators_plan';
    const RESULT_IBLOCK_CODE = 'resproekt';
    const DAY_TO_ADD_TO_DEBUG = 8;

    /**TODO:Метод в разработке
     * @param $HLBlockItem
     */
    public static function createFinanceNotify($HLBlockItem)
    {
        $hlFields = self::getHLFields($HLBlockItem);
        $entityElementsFinance = self::buildFinanceElements($hlFields);
        $entityElementsIndicators = self::buildIndicatorElements($hlFields);
        $totalEntities = array_merge($entityElementsFinance, $entityElementsIndicators);
        if (!empty($totalEntities)) {
            $notifyRows = NotifyHelper::prepareFinishNotifyRows(
                $totalEntities,
                $hlFields['notifyRuleElementId'],
                $hlFields['userCategoryToNotify'],
                self::DAY_TO_ADD_TO_DEBUG,
                $hlFields['textTemplate'],
                $hlFields['projectId']
            );
            NotifyHelper::addNotifyElement($notifyRows);
        }
    }

    public static function getHLFields($HLBlockItem): array
    {
        $params['notifyRuleElementId'] = $HLBlockItem['ID'];
        $params['intervalDayCount'] = (int)$HLBlockItem['UF_INTERVAL'];
        $params['projectId'] = $HLBlockItem['UF_PROJECT'][0];
        $params['isEveryDayNotify'] = $HLBlockItem['UF_ADDITION_NOTIFY'] == 0 ? false : true;
        $params['managersListId'] = $HLBlockItem['UF_MANAGERS_LIST'];
        $params['managersListId'] = array_filter($params['managersListId'], function ($var) {
            if ($var == '0')
                return false;
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        $params['userCategoryToNotify'] = $HLBlockItem['UF_USER_GROUPS'];
        $params['textTemplateId'] = $HLBlockItem['UF_TEMPLATE_LINK'];
        $params['templateInfo'] = HLblockHelper::getHLBlockItem('NotifyTemplates',
            $params['textTemplateId'], 'UF_NOTIFY_TEXT');
        $params['textTemplate'] = $params['templateInfo']['UF_NOTIFY_TEXT'];
        $params['textType'] = $params['templateInfo']['UF_TEMPLATE_TYPE'];

        return $params;
    }

    public static function buildFinanceElements(array $hlFields): array
    {
        //План финансирования - родительский инфоблок для инфоблока "План финансирования, детализация"
        $finPlanIblockId = IblockHelper::getIblockIdByCode(self::FIN_PLAN_IBLOCK_CODE);
        $finPlanElements = IblockHelper::getIblockElementsWithId(
            ['ID', 'NAME', 'PROPERTY_PROJECT_LOOKUP'],
            ['IBLOCK_ID' => $finPlanIblockId, 'PROPERTY_PROJECT_LOOKUP' => $hlFields['projectId']]
        );
        $finPlanElementsIdList = self::getEntityIdList($finPlanElements);
        $finPlanElementProps = Iblockhelper::getElementProperties(self::FIN_PLAN_IBLOCK_CODE,
            $finPlanElementsIdList, ['CODE' => ['RESULTAT_LOOKUP', 'YEAR', 'FIN_TYPE']]);

        $resultIdList = self::getEntityResultIdList($finPlanElementProps, ['RESULTAT_LOOKUP']);
        $resultiblockId = Iblockhelper::getIblockIdByCode(self::RESULT_IBLOCK_CODE);
        $finPlanElements['results'] = IblockHelper::getIblockElementsWithId(
            ['ID', 'IBLOCK_ID', 'NAME'],
            ['IBLOCK_ID' => $resultiblockId, 'ID' => $resultIdList]
        );

        //План финансирования - детализация
        $start = date('Y-m-d');
        $finish = date('Y-m-d', strtotime("+3 month"));
        $finPlanDetailIblockId = IblockHelper::getIblockIdByCode(self::FIN_PLAN_DETAIL_IBLOCK_CODE);
        $finPlanDetailElements = IblockHelper::getIblockElementsWithId(
            ['ID', 'NAME', 'PROPERTY_FINANCE_CONTROL_DATE', 'PROPERTY_FINANCE_FACT',
                'PROPERTY_FINANCE_PLAN_LOOKUP', 'PROPERTY_ACCOUNTABLE_OI'],
            [
                'IBLOCK_ID' => $finPlanDetailIblockId,
                'PROPERTY_FINANCE_PLAN_LOOKUP' => $finPlanElementsIdList,
                '>=PROPERTY_FINANCE_CONTROL_DATE' => $start,
                '<=PROPERTY_FINANCE_CONTROL_DATE' => $finish,
                'PROPERTY_FINANCE_FACT' => false
            ]
        );
        $finPlanDetailElementsIdList = self::getEntityIdList($finPlanDetailElements);
        $finPlanDetailElementsProps = Iblockhelper::getElementProperties(
            self::FIN_PLAN_DETAIL_IBLOCK_CODE,
            $finPlanDetailElementsIdList,
            ['CODE' => ['FINANCE_CONTROL_DATE', 'FINANCE_FACT', 'FINANCE_PLAN_LOOKUP', 'ACCOUNTABLE_OI']]
        );
        $financeParams = [
            'entityElements' => $finPlanDetailElements,
            'entityProps' => $finPlanDetailElementsProps,
            'parentElements' => $finPlanElements,
            'parentProps' => $finPlanElementProps,
            'intervalDayCount' => $hlFields['intervalDayCount'],
            'isEveryDayNotify' => $hlFields['isEveryDayNotify'],
            'controlDateProperty' => 'FINANCE_CONTROL_DATE',
            'parentProperty' => 'FINANCE_PLAN_LOOKUP',
            'resultProperty' => 'RESULTAT_LOOKUP',
            'entityType' => 'finance',
            'managersListId' => $hlFields['managersListId'],
            'notifyRuleId' => $hlFields['notifyRuleElementId'],
            'textType' => $hlFields['textType'],
            'textTemplate' => $hlFields['textTemplate'],
            'hasParent' => true,
            'projectId' => $hlFields['projectId']
        ];

        $entityElementsFinance = self::filterEntityElementsByDate($financeParams);

        return $entityElementsFinance;
    }

    public static function buildIndicatorElements(array $hlFields): array
    {
        $indicatorIblockId = IblockHelper::getIblockIdByCode(self::INDICATOR_IBLOCK_CODE);
        $indicatorElements = IblockHelper::getIblockElementsWithId(
            ['ID', 'NAME', 'IBLOCK_ID', 'PROPERTY_PROJECT'],
            [
                'IBLOCK_ID' => $indicatorIblockId,
                'PROPERTY_PROJECT' => $hlFields['projectId'],
                [
                    'LOGIC' => 'OR',
                    ['PROPERTY_REMOVED' => 'Нет'],
                    ['PROPERTY_REMOVED' => false]
                ]

            ]
        );
        $indicatorElementsIdList = self::getEntityIdList($indicatorElements);
        $indicatorElementsProps = IblockHelper::getElementProperties(self::INDICATOR_IBLOCK_CODE,
            $indicatorElementsIdList,
            ['CODE' => ['ACCOUNTABLE_OI', 'RESULT_LOOKUP']]
        );
        $start = date('Y-m-d');
        $finish = date('Y-m-d', strtotime("+3 month"));
        $indicatorsDetailIblockId = IblockHelper::getIblockIdByCode(self::INDICATOR_DETAIL_IBLOCK_CODE);
        $indicatorsDetailElements = IblockHelper::getIblockElementsWithId(
            ['ID', 'NAME', 'PROPERTY_INDICATOR_CONTROL_DATE', 'PROPERTY_INDICATOR_FACT', 'PROPERTY_INDICATOR_LOOKUP'],
            [
                'IBLOCK_ID' => $indicatorsDetailIblockId,
                'PROPERTY_INDICATOR_LOOKUP' => $indicatorElementsIdList,
                'PROPERTY_INDICATOR_FACT' => false,
                '>=PROPERTY_INDICATOR_CONTROL_DATE' => $start,
                '<=PROPERTY_INDICATOR_CONTROL_DATE' => $finish,
            ]
        );
        $indicatorsDetailElementsIdList = self::getEntityIdList($indicatorsDetailElements);
        $indicatorsDetailElementsProps = IblockHelper::getElementProperties(
            self::INDICATOR_DETAIL_IBLOCK_CODE,
            $indicatorsDetailElementsIdList,
            ['CODE' => ['INDICATOR_CONTROL_DATE', 'INDICATOR_LOOKUP']]
        );
        $params = [
            'entityElements' => $indicatorsDetailElements,
            'entityProps' => $indicatorsDetailElementsProps,
            'parentElements' => $indicatorElements,
            'parentProps' => $indicatorElementsProps,
            'resultProperty' => 'RESULT_LOOKUP',
            'intervalDayCount' => $hlFields['intervalDayCount'],
            'isEveryDayNotify' => $hlFields['isEveryDayNotify'],
            'controlDateProperty' => 'INDICATOR_CONTROL_DATE',
            'parentProperty' => 'INDICATOR_LOOKUP',
            'entityType' => 'indicator',
            'managersListId' => $hlFields['managersListId'],
            'notifyRuleId' => $hlFields['notifyRuleElementId'],
            'textType' => $hlFields['textType'],
            'textTemplate' => $hlFields['textTemplate'],
            'hasParent' => true,
            'projectId' => $hlFields['projectId']
        ];

        $entityElementsIndicators = self::filterIndicatorsByDate($params);

        return $entityElementsIndicators;
    }

    /**
     * @param array $entityElements
     * @param int $intervalDayCount
     * @return array
     * @throws \Exception
     */
    public static function filterEntityElementsByDate(array $params): array
    {
        $preparedData = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->format('Y-m-d');
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');

        foreach ($params['entityElements'] as $entityElementKey => $entityElementItem) {
            $controlDate = new \DateTime($entityElementItem['PROPERTY_' . $params['controlDateProperty'] . '_VALUE']);
            $firstDayNotify = clone $controlDate;
            $firstDayNotify->modify('-' . $params['intervalDayCount'] . ' day');
            if ($curDate == $firstDayNotify ||
                ($curDate > $firstDayNotify && $params['isEveryDayNotify']) ||
                ($curDate > $controlDate)
            ) {
                $parentEntityId = $params['entityProps'][$entityElementKey][$params['parentProperty']]['VALUE'];
                $resultId = $params['parentProps'][$parentEntityId][$params['resultProperty']]['VALUE'];
                $preparedData[$entityElementKey]['resultId'] = $resultId;
                $preparedData[$entityElementKey]['resultText'] = $params['parentElements']['results'][$resultId]['NAME'];
                $preparedData[$entityElementKey]['year'] = $params['parentProps'][$parentEntityId]['YEAR']['VALUE'];

                $monthNumber = explode('.',
                    $params['entityProps'][$entityElementKey][$params['controlDateProperty']]["VALUE"])[1];
                $month = Iblockhelper::getMonthNameByNumber($monthNumber);
                $preparedData[$entityElementKey]['month'] = $month;

                $preparedData[$entityElementKey]['finTypeId'] = $params['parentProps'][$parentEntityId]['FIN_TYPE']['VALUE_ENUM_ID'];
                $preparedData[$entityElementKey]['finTypeName'] = $params['parentProps'][$parentEntityId]['FIN_TYPE']['VALUE'];

                $preparedData[$entityElementKey]['entityType'] = $params['entityType'];
                $preparedData[$entityElementKey]['ACCOUNTABLE_OI'] =
                    $params['entityProps'][$entityElementKey]['ACCOUNTABLE_OI']['VALUE'];
                $preparedData[$entityElementKey]['users'] = [];
                $preparedData[$entityElementKey]['notifyRuleId'] = $params['notifyRuleId'];
                $preparedData[$entityElementKey]['projectId'] = $params['projectId'];
            }

            if ($curDate > $controlDate)
                $preparedData[$entityElementKey]['users'] = $params['managersListId'];

        }

        return $preparedData;
    }

    public static function filterIndicatorsByDate(array $params): array
    {
        $preparedData = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->format('Y-m-d');
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');

        foreach ($params['entityElements'] as $entityElementKey => $entityElementItem) {
            $controlDate = new \DateTime($entityElementItem['PROPERTY_' . $params['controlDateProperty'] . '_VALUE']);
            $firstDayNotify = clone $controlDate;
            $firstDayNotify->modify('-' . $params['intervalDayCount'] . ' day');
            if ($curDate == $firstDayNotify ||
                ($curDate > $firstDayNotify && $params['isEveryDayNotify']) ||
                ($curDate > $controlDate)
            ) {
                $parentEntityId = $params['entityProps'][$entityElementKey][$params['parentProperty']]['VALUE'];
                $resultId = $params['parentProps'][$parentEntityId][$params['resultProperty']]['VALUE'];
                $preparedData[$entityElementKey]['entityName'] = $entityElementItem['NAME'];
                $preparedData[$entityElementKey]['resultId'] = $resultId;
                $preparedData[$entityElementKey]['resultText'] = $params['parentElements']['results'][$resultId]['NAME'];
                $preparedData[$entityElementKey]['year'] = $params['parentProps'][$parentEntityId]['YEAR']['VALUE'];

                $monthNumber = explode('.',
                    $params['entityProps'][$entityElementKey][$params['controlDateProperty']]["VALUE"])[1];
                $month = Iblockhelper::getMonthNameByNumber($monthNumber);
                $preparedData[$entityElementKey]['month'] = $month;

                $preparedData[$entityElementKey]['finTypeId'] = $params['parentProps'][$parentEntityId]['FIN_TYPE']['VALUE_ENUM_ID'];
                $preparedData[$entityElementKey]['finTypeName'] = $params['parentProps'][$parentEntityId]['FIN_TYPE']['VALUE'];

                $preparedData[$entityElementKey]['entityType'] = $params['entityType'];
                $preparedData[$entityElementKey]['ACCOUNTABLE_OI'] =
                    $params['parentProps'][$parentEntityId]['ACCOUNTABLE_OI']['VALUE'];
                $preparedData[$entityElementKey]['users'] = [];
                $preparedData[$entityElementKey]['notifyRuleId'] = $params['notifyRuleId'];
                $preparedData[$entityElementKey]['projectId'] = $params['projectId'];
            }

            if ($curDate > $controlDate)
                $preparedData[$entityElementKey]['users'] = $params['managersListId'];

        }

        return $preparedData;
    }

    public static function getEntityIdList(array $elements): array
    {
        $idList = [];
        foreach ($elements as $elementKey => $elementItem) {
            $idList[] = $elementKey;
        }

        return $idList;
    }

    public static function getEntityResultIdList(array $elements, array $propsToGet): array
    {
        $result = [];
        foreach ($elements as $elementKey => $elementValue) {
            foreach ($propsToGet as $propKey => $propValue) {
                $result[] = $elementValue[$propValue]['VALUE'];
            }
        }

        return $result;
    }

}