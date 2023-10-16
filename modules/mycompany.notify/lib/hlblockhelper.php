<?php

namespace MyCompany\Notify;

use Bitrix\Main\Loader;
use MyCompany\Rest\Model\Debuger;
use Bitrix\Highloadblock as HL;
use MyCompany\Notify\IblockHelper;

Loader::includeModule('highloadblock');
Loader::includeModule('mycompany.notify');

class HLblockHelper
{
    const MODULE_ID = 'mycompany.notify';

    public static function getHLblockIdByCode(string $code): int
    {
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $code]
        ])->fetch();
        if (!$hlblock) {
            throw new \Exception('Error get HL block');
        }
        $hlblockId = $hlblock['ID'];
        return (int)$hlblockId;
    }

    /**
     *
     * @param string $hlBlockCode
     * @param int $enumListItemValueId
     * @throws \Exception
     */
    public static function getEnumListValueById(int $enumListItemValueId): string
    {
        $dbItems = \CUserFieldEnum::GetList([], ['ID' => $enumListItemValueId]);
        $enumItemValue = '';
        while ($item = $dbItems->fetch()) {
            $enumItemValue = $item['VALUE'];
        }

        return $enumItemValue;
    }

    public static function getEntityDataClass(int $HlBlockId): string
    {
        if (empty($HlBlockId) || $HlBlockId < 1) {
            return false;
        }
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($HlBlockId)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();
        return $entityDataClass;
    }

    public static function getUserFieldEnumId(string $tableName, string $fieldName, string $fieldValue): int
    {
        $hlBlockId = self::getHLblockIdByCode($tableName);
        $dbUserFields = \Bitrix\Main\UserFieldTable::getList([
            'filter' => ['ENTITY_ID' => 'HLBLOCK_' . $hlBlockId, 'FIELD_NAME' => $fieldName]
        ]);
        $enumId = null;
        if ($arUserField = $dbUserFields->fetch()) {
            if ($arUserField["USER_TYPE_ID"] == 'enumeration') {
                $userFieldId = $arUserField['ID'];
            }
        }
        if (!empty($userFieldId)) {
            $dbEnums = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => $userFieldId, 'VALUE' => $fieldValue]);
            if ($arEnum = $dbEnums->GetNext()) {
                $enumId = (int)$arEnum['ID'];
            }
        }
        if (empty($enumId))
            throw new \Exception('Пользовательское свойство ' . $fieldName . ' не найдено');

        return $enumId;
    }

    public static function getUserFieldEnumValue(string $tableName, string $fieldName, int $enumValueId)
    {
        $hlBlockId = self::getHLblockIdByCode($tableName);
        $dbUserFields = \Bitrix\Main\UserFieldTable::getList([
            'filter' => ['ENTITY_ID' => 'HLBLOCK_' . $hlBlockId, 'FIELD_NAME' => $fieldName]
        ]);
        $enumValue = '';
        if ($arUserField = $dbUserFields->fetch()) {
            if ($arUserField["USER_TYPE_ID"] == 'enumeration') {
                $userFieldId = $arUserField['ID'];
            }
        }
        if (!empty($userFieldId)) {
            $dbEnums = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => $userFieldId, 'ID' => $enumValueId]);
            if ($arEnum = $dbEnums->GetNext()) {
                $enumValue = $arEnum['VALUE'];
            }
        }
        if (empty($enumValue))
            throw new \Exception('Пользовательское свойство ' . $enumValueId . ' не найдено');

        return $enumValue;
    }

    public static function getUserFieldEnumList($tableName, $fieldName): array
    {
        $hlBlockId = self::getHLblockIdByCode($tableName);
        $dbUserFields = \Bitrix\Main\UserFieldTable::getList([
            'filter' => ['ENTITY_ID' => 'HLBLOCK_' . $hlBlockId, 'FIELD_NAME' => $fieldName]
        ]);
        $enumValues = [];
        $enumIdList = [];
        while ($arUserField = $dbUserFields->fetch()) {
            if ($arUserField["USER_TYPE_ID"] == 'enumeration') {
                $enumIdList[] = $arUserField['ID'];
            }
        }
        if (!empty($enumIdList)) {
            $dbEnums = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => $enumIdList]);
            while ($arEnum = $dbEnums->GetNext()) {
                $enumValues[$arEnum['ID']]['VALUE'] = $arEnum['VALUE'];
                $enumValues[$arEnum['ID']]['XML_ID'] = $arEnum['XML_ID'];
            }
        }


        return $enumValues;
    }

    public static function getHLBlockItem(string $HLBlockName, int $HLElementId, string $selectField = null): array
    {
        $entity = HL\HighloadBlockTable::compileEntity($HLBlockName);
        $entityDataClass = $entity->getDataClass();
        $select[] = $selectField;
        if (empty($select)) {
            $select = ["*"];
        }
        $rsData = $entityDataClass::getList([
            "select" => $select,
            "filter" => ['ID' => $HLElementId]
        ]);

        $hlElementItem = [];
        if ($arData = $rsData->Fetch()) {
            $hlElementItem = $arData;
        }

        return $hlElementItem;
    }

    public static function getSectionUserFieldId(string $iblockCode, string $fieldName): int
    {
        $iblockId = IblockHelper::getIblockIdByCode($iblockCode);
        $dbUserFields = \Bitrix\Main\UserFieldTable::getList([
            'select' => ['*'],
            'filter' => ['ENTITY_ID' => 'IBLOCK_' . $iblockId . '_SECTION', 'FIELD_NAME' => $fieldName]
        ]);
        if ($item = $dbUserFields->fetch()) {
            $userFieldId = (int)$item['ID'];
        }
        if (empty($userFieldId))
            throw new \Exception('Пользовательское поле ' . $fieldName . ' не найдено');

        return $userFieldId;
    }

    public static function getUserFieldValue(string $HLBlockCode, int $HLBlockElementId, string $userFieldCode)
    {
        $HLBlockItemAdditionUsers = self::getHLBlockItem($HLBlockCode, $HLBlockElementId, $userFieldCode);
        return $HLBlockItemAdditionUsers[$userFieldCode];
    }

    public static function getSectionUserFieldValueById(string $iblockCode, int $sectionId, string $userFieldCode): array
    {
        $iblockId = IblockHelper::getIblockIdByCode($iblockCode);
        $select = ['ID', 'IBLOCK_ID', $userFieldCode];
        $filter = ['IBLOCK_ID' => $iblockId, 'ID' => $sectionId];
        $dbList = \CIBlockSection::GetList([], $filter, false, $select, false);
        $result = [];
        while ($item = $dbList->fetch()) {
            $result[] = $item[$userFieldCode];
        }

        return $result;
    }


}