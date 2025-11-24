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

class Collectors_core extends \CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'collectors.core';
        $this->setVersionData();

        $this->MODULE_NAME = Loc::getMessage("COLLECTORS_CORE_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("COLLECTORS_CORE_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("COLLECTORS_CORE_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("COLLECTORS_CORE_PARTNER_URI");

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


        return true;
    }

    //Регистрируем обработчики событий
    public function installEvents()
    {

		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandler('crm', 'OnCrmDealUpdate', $this->MODULE_ID,
			'\Collectors\Core\Events\CrmEvents', 'onCrmDealUpdateHandler');

		$eventManager->registerEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID,
            '\Collectors\Core\Events\CrmEvents', 'onAfterCrmDealUpdateHandler');

        return true;
    }

    //Удаляем регистрационную запись обработчика события
    public function unInstallEvents()
    {

		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler('crm', 'OnCrmDealUpdate', $this->MODULE_ID,
			'\Collectors\Core\Events\CrmEvents', 'onCrmDealUpdateHandler');

		$eventManager->unRegisterEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID,
            '\Collectors\Core\Events\CrmEvents', 'onAfterCrmDealUpdateHandler');

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
                    Loc::GetMessage('COLLECTORS_CORE_REQUIRED_MODULE_ERROR', ['#MODULE#' => $moduleId])
                );

                return false;
            } elseif ($version !== '*' && !CheckVersion(ModuleManager::getVersion($moduleId), $version)) {
                $APPLICATION->ThrowException(
                    Loc::GetMessage(
                        'COLLECTORS_CORE_MODULE_VERSION_ERROR',
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

        return true;
    }
}
