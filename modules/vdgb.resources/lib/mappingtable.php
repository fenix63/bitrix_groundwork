<?
namespace Vdgb\Resources;
use Bitrix\Main\Entity;

class MappingTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'resources_mapping';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\StringField('ADVANTA_TASK_RECORD_UID', array(
                'required' => true,
            ))
        ];
    }
}