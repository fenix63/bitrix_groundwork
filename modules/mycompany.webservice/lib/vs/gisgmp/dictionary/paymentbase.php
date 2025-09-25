<?php


namespace MyCompany\WebService\VS\Gisgmp\Dictionary;

use Bitrix\Main\Loader;
Loader::includeModule("MyCompany.rest");

use MyCompany\Rest\Helper\HlBlock;

class PaymentBase
{
    const ENTITY_NAME = 'PaymentBasis';
    const TABLE_NAME = 'gisgmp_paymentbasis';
    const SCHEMA_NAME = 'payment_base';

    /**
     * @OA\Get(
     *   tags={"Dictionary"},
     *   path="/dictionary/paymentbase/",
     *   summary="Основание платеже",
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
        $filter = Common::prepareFilter($params, $fieldCodes, self::SCHEMA_NAME);
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
