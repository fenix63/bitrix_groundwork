<?php


namespace MyCompany\WebService\VS\Gisgmp;


use MyCompany\WebService\Helper;
use MyCompany\Rest\Helper as RestHelper;
use MyCompany\Rest\Response;

class ExportChargeRequestList
{
    const IBLOCK_CODE = 'gis-gmp-export-charges-request';
    public array $items;


    public function get(array $filter, int $limit = 1000, int $offset = 0, array $sort = [])
    {
        $iblockId = Helper::getIblockIdByCode(self::IBLOCK_CODE);
        $iblockProps = Helper::getIblockProperties($iblockId);
        foreach ($iblockProps as $propCode => $propItem) {
            $iblockProps['PROPERTY_' . $propCode] = 'PROPERTY.PROPERTY_' . $propItem['ID'];
            $iblockPropsType['PROPERTY_' . $propCode] = $propItem['PROPERTY_TYPE'];
            unset($iblockProps[$propCode]);
        }

        \Bitrix\Iblock\IblockElementPropertyTable::setProperties($iblockId);
        $select = array_merge(['ID', 'NAME', 'IBLOCK_ID', 'SORT', 'CREATED_BY', 'DATE_CREATE', 'TIMESTAMP_X'], $iblockProps);
        if ($offset == 0)
            $offset = 1;
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'order' => $sort,
            'select' => $select,
            'filter' => array_merge(['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'], $filter),
            'limit' => $limit,
            'offset' => $limit * ($offset - 1),
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
        foreach($items as &$item){
            $item['DATE_CREATE'] = $item["DATE_CREATE"]->toString();
            $item['TIMESTAMP_X'] = $item["TIMESTAMP_X"]->toString();
        }
        $userIdList = array_unique(array_column($items,'CREATED_BY'));
        $authorInfo = Helper::getUserInfo($userIdList);
        foreach($items as &$item){
            $item['CREATED_BY_ID'] = $item['CREATED_BY'];
            $item['CREATED_BY'] = $authorInfo[$item['CREATED_BY']];
        }

        $this->items = $items;
    }

    public static function addItem($params): array
    {
        $el = new \CIBlockElement;
        $props = self::prepareProps($params);
        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(ExportChargeRequestList::IBLOCK_CODE),
            'PROPERTY_VALUES' => $props,
            'NAME' => $params["name"],//Название элемента инфоблока
            'ACTIVE' => 'Y'
        ];
        if($elementId = $el->Add($fields))
            $result = ['result' => 'success'];
        else
            Response::createError(
                'Ошибка добавления элемента инфоблока: ' . $el->LAST_ERROR,
                Response::ERROR_NOT_FOUND,
                Response::STATUS_WRONG_REQUEST
            );

        $object = new ExportChargeRequestList();
        $object->get(['ID' => $elementId]);

        return $object->items;
    }

    public static function prepareProps(array $input): array
    {
        $preparedProps = [];
        $schema = (RestHelper\Options::getMapFromOptions('schema_exportchargeresponselist'));
        foreach ($input as $propItemKey => $propItem) {
            $preparedProps[$schema[$propItemKey]] = $propItem;
        }

        return $preparedProps;
    }

    public static function updateItem(array $params, int $elementId): array
    {
        $el = new \CIBlockElement;
        $props = self::prepareProps($params);
        $fields = [
            'IBLOCK_ID' => RestHelper\Iblock::getIblockIdByCode(ExportChargeRequestList::IBLOCK_CODE),
            'NAME' => $params["name"]
        ];
        $res = $el->Update($elementId, $fields);//Обновляем поля
        \CIBlockElement::SetPropertyValuesEx($elementId, false, $props);//Обновляем свойства
        if ($res) {
            $object = new ExportChargeRequestList();
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
}
