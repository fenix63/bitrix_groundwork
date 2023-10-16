<?php


namespace MyCompany\Notify\Helpers;

use \Bitrix\Main\Loader;

Loader::includeModule('iblock');

class OptionsHelper
{
    public static function getIblockTypes(): array
    {
        $res = \Bitrix\Iblock\TypeTable::getList([
            'filter' => [
                'LANG.LANGUAGE_ID' => 'ru',
            ],
            'runtime' => [
                'LANG' => [
                    'data_type' => '\Bitrix\Iblock\TypeLanguageTable',
                    'reference' => [
                        '=this.ID' => 'ref.IBLOCK_TYPE_ID',
                    ]
                ]

            ],
            'select' => ['ID', 'LANG_NAME' => 'LANG.NAME']
        ]);
        $iblockTypes = [];
        while ($row = $res->fetch()) {
            $iblockTypes[$row['ID']] = $row['LANG_NAME'];
        }

        return $iblockTypes;
    }

    public static function getIblockList(): array
    {
        $options = $groups = [];
        $iblockTypes = self::getIblockTypes();

        foreach ($iblockTypes as $iblockTypeId => $iblockTypeName) {
            $groups[$iblockTypeId] = ['name' => $iblockTypeName, 'items' => []];
        }

        $iterator = \Bitrix\Iblock\IblockTable::getList([
            'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
            'filter' => [
                'ACTIVE' => 'Y',
                'IBLOCK_TYPE_ID' => array_keys($groups),
            ]
        ]);
        while ($row = $iterator->fetch()) {
            $value = $row['ID'];
            $name = '[' . $row['ID'] . '] ' . $row['NAME'];

            $options[$value] = $name;
            $groups[$row['IBLOCK_TYPE_ID']]['items'][$value] = $name;
        }

        $field['Options'] = $options;
        $field['Settings'] = ['Groups' => $groups];

        return $field;
    }

    public static function getIblocks(): array
    {
        $iblocks = [];
        $rsIblock = \Bitrix\Iblock\IblockTable::getList([
            'order' => ['NAME' => 'ASC'],
            'select' => [
                'ID',
                'NAME'
            ],
            'filter' => [
                'ACTIVE' => 'Y'
            ],
        ]);
        while ($iblock = $rsIblock->fetch()) {
            $iblocks[$iblock['ID']] = $iblock['NAME'] . ' [' . $iblock['ID'] . ']';
        }

        return $iblocks;
    }

    public static function getPropsForIblocks(int $iblockIdList): array
    {
        $props = [];
        $dbItems = \Bitrix\Iblock\PropertyTable::getList([
            'select' => ['ID', 'NAME', 'IBLOCK_ID', 'CODE'],
            'filter' => ['IBLOCK_ID' => $iblockIdList],
        ]);


        while ($item = $dbItems->fetch()) {
            $props[$item['ID']] = $item['NAME'] . ' [' . $item['ID'] . ']';
        }

        return $props;
    }
}
