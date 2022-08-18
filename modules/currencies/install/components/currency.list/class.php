<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?
class Currency extends CBitrixComponent
{
    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    public function onPrepareComponentParams($arParams){
        if(!isset($arParams["CACHE_TIME"]))
            $arParams["CACHE_TIME"] = 36000000;

        $arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"]);

        if(!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER1"]))
            $arParams["SORT_ORDER1"]="DESC";

        if(!is_array($arParams["PROPERTY_CODE"]))
            $arParams["PROPERTY_CODE"] = array();
        foreach($arParams["PROPERTY_CODE"] as $key=>$val){
            if($val==="")
                unset($arParams["PROPERTY_CODE"][$key]);
        }

        return $arParams;
    }

    protected function checkModules()
    {
        if (!Loader::includeModule('iblock'))
            throw new SystemException(Loc::getMessage('CPS_MODULE_NOT_INSTALLED', array('#NAME#' => 'iblock')));
    }

    protected function getResult(){

    }

    protected function getCurrencyInfo(){
        $client = new SoapClient("http://web.cbr.ru/GetCursOnDate");

        // Поcылка SOAP-запроса и получение результата
        $result = $client->getRate("us", "russia");
        echo "Текущий курс доллара: ", $result, " рублей";
    }

    public function executeComponent(){
        try{
            $this->getCurrencyInfo();
            $this->includeComponentTemplate();
        }catch (SystemException $e){
            ShowError($e->getMessage());
        }
    }

}
?>
