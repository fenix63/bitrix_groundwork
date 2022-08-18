<?

//подключаем основные классы для работы с модулем
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;


//require __DIR__.'/../../../../vendor/autoload.php';
require __DIR__.'/../../../modules/currencies/lib/Currency.php';



//в данном модуле создадим адресную книгу, и здесь мы подключаем класс, который создаст нам эту таблицу
//use Module\Adress\AdressTable;
use Currencies\Currency\CurrencyTable;
Loc::loadMessages(__FILE__);


class currencies extends CModule
{
//    var $MODULE_ID = "currencies";
//    var $MODULE_VERSION;
//    var $MODULE_VERSION_DATE;
//    var $MODULE_NAME;
//    var $MODULE_DESCRIPTION;
//    var $MODULE_CSS;

    function __construct(){
        $arModuleVersion = array();
        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        //пишем название нашего модуля как и директории
        $this->MODULE_ID = 'currencies';
        // название модуля
        $this->MODULE_NAME = Loc::getMessage('MYMODULE_MODULE_NAME');
        //описание модуля
        $this->MODULE_DESCRIPTION = Loc::getMessage('MYMODULE_MODULE_DESCRIPTION');
        //используем ли индивидуальную схему распределения прав доступа, мы ставим N, так как не используем ее
        $this->MODULE_GROUP_RIGHTS = 'N';
        //название компании партнера предоставляющей модуль
        $this->PARTNER_NAME = Loc::getMessage('MYMODULE_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = 'https://vk.com/youran_88';//адрес вашего сайта
    }

    //здесь мы описываем все, что делаем до инсталляции модуля, мы добавляем наш модуль в регистр и вызываем метод создания таблицы
    public function doInstall()
    {
        //dbgLog('doInstall()', 'do_install');
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->InstallFiles();
        //dbgLog('doInstall()', 'test0');
        ModuleManager::registerModule($this->MODULE_ID);
        //dbgLog('doInstall()', 'test1');
        //dbgLog($DOCUMENT_ROOT, 'test1');
        //dbgLog('doInstall()', 'test2');
        $this->installDB();

        //IncludeAdminFile - после вызова этого метода, дальше ничего не работает
        $APPLICATION->IncludeAdminFile("Установка модуля currencies", $DOCUMENT_ROOT."/local/modules/currencies/install/step.php");
        //Вот тут уже ничего не работает



    }

    //вызываем метод удаления таблицы и удаляем модуль из регистра
    public function doUninstall()
    {
        //dbgLog('doUninstall()', 'doUninstall');
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->UnInstallFiles();
        ModuleManager::UnRegisterModule($this->MODULE_ID);
        $this->UnInstallDB();
        $APPLICATION->IncludeAdminFile("Деинсталляция модуля currencies", $DOCUMENT_ROOT."/local/modules/currencies/install/unstep.php");
    }

    function InstallFiles()
    {
        //dbgLog('InstallFiles()', 'install_files');
        /*CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/currencies/install/components",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);*/
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/currencies/install/components",
            $_SERVER["DOCUMENT_ROOT"]."/local/components", true, true);
        return true;
    }

    function installDB(){
        //dbgLog('InstallDB()', 'create_db');
        //Создаём таблицу
        Loader::includeModule($this->MODULE_ID);
        if(!Application::getConnection()->isTableExists(Base::getInstance('Currencies\Currency\CurrencyTable')->getDBTableName())){
            Base::getInstance('Currencies\Currency\CurrencyTable')->createDbTable();
        }
    }

    function UnInstallDB()
    {
        dbgLog('UnInstallDB', 'uninstall_db');
        Loader::includeModule($this->MODULE_ID);
        Application::getConnection()->queryExecute('drop table if exists '.Base::getInstance('Currencies\Currency\CurrencyTable')->getDBTableName());
        Option::delete($this->MODULE_ID);

    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/local/components/currency.list");
        return true;
    }

}
?>
