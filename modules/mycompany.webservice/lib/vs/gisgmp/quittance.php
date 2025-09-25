<?php


namespace MyCompany\WebService\VS\Gisgmp;

use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper as RestHelper;
use MyCompany\Rest\Response;


class Quittance
{
    const IBLOCK_ID = 26;
    //const IBLOCK_CODE = 'quittance';
    const IBLOCK_CODE = 'gis-gmp-Quittance';
    private array $props;//Свойства из инфоблока
    public array $items;

    /**Предоставить данные по квитанции
     * @return array
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

        if (!\CIBlockRights::userHasRightTo($iblockId, $iblockId, 'element_read')) {
            $this->items = [];
            return;
        }
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'order' => $sortCorrect,
            'select' => $select,
            'filter' => array_merge(['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'], $filter),
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
            $items[$item['ID']] = $item;
        }
        $userIdList = array_unique(array_column($items,'CREATED_BY'));
        $authorInfo = Helper::getUserInfo($userIdList);
        foreach($items as &$item){
            $item['CREATED_BY'] = $authorInfo[$item['CREATED_BY']];
        }

        if (!$isFromGrid)
            self::prepareElements($items, 'quittance');

        $this->items = $items;
    }




    public static function addItem($params): array
    {

        $props = self::prepareProps($params);

        $elementId = self::add($props, $params["quittancename"]);

        $object = new Quittance();
        $object->get(['ID' => $elementId]);

        return $object->items;
    }

    public static function add($props, $name = ''){
        $el = new \CIBlockElement;

        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(Quittance::IBLOCK_CODE),
            'PROPERTY_VALUES' => $props,
            'NAME' => $name ? $name : $props['RqID'],
            'ACTIVE' => 'Y'
        ];
        if($elementId = $el->Add($fields))
            return $elementId;
        else
            Response::createError(
                'Ошибка добавления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );
    }


    public static function updateItem(array $params, int $elementId): array
    {
        $el = new \CIBlockElement;
        $props = self::prepareProps($params);
        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(Quittance::IBLOCK_CODE),
            'NAME' => $params["quittancename"]
        ];
        $res = $el->Update($elementId, $fields);//Обновляем поля
        \CIBlockElement::SetPropertyValuesEx($elementId, false, $props);//Обновляем свойства
        if ($res) {
            //$result = ['result' => 'success'];
            $object = new Quittance();
            $object->get(['ID' => $elementId]);
        }
        else
            Response::createError(
                'Ошибка обновления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );

        return $object->items;
    }


    public static function deleteItem(int $elementId): Response
    {
        global $DB;
        $DB->StartTransaction();
        if(!\CIBlockElement::Delete($elementId))
        {
            $DB->Rollback();
            $result =  Response::createError(
                'Ошибка удаления элемента инфоблока!',
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );
        }
        else {
            $DB->Commit();
            $result = Response::createSuccess('Элемент успешно удалён!');
        }

        return $result;
    }

    public static function prepareProps(array $input): array
    {
        $preparedProps = [];
        $schema = (RestHelper\Options::getMapFromOptions('schema_quittance'));
        $schemaKeys = array_keys($schema);
        $iblockId = Helper::getIblockIdByCode(self::IBLOCK_CODE);
        $iblockProps = RestHelper\Iblock::getIblockProperties(Helper::getIblockIdByCode(self::IBLOCK_CODE));
        $enumPropertyValue = Helper::getPropertyEnumValue($iblockId, 'ISREVOKED', $input["isrevoked"]);
        foreach ($input as $propItemKey => $propItem) {
            //$preparedProps[$schema[$propItemKey]] = $propItem;
            if (in_array($propItemKey, $schemaKeys)) {
                //Проверяем тип свойства
                if ($iblockProps[$schema[$propItemKey]]['PROPERTY_TYPE'] == 'L') {
                    $preparedProps[$schema[$propItemKey]] = $enumPropertyValue;
                } else {
                    $preparedProps[$schema[$propItemKey]] = $propItem;
                }
            }
        }

        return $preparedProps;
    }

    public static function prepareElements(array &$items, string $schemaName)
    {
        $schema = array_flip(\MyCompany\Rest\Helper\Options::getMapFromOptions('schema_' . $schemaName));
        foreach ($items as &$item) {
            foreach ($item as $propCode => $property) {
                $curProperty = str_replace('PROPERTY_', '', $propCode);
                if (array_key_exists($curProperty, $schema))
                    $item[$schema[$curProperty]] = $property;

                unset($item[$propCode]);
            }
        }
    }

    public function set(array $data)
    {
        foreach($data as $dataItemKey => $dataItemValue){
            $this->props[$dataItemKey] = $dataItemValue;
        }
    }
}
