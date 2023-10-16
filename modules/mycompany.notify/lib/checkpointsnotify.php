<?php


namespace MyCompany\Notify;

use MyCompany\Notify\IblockHelper;
use Bitrix\Main\Localization\Loc;


class CheckPointsNotify
{
    /**
     * Проходимся по сущности (по инфоблоку), для которой нужно сформировать уведомления (например контрольные точки)
     * И фильтруем элементы этой сущности по проекту
     * И по дате завершения (план). И элементы, у которых не проставлено Дата заверешения (ФАКТ) - по ним формируем уведомления
     * Только уведомление нужно сформировать для тех контрольных точек, для которых уже наступила дата формирования уведомления
     * Например сегодня 1.09.2023, а первая дата уведомлений - 21.11.2023. Тогда формировать уведомления не нужно
     * А если сегодня 21.11.2023, то формируем уведомление на этот день (21.11.2023)
     * @param $HLBlockItem
     * @throws \Exception
     */
    const DAY_TO_ADD_TO_DEBUG = 4;

    public static function createCheckPointNotify(array $HLBlockItem)
    {
        $notifyRuleElementId = $HLBlockItem['ID'];
        $intervalDayCount = (int)$HLBlockItem['UF_INTERVAL'];
        $projectId = $HLBlockItem['UF_PROJECT'][0];
        $isEveryDayNotify = $HLBlockItem['UF_ADDITION_NOTIFY'] == 0 ? false : true;
        $userCategoryToNotify = $HLBlockItem['UF_USER_GROUPS'];
        $managersListId = $HLBlockItem['UF_MANAGERS_LIST'];
        $managersListId = array_filter($managersListId, function ($var) {
            if ($var == '0')
                return false;
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        $curDate = date('Y-m-d');
        $finish = date('Y-m-d', strtotime("+3 month"));
        $entityIblockId = $HLBlockItem['UF_NOTIFY_IBLOCK_ID'];
        $select = ['ID', 'PROPERTY_PLAN_DATE'];
        $filter = [
            'IBLOCK_ID' => $entityIblockId,
            'PROPERTY_PROJECT_LOOKUP' => $projectId,
            '<=PROPERTY_PLAN_DATE' => $finish,
            '>=PROPERTY_PLAN_DATE'=> $curDate,
            'PROPERTY_FACT_DATE' => false,
            [
                'LOGIC' => 'OR',
                ['PROPERTY_REMOVED_VALUE' => 'Нет'],
                ['PROPERTY_REMOVED_VALUE' => false]
            ]
        ];

        $entityElements = IblockHelper::getIblockElements($select, $filter);
        $entityElementsId = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->format('Y-m-d');
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');
        foreach ($entityElements as $entityElement) {
            $curCheckPointPlanDate = new \DateTime($entityElement["PROPERTY_PLAN_DATE_VALUE"]);
            $curCheckPointFirstDateNotify = clone $curCheckPointPlanDate;
            $curCheckPointFirstDateNotify->modify('-' . $intervalDayCount . ' day');
            if (($curDate == $curCheckPointFirstDateNotify) ||
                ($curDate > $curCheckPointFirstDateNotify && $isEveryDayNotify) ||
                ($curDate > $curCheckPointPlanDate)) {
                $entityElementsId[] = $entityElement['ID'];
            }
        }
        if (!empty($entityElementsId)) {
            $notifyRows = self::controlPointNotify($entityElementsId, $notifyRuleElementId, $intervalDayCount,
                $userCategoryToNotify, $HLBlockItem['ID'], $managersListId);
            self::addNotifyElement($notifyRows);
        }

    }

    public static function controlPointNotify(array $entityElementsIdList, int $notifyRuleId, int $notifyRuleInterval,
                                              int $usersCategory, int $HLBlockElementId, array $managersListId): array
    {
        $propsFilter = ['CODE' => ['ACCOUNTABLE_OI', 'RESULT_LOOKUP', 'PLAN_DATE', 'PROJECT_LOOKUP']];
        $elements = IblockHelper::getElementProperties('controlpan', $entityElementsIdList, $propsFilter);

        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->format('Y-m-d');
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');

        $elementsIdList = [];
        foreach ($elements as $elementItemkey => &$elementItem) {
            $curCheckPointPlanDate = new \DateTime($elementItem["PLAN_DATE"]["VALUE"]);
            $elementItem['users'] = [];
            if ($curDate > $curCheckPointPlanDate)
                $elementItem['users'] = $managersListId;
            $elementsIdList[] = $elementItemkey;
            $fpIdList[$elementItemkey] = $elementItem["PROJECT_LOOKUP"]["VALUE"];
            $resultIdList[$elementItemkey] = $elementItem["RESULT_LOOKUP"]["VALUE"];
        }

        self::getUsersByCategory($elements, $usersCategory, $HLBlockElementId);

        $fpNames = IblockHelper::getSectionNameById($fpIdList);
        $resultNames = IblockHelper::getResultNames('resproekt', $resultIdList);
        $checkPointNames = IblockHelper::getCheckPointNames('controlpan', $elementsIdList);
        $i = 0;
        $notifyList = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->modify('+' . self::DAY_TO_ADD_TO_DEBUG . ' day');
        //Добавляем в notifyList все контрольные и подконтрольные КТ текущего месяца +3 месяца
        $notifyTypesInfo = HLblockHelper::getUserFieldEnumList('Notify', 'UF_NOTIFY_TYPE');
        $notifyTypeId = self::getNotifyTypeIdByValue($notifyTypesInfo, 'Уведомление');
        foreach ($elements as $elementItemKey => &$elementItem) {
            if (!empty($elementItem['users'])) {
                foreach ($elementItem['users'] as $userId) {
                    $notifyList[$i]['userId'] = $userId;
                    $notifyList[$i]['entityId'] = $elementItemKey;
                    $notifyList[$i]['resultId'] = $elementItem['RESULT_LOOKUP']['VALUE'];
                    $notifyList[$i]['planDate'] = $elementItem['PLAN_DATE']['VALUE'];
                    $notifyList[$i]['notifyRuleId'] = $notifyRuleId;

                    $notifyFinishDate = new \DateTime($elementItem["PLAN_DATE"]["VALUE"]);
                    $notifyStartDate = clone $notifyFinishDate;
                    $notifyStartDate->modify('-' . $notifyRuleInterval . ' day');
                    $notifyStartDate->format('Y-m-d');

                    if ($curDate > $notifyStartDate)
                        $notifyStartDate = clone $curDate;

                    $notifyList[$i]['notifyStartDate'] = $notifyStartDate;
                    $notifyList[$i]['notifyFinishDate'] = $notifyFinishDate;
                    $notifyList[$i]['dates'][] = $curDate;
                    if (!in_array($userId, $managersListId))
                        $notifyList[$i]['text'] = Loc::getMessage('CHECK_POINT_PLAN_DATE_FINISH',
                            [
                                '#CHECK_POINT_ID#' => $elementItemKey,
                                '#CHECK_POINT_PLAN_DATE#' => $elementItem["PLAN_DATE"]["VALUE"],
                                '#CHECK_POINT_NAME#' => $checkPointNames[$elementItemKey],
                                '#CHECK_POINT_RESULT#' => $resultNames[$elementItem["RESULT_LOOKUP"]["VALUE"]],
                                '#FP_NAME#' => $fpNames[$elementItem["PROJECT_LOOKUP"]["VALUE"]]
                            ]
                        );
                    else
                        $notifyList[$i]['text'] = Loc::getMessage('MANAGER_CHECK_POINT_PLAN_DATE_FINISH',
                            [
                                '#CHECK_POINT_NUMBER#' => $elementItemKey,
                                '#CHECK_POINT_FINISH_DATE#' => $elementItem["PLAN_DATE"]["VALUE"],
                                '#CHECK_POINT_NAME#' => $checkPointNames[$elementItemKey]
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

    /**
     *
     * @param string $HLBlockCategoryName
     * @param string $iblockCode
     * @param array $entityIdList
     * @return array
     */
    public static function getUsersByCategory(array &$iblockElements, int $usersCategory, int $HLBlockElementId)
    {
        $userCategoryInfo = HLblockHelper::getUserFieldEnumList('NotifyRules', 'UF_USER_GROUPS');
        foreach ($iblockElements as &$elementItem) {
            switch ($userCategoryInfo[$usersCategory]['VALUE']) {
                case 'Ответственные исполнители':
                    if (!empty($elementItem["ACCOUNTABLE_OI"]["VALUE"]))
                        $elementItem['users'] = array_merge($elementItem['users'], $elementItem["ACCOUNTABLE_OI"]["VALUE"]);
                    break;
                case 'Администраторы федеральных проектов':
                    //Надо получить проект и вытянуть оттуда администраторов ФП
                    $projectId = $elementItem["PROJECT_LOOKUP"]["VALUE"];
                    $elementItem['users'] = array_merge($elementItem['users'],
                        HLblockHelper::getSectionUserFieldValueById('Project',
                            $projectId, 'UF_VERIFICATION_FP'));
                    break;
                case 'Все':
                    if (!empty($elementItem["ACCOUNTABLE_OI"]["VALUE"]))
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
                        HLblockHelper::getUserFieldValue(
                            'NotifyRules',
                            $HLBlockElementId, 'UF_ADDITION_USERS')
                    );
                    break;
                default:
                    $elementItem['users'] = [];
                    break;
            }
        }
    }

    public static function addNotifyElement($notifyRows)
    {
        $hlBlockid = HLblockHelper::getHLblockIdByCode('Notify');
        \CModule::IncludeModule('highloadblock');
        $entity_data_class = HLblockHelper::getEntityDataClass($hlBlockid);
        $notifyStatusEnumId = HLblockHelper::getUserFieldEnumId('Notify',
            'UF_NOTIFY_STATUS', 'Не прочитано');
        foreach ($notifyRows as $notifyItemKey => $notifyItem) {
            foreach ($notifyItem['dates'] as $dateItem) {
                $result = $entity_data_class::add([
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

}