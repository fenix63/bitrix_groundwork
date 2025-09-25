<?php


namespace MyCompany\WebService\VS\Gisgmp\Dictionary;

use MyCompany\Rest\Helper;

class Common
{
    public static function getFieldsCode(array $fields): array
    {
        $result = [];
        foreach ($fields as $fieldItem) {
            $result[] = $fieldItem["FIELD_NAME"];
        }

        return $result;
    }

    public static function prepareFilter(array $params, array $fields, string $schemaName): array
    {
        $filter = [];
        $schema = Helper\Options::getMapFromOptions('schema_' . $schemaName);
        foreach ($params as $paramItemKey => $paramItem) {
            if (array_key_exists($paramItemKey, $schema)) {
                $filter[$schema[$paramItemKey]] = $paramItem;
            }
        }

        return $filter;
    }

    public static function prepareResult(array &$items, string $schemaName)
    {
        $schema = array_flip(Helper\Options::getMapFromOptions('schema_'.$schemaName));

        foreach ($items as &$item) {
            foreach ($item as $fieldCode => $fieldValue) {
                if (!empty($schema[$fieldCode]))
                    $item[$schema[$fieldCode]] = $fieldValue;
                unset($item[$fieldCode]);
            }
        }

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            if (!empty($item['code']) && is_numeric($item['code']))
                $item['code'] = (int)$item['code'];
        }
    }
}
