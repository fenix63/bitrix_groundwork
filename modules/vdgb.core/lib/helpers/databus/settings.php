<?php

namespace Vdgb\Core\Helpers\Databus;

use Vdgb\Core\Helpers\HlblockHelper;

class Settings
{
    public static function getDataBusServerURL(): string
    {
      $url = \Bitrix\Main\Config\Option::get(
           "vdgb.core",
           "databus_server_url",
           "",
           false
        );

        return $url;
    }

    public static function getSettings(string $topicName, array $ufFields): array
    {
        $settings = [];
        $settings = HlblockHelper::getHLBlockItemByFilter(
            "DatabusSettings",
            ["UF_TOPIC_NAME" => $topicName],
            $ufFields
        );

        return $settings;
    }

    public static function dataBusIsUsing(): bool
    {
        $databusOption = \Bitrix\Main\Config\Option::get(
           // ID модуля, обязательный параметр
           "vdgb.core",
           // имя параметра, обязательный параметр
           "databus_enabled",
           // возвращается значение по умолчанию, если значение не задано
           "N",
           // ID сайта, если значение параметра различно для разных сайтов
           false
        );

        if($databusOption=='Y')
            return true;
        return false;

    }
}