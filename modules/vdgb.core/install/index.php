<?php

use Bitrix\Main\Loader;

//Loader::includeModule('mycompany.rest');


use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ModuleManager,
    Bitrix\Main\EventManager,
    Bitrix\Main\Application,
    Bitrix\Highloadblock\HighloadBlockTable;
    //MyCompany\Rest\Model\Debuger;


Loc::loadMessages(__FILE__);

class Vdgb_core extends \CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'vdgb.core';
        $this->setVersionData();

        $this->MODULE_NAME = Loc::getMessage("VDGB_CORE_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("VDGB_CORE_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("VDGB_CORE_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("VDGB_CORE_PARTNER_URI");

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

        /*
        Loader::includeModule('sprint.migration');
        $versionManager = new \Sprint\Migration\VersionManager('version');
        $versionManager->startMigration('AddHLBlockNotifyRule20230913102542', 'down', [], false, '');
        $versionManager->startMigration('AddHLBlockNotify20230913102653', 'down', [], false, '');
        */

        return true;
    }

    //Регистрируем обработчики событий
    public function installEvents()
    {
        /*
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
        */

        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler('crm', 'OnAfterCrmDealAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onAfterCrmDealAddHandler');

        $eventManager->registerEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onAfterCrmDealUpdateHandler');

        $eventManager->registerEventHandler('tasks', 'OnTaskAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\TaskEvents', 'onTaskAddHandler');

        $eventManager->registerEventHandler('tasks', 'OnTaskUpdate', $this->MODULE_ID,
            '\Vdgb\Core\Events\TaskEvents', 'onTaskUpdateHandler');


        //Перед добавлением задачи
        $eventManager->registerEventHandler('tasks', 'OnBeforeTaskAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\TaskEvents', 'onBeforeTaskAddHandler');

        //При добавлении дела к сущности
        $eventManager->registerEventHandler('crm', 'OnActivityAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onActivityAddHandler');

        //При обновлении дела
        $eventManager->registerEventHandler('crm', 'OnActivityUpdate', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onActivityUpdateHandler');

		$eventManager->registerEventHandler('crm', 'OnCrmDealUpdate', $this->MODULE_ID,
			'\Vdgb\Core\Events\CrmEvents', 'onCrmDealUpdateHandler');

        return true;
    }

    //Удаляем регистрационную запись обработчика события
    public function unInstallEvents()
    {
        /*
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockElementUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockElement', 'onIblockElementChangeHandler');
        $eventManager->unRegisterEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', $this->MODULE_ID,
            '\MyCompany\Notify\Event\IblockSection', 'onIblockBeforeSectionChangeHandler');
            */

        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('crm', 'OnAfterCrmDealAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onAfterCrmDealAddHandler');
        $eventManager->unRegisterEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onAfterCrmDealUpdateHandler');

        $eventManager->unRegisterEventHandler('tasks', 'OnTaskAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\TaskEvents', 'onTaskAddHandler');
        $eventManager->unRegisterEventHandler('tasks', 'OnTaskUpdate', $this->MODULE_ID,
            '\Vdgb\Core\Events\TaskEvents', 'onTaskUpdateHandler');


        $eventManager->unRegisterEventHandler('tasks', 'OnBeforeTaskAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\TaskEvents', 'onBeforeTaskAddHandler');

        //При добавлении дела к сущности
        $eventManager->unRegisterEventHandler('crm', 'OnActivityAdd', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onActivityAddHandler');

        //При обновлении дела
        $eventManager->unRegisterEventHandler('crm', 'OnActivityUpdate', $this->MODULE_ID,
            '\Vdgb\Core\Events\CrmEvents', 'onActivityUpdateHandler');

		$eventManager->unRegisterEventHandler('crm', 'OnCrmDealUpdate', $this->MODULE_ID,
			'\Vdgb\Core\Events\CrmEvents', 'onCrmDealUpdateHandler');

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
                    Loc::GetMessage('VDGB_CORE_REQUIRED_MODULE_ERROR', ['#MODULE#' => $moduleId])
                );

                return false;
            } elseif ($version !== '*' && !CheckVersion(ModuleManager::getVersion($moduleId), $version)) {
                $APPLICATION->ThrowException(
                    Loc::GetMessage(
                        'VDGB_CORE_MODULE_VERSION_ERROR',
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
        /*return [
            'main' => '18.5.180',
            'sprint.migration' => '4.1.2',
            'mycompany.rest' => '1.0.0',
        ];*/
        return [];
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

        /*
        Loader::includeModule('sprint.migration');
        $versionManager = new \Sprint\Migration\VersionManager('version');
        $versionManager->startMigration('AddHLBlockNotifyRule20230913102542', 'up', [], false, '');
        $versionManager->startMigration('AddHLBlockNotify20230913102653', 'up', [], false, '');
        */

        return true;
    }
}
