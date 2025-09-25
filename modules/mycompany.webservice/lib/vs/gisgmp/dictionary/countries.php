<?php

namespace MyCompany\WebService\VS\Gisgmp\Dictionary;

use MyCompany\Rest\Helper\HlBlock;
use MyCompany\Rest\Helper;

class Countries
{
    const ENTITY_NAME = 'OKSM';
    const TABLE_NAME = 'gisgmp_oksm';
    const SCHEMA_NAME = 'countries_dictionary';

    /**
     * @OA\Get(
     *   tags={"Dictionary"},
     *   path="/dictionary/countries/",
     *   summary="Получить информацию по стране",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function get(array $params): array
    {
        $hlblockId = Hlblock::getHLIdByEntityName(self::ENTITY_NAME);
        $hlblockFields = Hlblock::getHlUserFields($hlblockId);
        $fieldCodes = Common::getFieldsCode($hlblockFields);
        $filter = Common::prepareFilter($params,$fieldCodes,self::SCHEMA_NAME);
        $items = HlBlock::getHlItems(self::TABLE_NAME, array_merge(['ID', 'UF_XML_ID'], $fieldCodes), $filter);
        Common::prepareResult($items, self::SCHEMA_NAME);

        return $items;
    }

    public static function getByFilter(array $filter): array
    {
        $hlblockId = Hlblock::getHLIdByEntityName(self::ENTITY_NAME);
        $hlblockFields = Hlblock::getHlUserFields($hlblockId);
        $fieldCodes = Common::getFieldsCode($hlblockFields);
        $items = HlBlock::getHlItems(self::TABLE_NAME, array_merge(['ID', 'UF_XML_ID'], $fieldCodes), $filter);
        Common::prepareResult($items, self::SCHEMA_NAME);

        return $items;
    }
}
