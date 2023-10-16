<?php

namespace MyCompany\Notify\Event;

use Bitrix\Main\Localization\Loc;
use MyCompany\Notify\HLblockHelper;
use  MyCompany\Notify\IblockHelper;

Loc::loadMessages(__FILE__);
\Bitrix\Main\Loader::includeModule('iblock');

class IblockSection
{
    /**
     * Обработчик события "Изменение раздела инфоблока"
     * @param \Bitrix\Main\Event $event
     */
    const PROJECT_IBLOCK_CODE = 'Project';
    const PROJECT_FPADMIN_FIELD_CODE = 'UF_VERIFICATION_FP';
    const HLBLOCK_NOTIFY_CODE = 'Notify';

    public static function onIblockBeforeSectionChangeHandler(&$arFields)
    {
        $iblockId = $arFields["IBLOCK_ID"];
        $iblockCode = IblockHelper::getIblockCodeById($iblockId);
        if ($iblockCode == self::PROJECT_IBLOCK_CODE) {
            $sectionId = $arFields["ID"];
            $projectOldAdminId = HLblockHelper::getSectionUserFieldValueById(
                self::PROJECT_IBLOCK_CODE,
                $sectionId,
                self::PROJECT_FPADMIN_FIELD_CODE
            )[0];

            if (!empty($projectOldAdminId))
                $projectOldAdminId = (int)$projectOldAdminId;
            if (!empty($arFields[self::PROJECT_FPADMIN_FIELD_CODE]))
                $projectNewAdminId = (int)$arFields[self::PROJECT_FPADMIN_FIELD_CODE];

            if ($projectOldAdminId != $projectNewAdminId) {
                if (!empty($projectOldAdminId)) {
                    $notifyList['usersCancelFPAdmin']['userId'] = (int)$projectOldAdminId;
                    $notifyList['usersCancelFPAdmin']['messageType'] = 'CANCEL_FP_PROJECT_ADMIN';
                }

                if (!empty($projectNewAdminId)) {
                    $notifyList['usersAddFPAdmin']['userId'] = $projectNewAdminId;
                    $notifyList['usersAddFPAdmin']['messageType'] = 'ADD_FP_PROJECT_ADMIN';
                }

                $notifyTypesInfo = HLblockHelper::getUserFieldEnumList('Notify', 'UF_NOTIFY_TYPE');
                $notifyTypeId = self::getNotifyTypeId($notifyTypesInfo, 'Уведомление');
                self::createNotify($notifyList, $sectionId, $notifyTypeId);
            }
        }
    }

    public static function getNotifyTypeId(array $notifyTypesInfo, string $value)
    {
        foreach ($notifyTypesInfo as $notifyTypeKey => $notifyTypeItem) {
            if ($notifyTypeItem['VALUE'] == $value)
                return $notifyTypeKey;
        }

        return null;
    }

    public static function createNotify(array $notifyList, int $projectId, int $notifyTypeId)
    {
        $hlBlockid = HLblockHelper::getHLblockIdByCode(self::HLBLOCK_NOTIFY_CODE);
        \CModule::IncludeModule('highloadblock');
        $entityDataClass = HLblockHelper::getEntityDataClass($hlBlockid);
        $notifyStatusEnumId = HLblockHelper::getUserFieldEnumId(self::HLBLOCK_NOTIFY_CODE,
            'UF_NOTIFY_STATUS', 'Не прочитано');
        $usersIdList = [];
        foreach ($notifyList as $notifyItem) {
            $usersIdList[] = (int)$notifyItem['userId'];
        }
        $usersInfo = IblockHelper::getUsersInfo($usersIdList, ['NAME', 'SECOND_NAME', 'PERSONAL_GENDER']);
        $projectName = IblockHelper::getSectionNameById($projectId);
        $now = date('d.m.Y');
        foreach ($notifyList as $notifyItem) {
            $gender = 'Уважаемый(-ая)';
            if (!empty($usersInfo[$notifyItem['userId']]['PERSONAL_GENDER'])) {
                $gender = $usersInfo[$notifyItem['userId']]['PERSONAL_GENDER'] == 'M' ? 'Уважаемый' : 'Уважаемая';
            }
            $notifyText = Loc::getMessage(
                $notifyItem['messageType'],
                [
                    '#USER_GENDER#' => $gender,
                    '#USER_FIO#' => $usersInfo[$notifyItem['userId']]['NAME'] . ' ' .
                        $usersInfo[$notifyItem['userId']]['SECOND_NAME'],
                    '#DATE#' => $now,
                    '#POSITION#' => 'администратора федерального проекта',
                    '#PROJECT_NAME#' => $projectName[$projectId]
                ]
            );
            $result = $entityDataClass::add([
                'UF_NOTIFY_DATETIME' => $now,
                'UF_NOTIFY_STATUS' => $notifyStatusEnumId,
                'UF_NOTIFY_USER' => $notifyItem['userId'],
                'UF_NOTIFY_TEXT' => $notifyText,
                'UF_NOTIFY_TYPE' => $notifyTypeId
            ]);
        }
    }
}
