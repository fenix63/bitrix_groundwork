<?php

namespace MyCompany\Notify\Event;

use Bitrix\Main\Localization\Loc;
use MyCompany\Rest\Model\Debuger;
use  MyCompany\Notify\IblockHelper;
use MyCompany\Notify\HLblockHelper;

Loc::loadMessages(__FILE__);
\Bitrix\Main\Loader::includeModule('iblock');

class IblockElement
{

    const IBLOCK_CODE_TO_WATCH = ['controlpan', 'planres'];//Контрольные точки и плановые значения результатов
    const PROPERTY_CODE_TO_CHECK = 'ACCOUNTABLE_OI';//Свойство, которое проверяем на изменение
    const HLBLOCK_NOTIFY_CODE = 'Notify';
    const PROPERTY_CODE_PROJECT = 'PROJECT_LOOKUP';

    /**
     * Обработчик события "Изменение элемента инфоблока"
     * @param \Bitrix\Main\Event $event
     */
    public static function onIblockBeforeElementUpdateHandler(&$arFields)
    {
        try {
            $iblockId = $arFields["IBLOCK_ID"];
            $iblockCode = IblockHelper::getIblockCodeById($iblockId);

            if (in_array($iblockCode, self::IBLOCK_CODE_TO_WATCH)) {
                //Изменили контрольную точку - смотрим поле "Ответственный за отчетность". Запоминаем ID, которые там были
                //для этогонадо по ID элемента вытянуть сойство из него
                $elementId = $arFields["ID"];
                $elementPropsInfo = IblockHelper::getElementProperties($iblockCode,
                    $elementId, ['CODE' => [self::PROPERTY_CODE_TO_CHECK, self::PROPERTY_CODE_PROJECT]]);
                $propertyId = $elementPropsInfo[$elementId][self::PROPERTY_CODE_TO_CHECK]['ID'];
                $projectId = (int)$elementPropsInfo[$elementId][self::PROPERTY_CODE_PROJECT]["VALUE"];
                $oldResponsibleUsers = $elementPropsInfo[$elementId][self::PROPERTY_CODE_TO_CHECK]['VALUE'];
                $newResponsibleUsersInfo = $arFields["PROPERTY_VALUES"][$propertyId];
                $newResponsibleUsers = [];
                foreach ($newResponsibleUsersInfo as $newUserItem) {
                    if (!empty($newUserItem['VALUE']))
                        $newResponsibleUsers[] = (int)$newUserItem['VALUE'];
                }

                //Пользователи, которые перестали быть ответственными
                if (!empty($oldResponsibleUsers)) {
                    $usersCancelResponsible = array_diff($oldResponsibleUsers, $newResponsibleUsers);
                    $notifyList['usersCancelResponsible']['userIdList'] = $usersCancelResponsible;
                    $notifyList['usersCancelResponsible']['messageType'] = 'CANCEL_RESPONSIBLE_USERS';
                }

                //пользователи, которые стали ответственными
                if (!empty($newResponsibleUsers)) {
                    $usersAddResponsible = array_diff($newResponsibleUsers, $oldResponsibleUsers);
                    $notifyList['usersAddResponsible']['userIdList'] = $usersAddResponsible;
                    $notifyList['usersAddResponsible']['messageType'] = 'ADD_RESPONSIBLE_USERS';
                }

                $notifyTypesInfo = HLblockHelper::getUserFieldEnumList('Notify', 'UF_NOTIFY_TYPE');
                $notifyTypeId = self::getNotifyTypeId($notifyTypesInfo, 'Уведомление');
                if (!empty($notifyList))
                    self::createNotify($notifyList, $projectId, $notifyTypeId);
            }
        } catch (\Exception $e) {
            echo 'Error: ', $e->getMessage(), "\n";
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
            foreach ($notifyItem['userIdList'] as $userId) {
                $usersIdList[] = $userId;
            }
        }
        $usersInfo = IblockHelper::getUsersInfo($usersIdList, ['NAME', 'SECOND_NAME', 'PERSONAL_GENDER']);

        $projectName = IblockHelper::getSectionNameById($projectId);
        $now = date('d.m.Y');

        foreach ($notifyList as $notifyItem) {
            foreach ($notifyItem['userIdList'] as $userId) {
                $gender = 'Уважаемый(-ая)';
                if (!empty($usersInfo[$userId]['PERSONAL_GENDER'])) {
                    $gender = $usersInfo[$userId]['PERSONAL_GENDER'] == 'M' ? 'Уважаемый' : 'Уважаемая';
                }
                $notifyText = Loc::getMessage(
                    $notifyItem['messageType'],
                    [
                        '#USER_GENDER#' => $gender,
                        '#USER_FIO#' => $usersInfo[$userId]['NAME'] . ' ' . $usersInfo[$userId]['SECOND_NAME'],
                        '#DATE#' => $now,
                        '#POSITION#' => 'ответственного исполнителя',
                        '#PROJECT_NAME#' => $projectName[$projectId]
                    ]
                );
                $result = $entityDataClass::add([
                    'UF_NOTIFY_DATETIME' => $now,
                    'UF_NOTIFY_STATUS' => $notifyStatusEnumId,
                    'UF_NOTIFY_USER' => $userId,
                    'UF_NOTIFY_TEXT' => $notifyText,
                    'UF_NOTIFY_TYPE' => $notifyTypeId
                ]);
            }
        }
    }

}
