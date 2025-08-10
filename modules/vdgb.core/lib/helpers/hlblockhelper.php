<?php

namespace Vdgb\Core\Helpers;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Vdgb\Core\Debug;

class HlblockHelper
{
    public static function getHLBlockItemByFilter(string $HLBlockName, array $filter, array $selectFields = []): array
    {
        
        $entity = HL\HighloadBlockTable::compileEntity($HLBlockName);
        $entityDataClass = $entity->getDataClass();
        $select = $selectFields;
        if (empty($select)) {
            $select = ["*"];
        }

        $rsData = $entityDataClass::getList([
            "select" => $select,
            "filter" => $filter
        ]);

        $hlElementItem = [];
        if ($arData = $rsData->Fetch()) {
            $hlElementItem = $arData;
        }

        return $hlElementItem;
    }

    public static function test(): array
    {
        return [];
    }
}