<?php

namespace MyCompany\Notify;

class IblockHelper
{
    public static function getIblockIdByCode($code)
    {
        $dbItems = \Bitrix\Iblock\IblockTable::getRow([
            'order' => ['SORT' => 'ASC'],
            'select' => ['ID'],
            'filter' => ['=CODE' => $code]
        ]);

        return $dbItems['ID'];
    }

    public static function getIblockCodeById(int $iblockId)
    {
        $dbItems = \Bitrix\Iblock\IblockTable::getList([
            'order' => ['SORT' => 'ASC'],
            'select' => ['CODE'],
            'filter' => ['=ID' => $iblockId]
        ])->fetchAll();

        return $dbItems[0]['CODE'];
    }

    public static function getElementProperties($iblockCode, $elementId, $propFilterArray = null): array
    {
        $props = [];
        $iblockId = self::getIblockIdByCode($iblockCode);
        $propFilter = [];
        if ($propFilterArray)
            $propFilter = $propFilterArray;

        \CIBlockElement::GetPropertyValuesArray(
            $props,
            $iblockId,
            ['ID' => $elementId],
            $propFilter
        );

        return $props;
    }

    public static function getElementPropertiesByFilter($iblockCode, $elementsFilter, $propFilterArray = null): array
    {
        $props = [];
        $iblockId = self::getIblockIdByCode($iblockCode);
        $propFilter = [];
        if ($propFilterArray)
            $propFilter = $propFilterArray;
        \CIBlockElement::GetPropertyValuesArray(
            $props,
            $iblockId,
            $elementsFilter,
            $propFilter
        );

        return $props;
    }

    public static function getSectionProperties($iblockCode, $sectionId, $propFilterArray = null): array
    {
        $props = [];
        $iblockId = self::getIblockIdByCode($iblockCode);
        $propFilter = [];
        if ($propFilterArray)
            $propFilter = $propFilterArray;
        $dbItems = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID', 'IBLOCK_ID', 'UF_VERIFICATION_FP'],
            'filter' => ['IBLOCK_ID' => 4]
        ]);

        return $props;
    }

    public static function getUsersInfo(array $usersIdList, array $selectFields): array
    {
        $usersInfo = [];
        $result = \Bitrix\Main\UserTable::getList([
            'select' => array_merge(['ID'], $selectFields),
            'filter' => ['ID' => $usersIdList]
        ]);

        while ($item = $result->fetch()) {
            $usersInfo[$item['ID']]['NAME'] = $item['NAME'];
            $usersInfo[$item['ID']]['SECOND_NAME'] = $item['SECOND_NAME'];
            $usersInfo[$item['ID']]['PERSONAL_GENDER'] = $item['PERSONAL_GENDER'];
        }

        return $usersInfo;
    }

    public static function getSectionNameById($sectionId)
    {
        $sectionName = [];
        $sectionQuery = \Bitrix\Iblock\SectionTable::getList([
            'filter' => [
                'ID' => $sectionId
            ],
            'select' => ['ID', 'NAME'],
        ]);

        while ($sections = $sectionQuery->fetch()) {
            $sectionName[$sections['ID']] = $sections['NAME'];
        }

        return $sectionName;
    }

    public static function getIblockElements($arSelect, $arFilter): array
    {
        $elements = [];
        $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        while ($objectResult = $res->fetch()) {
            $elements[] = $objectResult;
        }

        return $elements;
    }

    public static function getIblockElementsWithId($select, $filter): array
    {
        $elements = [];
        $res = \CIBlockElement::GetList([], $filter, false, false, $select);
        while ($objectResult = $res->fetch()) {
            $elements[$objectResult['ID']] = $objectResult;
        }

        return $elements;
    }

    public static function getPropertyType(string $iblockCode, string $propertyCode)
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\PropertyTable::getList([
            'select' => ['ID', 'NAME', 'CODE', 'IBLOCK_ID', 'PROPERTY_TYPE'],
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode],
        ]);
        if ($item = $dbItems->fetch()) {
            $propType = $item['PROPERTY_TYPE'];
        }


        return $propType;
    }

    public static function getResultNames(string $iblockCode, array $resultIdList): array
    {
        $iblockId = Iblockhelper::getIblockIdByCode($iblockCode);
        $select = ['ID', 'NAME'];
        $filter = ['IBLOCK_ID' => $iblockId, 'ID' => $resultIdList];
        $res = \CIBlockElement::GetList([], $filter, false, false, $select);
        $resultNames = [];
        while ($item = $res->fetch()) {
            $resultNames[$item['ID']] = $item['NAME'];
        }

        return $resultNames;
    }

    public static function getCheckPointNames(string $iblockCode, array $checkPointIdList): array
    {
        $iblockId = Iblockhelper::getIblockIdByCode($iblockCode);
        $select = ['ID', 'NAME'];
        $filter = ['IBLOCK_ID' => $iblockId, 'ID' => $checkPointIdList];
        $res = \CIBlockElement::GetList([], $filter, false, false, $select);
        $resultNames = [];
        while ($item = $res->fetch()) {
            $resultNames[$item['ID']] = $item['NAME'];
        }

        return $resultNames;
    }

    public static function getElementNameById(string $iblockCode, array $elementIdList): array
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['IBLOCK_ID' => $iblockId, 'ID' => $elementIdList]
        ]);

        $name = [];
        while ($item = $dbItems->fetch()) {
            $name[$item['ID']] = $item['NAME'];
        }


        return $name;
    }

    public static function getMonthNameByNumber($monthNumber)
    {
        $months = ['Январь' => '01', 'Февраль' => '02', 'Март' => '03', 'Апрель' => '04',
            'Май' => '05', 'Июнь' => '06', 'Июль' => '07',
            'Август' => '08', 'Сентябрь' => '09', 'Октябрь' => '10', 'Ноябрь' => '11', 'Декабрь' => '12'];

        return array_search($monthNumber, $months);
    }
}