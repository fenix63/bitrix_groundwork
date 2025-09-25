<?php


namespace MyCompany\WebService\VS\Gisgmp;

\Bitrix\Main\Loader::includeModule('MyCompany.rest');
use MyCompany\WebService\Helper;


class MatchEntity
{
    const IBLOCK_CODE = 'match';

    public array $items;

    /**
     * @OA\Get(
     *   tags={"Match"},
     *   path="/match/find/",
     *   summary="Найти элементы по параметрам",
     *   @OA\Parameter(
     *     name="paymentid",
     *     in="query",
     *     required=false,
     *     @OA\Schema(type="number")
     *   ),
     *   @OA\Parameter(
     *     name="chargeid",
     *     in="query",
     *     required=false,
     *     @OA\Schema(type="number")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function get(array $filter, int $limit = 1000, int $offset = 0, array $sort = [], bool $isFromGrid = false)
    {
        $iblockId = Helper::getIblockIdByCode(self::IBLOCK_CODE);
        $iblockProps = Helper::getIblockProperties($iblockId);
        foreach ($iblockProps as $propCode => $propItem) {
            $iblockProps['PROPERTY_' . $propCode] = 'PROPERTY.PROPERTY_' . $propItem['ID'];
            $iblockPropsType['PROPERTY_' . $propCode] = $propItem['PROPERTY_TYPE'];
            unset($iblockProps[$propCode]);
        }
        $iblockPropsCodesList = array_keys($iblockProps);
        \Bitrix\Iblock\IblockElementPropertyTable::setProperties($iblockId);
        $select = array_merge(['ID', 'NAME', 'IBLOCK_ID', 'SORT', 'CREATED_BY', 'DATE_CREATE', 'TIMESTAMP_X'], $iblockProps);
        $sortField = array_key_first($sort);
        $sortCorrect = $sort;
        if (in_array('PROPERTY_' . $sortField, $iblockPropsCodesList)) {
            unset($sortCorrect);
            $sortCorrect['PROPERTY_' . $sortField] = $sort[$sortField];
        }
        if (!$isFromGrid)
            $offset = 0;
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'order' => $sortCorrect,
            'select' => $select,
            'filter' => array_merge(['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'IBLOCK_SECTION_ID' => false], $filter),
            'limit' => $limit,
            'offset' => $offset,
            'count_total' => 1,
            'runtime' => [
                'PROPERTY' => [
                    'data_type' => '\Bitrix\Iblock\IblockElementProperty',
                    'reference' => ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID']
                ]
            ]
        ]);
        $this->totalItemsCount = $dbItems->getCount();
        $items = [];
        while ($item = $dbItems->fetch()) {
            $items[] = $item;
        }

        $schemaName = 'match';
        if (!$isFromGrid) {
            if (isset($schemaName)) {
                self::prepareElements($items, $schemaName);
            }
        }

        $this->items = $items;
    }

    public static function prepareElements(array &$items, string $schemaName)
    {
        $schema = array_flip(\MyCompany\Rest\Helper\Options::getMapFromOptions('schema_' . $schemaName));
        foreach ($items as $itemKey => &$item) {
            foreach ($item as $fieldCode => $fieldValue) {
                $fieldCodeShort = str_replace('PROPERTY_', '', $fieldCode);
                if (!empty($schema[$fieldCode]))
                    $item[$schema[$fieldCode]] = (int)$fieldValue;

                if (!empty($schema[$fieldCodeShort]) && $schema[$fieldCodeShort]!=null)
                    $item[$schema[$fieldCodeShort]] = $fieldValue;

                unset($item[$fieldCode]);
            }
        }
    }
}
