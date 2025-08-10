<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

global $APPLICATION;

//Получаем объект контекста
$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();
$moduleId = $request->get('mid');

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule($moduleId);

$showRightsTab = true;

/**
 * TODO
 * Страница настроек модуля в разработке
 */

 $aTabs = [
    [
        "DIV" => "tab1",//Ячейки с ключами DIV - идентификатор ячейки
        "TAB" => "Интеграция с Адвантой",//TAB - имя ячейки, которое подписано на самой вкладке
        "TITLE" => "Настройки интеграции",
        //"CONTENT"=>"Содержимое вкладки",
        "OPTIONS"=>[//OPTIONS - массив опций настроек модуля, которые имеются на данной вкладке
            'Настройки прямой интеграции с Адвантой',
            [
                'advanta_user_login',//name опции в форме
                'Имя пользователя в Адванте',//Подпись опции в форме
                '',//Значение опции по-умолчанию
                ['text', 20]
            ],
            [
                'advanta_user_password',//name опции в форме
                'Пароль пользователя в Адванте',//Подпись опции в форме
                '',//Значение опции по-умолчанию
                ['text', 20]
            ],
            [
                'advanta_server_url',//name опции в форме
                'URL сервера адванты',//Подпись опции в форме
                '',//Значение опции по-умолчанию
                ['text', 80]
            ],
        ]
    ]
];


// проверяем текущий POST запрос и сохраняем выбранные пользователем настройки
if ($request->isPost() && check_bitrix_sessid()) {
    // цикл по вкладкам
    foreach ($aTabs as $aTab) {
        // цикл по заполненым пользователем данным
        foreach ($aTab["OPTIONS"] as $arOption) {
            // если это название секции, переходим к следующий итерации цикла
            if (!is_array($arOption)) {
                continue;
            }
            // проверяем POST запрос, если инициатором выступила кнопка с name="Update" сохраняем введенные настройки в базу данных
            if ($request["Update"]) {
                // получаем в переменную $optionValue введенные пользователем данные
                $optionValue = $request->getPost($arOption[0]);
                // метод getPost() не работает с input типа checkbox, для работы сделал этот костыль
                if ($arOption[0] == "hmarketing_checkbox") {
                    if ($optionValue == "") {
                        $optionValue = "N";
                    }
                }
                // устанавливаем выбранные значения параметров и сохраняем в базу данных, хранить можем только текст, значит если приходит массив, то разбиваем его через запятую, если не массив сохраняем как есть
                Option::set($moduleId, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }
            // проверяем POST запрос, если инициатором выступила кнопка с name="default" сохраняем дефолтные настройки в базу данных 
            if ($request["default"]) {
                // устанавливаем дефолтные значения параметров и сохраняем в базу данных
                Option::set($moduleId, $arOption[0], $arOption[2]);
            }
        }
    }
}

// отрисовываем форму, для этого создаем новый экземпляр класса CAdminTabControl, куда и передаём массив с настройками
$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

// отображаем заголовки закладок
$tabControl->Begin();
?>
<form action="<? echo ($APPLICATION->GetCurPage()); ?>?mid=<? echo ($moduleId); ?>&lang=<? echo (LANG); ?>" method="post">
    <? foreach ($aTabs as $aTab) {
        if ($aTab["OPTIONS"]) {
            // завершает предыдущую закладку, если она есть, начинает следующую
            $tabControl->BeginNextTab();
            // отрисовываем форму из массива
            __AdmSettingsDrawList($moduleId, $aTab["OPTIONS"]);
        }
    }
    // завершает предыдущую закладку, если она есть, начинает следующую
    $tabControl->BeginNextTab();
    // выводим форму управления правами в настройках текущего модуля
    //require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php";
    // подключаем кнопки отправки формы
    $tabControl->Buttons();
    // выводим скрытый input с идентификатором сессии
    echo (bitrix_sessid_post());
    // выводим стандартные кнопки отправки формы
    ?>
    <input class="adm-btn-save" type="submit" name="Update" value="Применить" />
    <input type="submit" name="default" value="По умолчанию" />
</form>
<?
// обозначаем конец отрисовки формы
$tabControl->End();


/*
//Вывод вкладок
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();?>
<form method="post" action='<?=$request->getRequestUri() ?>'>
    <? foreach ($aTabs as $aTab){
        if ($aTab['OPTIONS']){
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($moduleId, $aTab['OPTIONS']);
        }
    }

    $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="Сохранить" />
    <input type="reset" name="reset" value="Сбросить" />
    <?= bitrix_sessid_post(); ?>

</form>

<?$tabControl->End();*/?>

