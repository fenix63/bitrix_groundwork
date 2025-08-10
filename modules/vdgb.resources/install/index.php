<?php

use Bitrix\Main\Loader;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ModuleManager,
    Bitrix\Main\EventManager,
    Bitrix\Main\Application,
    Bitrix\Main\Entity\Base,
    \Vdgb\Resources\Entity\MappingTable;


Loc::loadMessages(__FILE__);

class Vdgb_resources extends \CModule
{
    private static array $arTables = [
        MappingTable::class
    ];

    public function __construct()
    {
        $this->MODULE_ID = 'vdgb.resources';
        $this->setVersionData();

        $this->MODULE_NAME = Loc::getMessage("VDGB_RESOURCES_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("VDGB_RESOURCES_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("VDGB_RESOURCES_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("VDGB_RESOURCES_PARTNER_URI");

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
        ModuleManager::registerModule($this->MODULE_ID);
        \Bitrix\Main\Loader::includeModule($this->MODULE_ID);
        $this->installFiles();
        $this->installDB();
        $this->installEvents();

        return true;
    }

    public function doUninstall()
    {
        \Bitrix\Main\Loader::includeModule($this->MODULE_ID);
        $this->unInstallEvents();
        $this->unInstallFiles();
        $this->unInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
        

        return true;
    }

    public function unInstallDB()
    {
        foreach (self::$arTables as $tableClass) {
            Bitrix\Main\Application::getConnection($tableClass::getConnectionName())->queryExecute('drop table if exists ' . Base::getInstance($tableClass)->getDBTableName());
        }

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

    //Удаляем регистрационную запись обработчика события
    public function unInstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler('tasks', 'OnTaskElapsedTimeAdd', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onTaskElapsedTimeAddHandler');

        $eventManager->unRegisterEventHandler('tasks', 'OnTaskElapsedTimeUpdate', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onTaskElapsedTimeUpdateHandler');

        $eventManager->unRegisterEventHandler('tasks', 'OnTaskElapsedTimeDelete', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onTaskElapsedTimeDeleteHandler');


        $eventManager->unRegisterEventHandler('tasks', 'OnBeforeTaskElapsedTimeUpdate', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onBeforeTaskElapsedTimeUpdateHandler');

        $eventManager->unRegisterEventHandler('tasks', 'OnBeforeTaskElapsedTimeDelete', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onBeforeTaskElapsedTimeDeleteHandler');

        return true;
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

    public function installDB()
    {

        //Создаём таблицу resources_mapping
        try{
            //\Vdgb\Resources\MappingTable::getEntity()->compileDbTableStructureDump();
            //Создаю свои таблицы если их нет в базе
            foreach (self::$arTables as $tableClass) {
                if (!Application::getConnection($tableClass::getConnectionName())->isTableExists(Base::getInstance($tableClass)->getDBTableName())) {
                    Base::getInstance($tableClass)->createDbTable();
                }
            }

        }catch(\Exception $e){
            self::dbgLog($e->getMessage(),'_create_table_error_');
        }

        return true;
    }

    //Регистрируем обработчики событий
    public function installEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandler('tasks', 'OnTaskElapsedTimeAdd', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onTaskElapsedTimeAddHandler');

        $eventManager->registerEventHandler('tasks', 'OnTaskElapsedTimeUpdate', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onTaskElapsedTimeUpdateHandler');

        $eventManager->registerEventHandler('tasks', 'OnTaskElapsedTimeDelete', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onTaskElapsedTimeDeleteHandler');



        $eventManager->registerEventHandler('tasks', 'OnBeforeTaskElapsedTimeUpdate', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onBeforeTaskElapsedTimeUpdateHandler');

        $eventManager->registerEventHandler('tasks', 'OnBeforeTaskElapsedTimeDelete', $this->MODULE_ID,
            '\Vdgb\Resources\Events\TaskEvents', 'onBeforeTaskElapsedTimeDeleteHandler');

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

    public static function dbgLog($data, string $suffix = '_1')
    {
        if (
            !empty($suffix)
            &&
            preg_match('![^-0-9a-zA-Z_]+!', $suffix)
        ) {
            $suffix = '';
        }
     
        $fileName = $_SERVER['DOCUMENT_ROOT'].'/logs/install/dbg-' . date('Ymd') . $suffix . '.log';
     
        $r = fopen($fileName, 'a');
        fwrite($r, PHP_EOL);
        fwrite($r, date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($r, print_r($data, 1));
        //ob_start(); var_export($data); fwrite($r, ob_get_clean());
        fwrite($r, PHP_EOL);
        fclose($r);
    }
}