<?
namespace Currencies\Currency;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\FloatField;
//use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
Loc::loadMessages(__FILE__);


class CurrencyTable extends Entity\DataManager
{
    // название таблицы
    public static function getTableName()
    {
        return 'currency';
    }

    // создаем поля таблицы
    public static function getMap()
    {
        return array(
            new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true
            )),// autocomplite с первичным ключом
            new StringField('code',
                array(
                    'required' => true,
                    //'title' => Loc::getMessage('MYMODULE_NAME'),
                    'title' => 'currencies',
                    'default_value' => function () {
                        //return Loc::getMessage('MYMODULE_NAME_DEFAULT_VALUE');
                        return 'default_value';
                    },

                )
            ),

            new DatetimeField('date',
                array(
                    'required' => true
                )
            ),

            new FloatField(
                'course',
                array(
                    'required' => true
                )
            )

        );
    }
}

?>
