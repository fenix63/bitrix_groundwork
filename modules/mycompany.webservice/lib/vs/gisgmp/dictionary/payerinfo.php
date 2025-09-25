<?php


namespace MyCompany\WebService\VS\Gisgmp\Dictionary;

use MyCompany\Rest\Helper\HlBlock;
use MyCompany\WebService\Helper;
use \MyCompany\Rest\Helper\Options;

class PayerInfo
{
    const ENTITY_NAME = 'PayerInfo';
    const TABLE_NAME = 'gisgmp_payerinfo';
    const SCHEMA_NAME = 'payer_status';
    const PAYER_STATUS_IBLOCK_CODE = 'gis-gmp-payer-status';
    const PAYER_STATUS_SCHEMA_FROM_IBLOCK = 'payer_status_iblock';

    /**
     * @OA\Get(
     *   tags={"Dictionary"},
     *   path="/dictionary/payerinfo/",
     *   summary="Статус плательщика",
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

    public static function getElementsFromIblock(array $params):array
    {
        $iblockId = Helper::getIblockIdByCode(self::PAYER_STATUS_IBLOCK_CODE);
        $schema = array_flip(\MyCompany\Rest\Helper\Options::getMapFromOptions('schema_payer_status_iblock'));
        $select = array_keys($schema);
        $select[] = 'DETAIL_TEXT';
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'select' => $select,
            'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y']
        ]);
        $items = [];
        while ($item = $dbItems->fetch()) {
            $items[] = $item;
        }

        $items = self::prepareElements($items, $schema);

        return $items;
    }

    public static function prepareElements(array $items, array $schema): array
    {
        $preparedItems = [];
        foreach ($items as $itemKey => $item) {
            foreach ($schema as $propCodeBtx => $propCodeFront) {
                $preparedItems[$itemKey][$propCodeFront] = $item[$propCodeBtx];
            }
        }

        foreach ($preparedItems as $key => &$preparedItem) {
            $preparedItem['id'] = (int)$preparedItem['id'];
        }

        return $preparedItems;
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
