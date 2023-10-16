<?php

/*if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
*/

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use MyCompany\Rest\Model\Debuger;
use MyCompany\Notify\Helpers\OptionsHelper;

global $APPLICATION;
//Получаем объект контекста
$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();
$moduleId = $request->get('mid');

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule($moduleId);

$showRightsTab = true;
//Опции модуля
$iblockList = OptionsHelper::getIblocks();
$iblockIdList = array_keys($iblockList);

/**
 * TODO
 * Страница настроек модуля в разработке
 */
$aTabs = [
    [
        'DIV' => 'general',
        'TAB' => Loc::getMessage('MYCOMPANY_NOTIFY_OPTIONS_SETTINGS'),
        'TITLE' => Loc::getMessage('MYCOMPANY_NOTIFY_OPTIONS_SETTINGS'),
        'OPTIONS' => [
            'Связи между инфоблоками и свойствами',
            [
                'iblockId',//name контрола, например инпута
                Loc::getMessage('MYCOMPANY_NOTIFY_OPTIONS_IBLOCK'),
                '',//значение по-умолчанию
                ['selectbox', $iblockList]
            ],
        ]
    ]
];

//$iblockProps = OptionsHelper::getPropsForIblocks($iblockIdList);
foreach ($aTabs as &$tab) {
    foreach ($tab['OPTIONS'] as $option) {
        //Если выбран инфоблок, то для выбранного инфоблока надо отобразить свойства
        if (is_array($option)) {
            $optionName = $option[0];
            $optionValue = Option::get($moduleId, $optionName);
            $iblockProps[] = 'Выберите свойство';
            $iblockProps = array_merge($iblockProps, OptionsHelper::getPropsForIblocks($optionValue));
            $tab['OPTIONS'][] = [
                'iblock_' . $optionValue . '_property',
                'Выберите свойство инфоблока для фильтрации элементов',
                '',
                ['selectbox', $iblockProps]
            ];
            //Получаем ID выбранной опции для свойств
            //$selectedPropertyId = Option::get($moduleId, 'iblock_' . $optionValue . '_property');
            //Смотрим, что выбрано в опции свойств, и предоставляем возможность задать условие для выбранного свойства
            $tab['OPTIONS'][] = [
                'iblock_' . $optionValue . '_condition',
                'Задайте условие для фильтрации по этому свойству',
                '',
                ['selectbox', [0 => 'Выберите условие', 1 => 'Равенство', 2 => 'Неравенство']]
            ];
            //Свойство, откуда брать пользователей для рассылки
            $tab['OPTIONS'][] = [
                'users_iblock_' . $optionValue . '_prop_',
                'Выберите свойство, где брать пользователей для рассылки',
                '',
                ['selectbox', $iblockProps]
            ];

        }
    }
}


$test = '123';

if ($request->isPost() && $request['save'] && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) {
        foreach ($aTab['OPTIONS'] as $option) {
//            Debuger::dbgLog('option:', '_MODULE_OPTION');
//            Debuger::dbgLog($option[0], '_MODULE_OPTION');

            $optionValue = $request->getPost($option[0]);
//            Debuger::dbgLog('option value:', '_MODULE_OPTION');
//            Debuger::dbgLog($optionValue, '_MODULE_OPTION');


            //Сохраняем опцию модуля
            Option::set($moduleId, $option[0], $optionValue);
        }
    }

    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . $moduleId . '&lang=' . LANG);
}

$tabControl = new \CAdminTabControl('tabControl', $aTabs);
$tabControl->Begin();
?>
<form method='post' action='<?= $request->getRequestUri() ?>'>
    <? foreach ($aTabs as $aTab) {
        if ($aTab['OPTIONS']) {
            //Завершение предыдущей закладки, если она есть, и начало новой.
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($moduleId, $aTab['OPTIONS']);
        }
    } ?>


    <?
    $tabControl->Buttons([
        'btnSave' => true,
        'btnApply' => false,
    ]);
    ?>

    <?= bitrix_sessid_post(); ?>
</form>
