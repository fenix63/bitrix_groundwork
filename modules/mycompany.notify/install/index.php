<?php

use Bitrix\Main\Loader;

Loader::includeModule('mycompany.rest');


use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ModuleManager,
    Bitrix\Main\EventManager,
    Bitrix\Main\Application,
    Bitrix\Highloadblock\HighloadBlockTable,
    MyCompany\Rest\Model\Debuger;


Loc::loadMessages(__FILE__);

class Mycompany_notify extends \CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'mycompany.notify';
        $this->setVersionData();

        $this->MODULE_NAME = Loc::getMessage("MYCOMPANY_NOTIFY_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("MYCOMPANY_NOTIFY_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("MYCOMPANY_NOTIFY_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("MYCOMPANY_NOTIFY_PARTNER_URI");

        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }

    private function setVersionData()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    }

    public function doInstall()
    {
        //Главный модуль, миграции, и rest проверяем, установлены ли они
        if (!$this->isRequiredModulesInstalled()) {
            return false;
        }
        $this->installFiles();
        $this->installDB();
        $this->installEvents();

        return true;
    }

    public function doUninstall()
    {
        $this->unInstallDB();
        $this->unInstallFiles();
        $this->unInstallEvents();

        return true;
    }

    public function unInstallDB()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);

        Loader::includeModule('sprint.migration');

        $versionManager = new \Sprint\Migration\VersionManager('version');
        //$versionManager->startMigration('AddHLBlockNotifyRule20230904130417', 'down', [], false, '');
        $versionManager->startMigration('AddHLBlockNotifyRule20230913102542', 'down', [], false, '');
        //$versionManager->startMigration('AddHLBlockNotify20230904142034', 'down', [], false, '');
        $versionManager->startMigration('AddHLBlockNotify20230913102653', 'down', [], false, '');

        return true;
    }

    //Регистрируем обработчики событий
    public function installEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler('iblock', 'OnBeforeIBlockElementUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockElement', 'onIblockBeforeElementUpdateHandler');

        $eventManager->registerEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockSection', 'onIblockBeforeSectionChangeHandler');

        $eventManager->registerEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockElement', 'onIblockElementAfterUpdateHandler');




        $handlers[] = EventManager::getInstance()->findEventHandlers("iblock", "OnBeforeIBlockElementUpdate");
        $handlers[] = EventManager::getInstance()->findEventHandlers("iblock", "OnBeforeIBlockSectionUpdate");
        Debuger::dbgLog('events:', '_moduleEvents');
        Debuger::dbgLog($handlers, '_moduleEvents');

        return true;
    }

    //Удаляем регистрационную запись обработчика события
    public function unInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockElementUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockElement', 'onIblockElementChangeHandler');
        $eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockSection', 'onIblockBeforeSectionChangeHandler');

        return true;
    }

    /**
     * Проверка на обязательные установленные модули
     *
     * @return bool
     */
    private function isRequiredModulesInstalled()
    {
        /** @var $APPLICATION CMain */
        global $APPLICATION;

        foreach ($this->getRequiredModules() as $moduleId => $version) {
            if (!ModuleManager::isModuleInstalled($moduleId)) {
                $APPLICATION->ThrowException(
                    Loc::GetMessage('MYCOMPANY_NOTIFY_REQUIRED_MODULE_ERROR', ['#MODULE#' => $moduleId])
                );

                return false;
            } elseif ($version !== '*' && !CheckVersion(ModuleManager::getVersion($moduleId), $version)) {
                $APPLICATION->ThrowException(
                    Loc::GetMessage(
                        'MYCOMPANY_NOTIFY_MODULE_VERSION_ERROR',
                        ['#MODULE#' => $moduleId, '#VERSION#' => $version]
                    )
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Обязательные модули
     *
     * @return string[]
     */
    private function getRequiredModules(): array
    {
        /**
         * формат: [<moduleId> => <version>, ...]
         *
         * version = * - любая версия
         */
        return [
            'main' => '18.5.180',
            'sprint.migration' => '4.1.2',
            'mycompany.rest' => '1.0.0',
        ];
    }

    public function installFiles()
    {
        CopyDirFiles(
            __DIR__ . '/migrations/',
            Application::getDocumentRoot() . '/local/php_interface/migrations/',
            true,
            true
        );

        return true;
    }

    public function unInstallFiles()
    {
        DeleteDirFiles(
            __DIR__ . '/migrations/',
            Application::getDocumentRoot() . '/local/php_interface/migrations/'
        );

        return true;
    }

    public function installDB()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule('sprint.migration');

        $versionManager = new \Sprint\Migration\VersionManager('version');
        //$versionManager->startMigration('AddHLBlockNotifyRule20230904130417', 'up', [], false, '');
        $versionManager->startMigration('AddHLBlockNotifyRule20230913102542', 'up', [], false, '');
        //$versionManager->startMigration('AddHLBlockNotify20230904142034', 'up', [], false, '');
        $versionManager->startMigration('AddHLBlockNotify20230913102653', 'up', [], false, '');

        return true;
    }
}