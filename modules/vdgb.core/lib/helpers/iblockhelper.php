<?php

namespace Vdgb\Core\Helpers;

use Vdgb\Core\Debug;

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

    public static function hasSectionByCode(string $iblockCode, string $sectionCode): bool
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID', 'NAME', 'CODE'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=CODE' => $sectionCode]
        ]);

        if($sectionCode = $dbItems->fetch())
            return true;

        return false;
    }

    public static function getSectionIdByCode(string $iblockCode, string $sectionCode): int
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=CODE' => $sectionCode]
        ]);

        $sectionId = -1;
        if($section = $dbItems->fetch()){
            $sectionId = $section['ID'];
        }

        return $sectionId;
    }

    public static function getSectionCodeById(string $iblockCode, int $sectionId): string
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=ID' => $sectionId]
        ]);

        $sectionCode = '';
        if($section = $dbItems->fetch()){
            $sectionCode = $section['CODE'];
        }

        return $sectionCode;
    }

    public static function getSectionIdListByCodeList(string $iblockCode, array $codeList): array
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=CODE' => $codeList]
        ]);

        $sectionIdList = [];
        while($section = $dbItems->fetch()){
            $sectionIdList[$section['CODE']] = $section['ID'];
        }

        return $sectionIdList;
    }


    public static function saveTreeToIblock(string $iblockCode, string $kanbanSectionCode, array $childSections)
    {
        $iblockId = self::getIblockIdByCode($iblockCode);

        $bs = new \CIBlockSection;
        $sectionFields = [
            'IBLOCK_ID' => $iblockId
        ];

        $kanbanSectionId = self::getSectionIdByCode($iblockCode, $kanbanSectionCode);
        //Debug::dbgLog($kanbanSectionId,'_kanbanSectionId_');

        $prefix = '. . ';

        //Первые 3 раздела
        foreach($childSections as $childSectionItem){

            $sectionFields['IBLOCK_SECTION_ID'] = $kanbanSectionId;
            $sectionFields['NAME'] = $prefix.$childSectionItem['Name'];
            $sectionFields['CODE'] = $childSectionItem['UID'];


            $sectionFields['ACTIVE'] = 'N';
            if(empty($childSectionItem['children']))
                $sectionFields['ACTIVE'] = 'Y';                

            //Дочерние для канбана разделы
            $addedSectionId = $bs->Add($sectionFields);


            
            if(!empty($childSectionItem['children'])){
                //Добавляем нижестоящий уровень

                $parentSectionsCodeList = array_unique(array_column($childSectionItem['children'],'ParentProjectId'));
                $parentSectionsIdList = self::getSectionIdListByCodeList('advanta_sections', $parentSectionsCodeList);
                Debug::dbgLog($parentSectionsIdList,'_parentSectionsIdList_');

                //Вложенные разделы
                foreach($childSectionItem['children'] as $childItem){
                    //$parentProjectId = self::getSectionIdByCode('advanta_sections',$childItem['ParentProjectId']);
                    $parentProjectId = $parentSectionsIdList[$childItem['ParentProjectId']];//Id родительского раздела

                    //Debug::dbgLog($childItem['Name'].': '.$parentProjectId,'_parentProjectId_');
                    //Debug::dbgLog($parentProjectId,'_parentProjectId_');

                    $sectionFields['IBLOCK_SECTION_ID'] = $parentProjectId;//TODO: переписать так, чтоб было внен цикла
                    $sectionFields['NAME'] = $prefix.$prefix.$childItem['Name'];
                    $sectionFields['CODE'] = $childItem['UID'];

                    $sectionFields['ACTIVE'] = 'N';
                    if(empty($childItem['children']))
                        $sectionFields['ACTIVE'] = 'Y';

                    Debug::dbgLog($sectionFields,'_sectionFields_');

                    $newSectionId = $bs->Add($sectionFields);

                    
                    if(!empty($childItem['children'])){
                        $parentSectionsCodeList_2 = array_unique(array_column($childItem['children'],'ParentProjectId'));
                        $parentSectionsIdList_2 = self::getSectionIdListByCodeList('advanta_sections', $parentSectionsCodeList_2);

                        foreach($childItem['children'] as $childItemLevel_2){
                            $parentProjectId_level2 = $parentSectionsIdList_2[$childItemLevel_2['ParentProjectId']];
                            //Debug::dbgLog($childItemLevel_2['Name'].': '.$parentProjectId_level2,'_ParentProjectLevel_2_');

                            $sectionFields['IBLOCK_SECTION_ID'] = $parentProjectId_level2;
                            $sectionFields['NAME'] = $prefix.$prefix.$prefix.$childItemLevel_2['Name'];
                            $sectionFields['CODE'] = $childItemLevel_2['UID'];

                            $sectionFields['ACTIVE'] = 'N';
                            if(empty($childItemLevel_2['children']))
                                $sectionFields['ACTIVE'] = 'Y';

                            $level2SectionId = $bs->Add($sectionFields);

                            if(!empty($childItemLevel_2['children'])){
                                $parentSectionsCodeList_Level_3 = array_unique(array_column($childItemLevel_2['children'],'ParentProjectId'));
                                $parentSectionsIdList_Level_3 = self::getSectionIdListByCodeList('advanta_sections', $parentSectionsCodeList_Level_3);

                                foreach($childItemLevel_2['children'] as $childItemLevel_3){
                                    $parentProjectId_Level_3 = $parentSectionsIdList_Level_3[$childItemLevel_3['ParentProjectId']];
                                    $sectionFields['IBLOCK_SECTION_ID'] = $parentProjectId_Level_3;
                                    $sectionFields['NAME'] = $prefix.$prefix.$prefix.$prefix.$childItemLevel_3['Name'];
                                    $sectionFields['CODE'] = $childItemLevel_3['UID'];

                                    $sectionFields['ACTIVE'] = 'N';
                                    if(empty($childItemLevel_3['children']))
                                        $sectionFields['ACTIVE'] = 'Y';

                                    $level3SectionId = $bs->Add($sectionFields);
                                }
                                
                            }

                        }
                    }



                }
                
            }

            
            
        }
    }
}