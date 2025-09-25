<?php


namespace MyCompany\WebService\VS\Gisgmp\Dictionary;

use MyCompany\Rest\Helper\HlBlock;

class IdentityCard
{
    const ENTITY_NAME = 'IdentityCard';
    const TABLE_NAME = 'gisgmp_identitycard';
    const SCHEMA_NAME = 'identity_card';

    /**
     * @OA\Get(
     *   tags={"Dictionary"},
     *   path="/dictionary/identitycard/",
     *   summary="Удостоверяющий документ",
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
        Common::prepareResult($items,self::SCHEMA_NAME);

        return $items;
    }

    public static function getByFilter(array $filter): array
    {
        $hlblockId = Hlblock::getHLIdByEntityName(self::ENTITY_NAME);
        $hlblockFields = Hlblock::getHlUserFields($hlblockId);
        $fieldCodes = Common::getFieldsCode($hlblockFields);
        $items = HlBlock::getHlItems(self::TABLE_NAME, array_merge(['ID', 'UF_XML_ID'], $fieldCodes), $filter);
        Common::prepareResult($items,self::SCHEMA_NAME);

        return $items;
    }
}
