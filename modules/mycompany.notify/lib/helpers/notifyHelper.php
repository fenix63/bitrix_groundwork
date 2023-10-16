<?php

namespace MyCompany\Notify\Helpers;

use MyCompany\Notify\HLblockHelper;
use MyCompany\Notify\IblockHelper;
use Bitrix\Main\Localization\Loc;


class NotifyHelper
{
    const HL_BLOCK_NOTIFY_CODE = 'Notify';

    public static function getNotifyTypeIdByValue(array $notifyTypes, string $value)
    {
        foreach ($notifyTypes as $notifyTypeKey => $notifyType) {
            if ($notifyType['VALUE'] == $value)
                return $notifyTypeKey;
        }

        return false;
    }

    public static function getUsersByCategory(array &$iblockElements, int $usersCategory,
                                              int $HLBlockElementId, int $projectId)
    {
        $userCategoryInfo = HLblockHelper::getUserFieldEnumList('NotifyRules', 'UF_USER_GROUPS');
        $adminsFP = HLblockHelper::getSectionUserFieldValueById('Project',
            $projectId, 'UF_VERIFICATION_FP');
        $additionUsers = HLblockHelper::getUserFieldValue('NotifyRules',
            $HLBlockElementId, 'UF_ADDITION_USERS');
        foreach ($iblockElements as &$elementItem) {
            switch ($userCategoryInfo[$usersCategory]['VALUE']) {
                case 'Ответственные исполнители':
                    if (!empty($elementItem["ACCOUNTABLE_OI"]))
                        $elementItem['users'] = array_merge($elementItem['users'], $elementItem["ACCOUNTABLE_OI"]);
                    break;
                case 'Администраторы федеральных проектов':
                    //Надо получить проект и вытянуть оттуда администраторов ФП
                    $elementItem['users'] = array_merge($elementItem['users'], $adminsFP);
                    break;
                case 'Все':
                    if (!empty($elementItem["ACCOUNTABLE_OI"]["VALUE"]))
                        $elementItem['users'] = array_merge($elementItem['users'], $elementItem["ACCOUNTABLE_OI"]["VALUE"]);

                    $elementItem['users'] = array_merge($elementItem['users'], $adminsFP);
                    break;
                case 'Не направлять':
                    //Смотрим поле Дополнительно оповещаемые пользователи
                    $elementItem['users'] = array_merge($elementItem['users'], $additionUsers);
                    break;
                default:
                    $elementItem['users'] = [];
                    break;
            }
        }
    }

    /**
     * Добавление новых элементов в HL-блок
     * @param $notifyRows
     * @throws \Exception
     */
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
                    'UF_NOTIFY_TYPE' => $notifyItem["notifyTypeId"]
                ]);
            }

        }

    }

    /**
     * PROPERTY_RESULTAT_LOOKUP_VALUE если нужно взять ID свойства RESULTAT_LOOKUP
     * @param array $elements
     * @param string $fieldCode
     * @return array
     */
    public static function getEntityIdList(array $elements, string $fieldCode): array
    {
        $idList = [];
        foreach ($elements as $elementKey => $elementItem) {
            if (!empty($elementItem[$fieldCode]))
                $idList[] = $elementItem[$fieldCode];
        }

        return $idList;
    }

    public static function prepareFinishNotifyRows(array $entityElements, int $notifyRuleId,
                                                   int $usersCategory, int $daysToAdd,
                                                   string $textTemplate, int $projectid): array
    {
        self::getUsersByCategory($entityElements, $usersCategory, $notifyRuleId, $projectid);
        $notifyRowList = [];
        $date = date('Y-m-d');
        $curDate = new \DateTime($date);
        $curDate->modify('+' . $daysToAdd . ' day');
        $i = 0;

        $finishText = '';
        foreach ($entityElements as $elementItemKey => $elementItem) {
            switch ($elementItem['entityType']) {
                case 'finance':
                    $finishText .= ' -Год: ' . $elementItem['year'] . ', ' . $elementItem["finTypeName"] . PHP_EOL;
                    $finishText .= 'Необходимо заполнить данные за: ' . $elementItem["month"] . PHP_EOL;
                    break;
                case 'indicator':
                    $finishText .= 'Показатель: ' . $elementItem['entityName'] . PHP_EOL;
                    $finishText .= 'Необходимо заполнить данные за ' . $elementItem["month"] . PHP_EOL;
                    break;
            }


        }
        $notifyTypeId = HLblockHelper::getUserFieldEnumId('Notify', 'UF_NOTIFY_TYPE', 'Уведомление');
        foreach ($entityElements as $elementItemKey => &$elementItem) {
            if (!empty($elementItem['users'])) {
                foreach ($elementItem['users'] as $userId) {
                    $notifyRowList[$i]['userId'] = $userId;
                    $notifyRowList[$i]['entityId'] = $elementItemKey;
                    $notifyRowList[$i]['resultId'] = $elementItem["resultId"];
                    $notifyRowList[$i]['resultText'] = $elementItem["resultText"];
                    $notifyRowList[$i]['dateToSendNotify'] = $curDate;
                    $notifyRowList[$i]['notifyRuleId'] = $elementItem["notifyRuleId"];
                    $text = $textTemplate;
                    $text = str_replace('#RESULT_NAME#', $notifyRowList[$i]['resultText'], $text);
                    $text = str_replace('#RESULT_YEAR#', $elementItem['year'], $text);
                    $text = str_replace('#RESULT_MONTH#', $elementItem['month'], $text);
                    $text .= PHP_EOL;
                    $text = str_replace('#FINANCE_PLAN_ITEM_LIST#', $finishText, $text);
                    //$text = str_replace('#INDICATORS_LIST#', $groupIndicatorsText, $text);
                    $notifyRowList[$i]['text'] = $text;
                    $notifyRowList[$i]['dates'][] = $curDate;
                    $notifyRowList[$i]['notifyTypeId'] = $notifyTypeId;
                    $i++;
                }
            }
        }

        return $notifyRowList;
    }
}
