<?

namespace Vdgb\Core\Helpers\Advanta;

use Vdgb\Core\Helpers\Advanta\Strategy;

class BuildXmlInsertDirectoryRecords implements Strategy
{
    //Команда управления пресейлом
    const PRESALE_COMMAND_CONTROL = '6f00ef62-8012-4846-a6f9-a3cef328f505';

    public static function buildXmlToRequest(string $sessId, array $presaleInfo)
    {
        $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <InsertDirectoryRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

      <!--6f00ef62-8012-4846-a6f9-a3cef328f505  Это ID справочника команды управления пресейлами-->
      <directoryTemplateId>'.self::PRESALE_COMMAND_CONTROL.'</directoryTemplateId>
      <projectRecords>';
            foreach($presaleInfo['users'] as $userItem){
              $fieldWrap='<ProjectRecordWrapper>';
              $fieldWrap.='<ProjectId>'.$presaleInfo['presaleId'].'</ProjectId>';
              $fieldWrap.='<Fields>';
              $fieldWrap.= '<FieldWrapper>';
              $fieldWrap.='<FieldName>ФИО участника</FieldName>';
              $fieldWrap.='<FieldId>61bffee0-97d7-47fc-b84a-13f1e69da518</FieldId>';
              $fieldWrap.='<FieldVal>'.$userItem['UID'].'</FieldVal>';
              $fieldWrap.='</FieldWrapper>';

              $fieldWrap.= '<FieldWrapper>';
              $fieldWrap.='<FieldName>Должность</FieldName>';
              $fieldWrap.='<FieldId>7fe90a25-b017-4eb4-9b2d-bea1e78fefce</FieldId>';
              $fieldWrap.='<FieldVal>'.$userItem['POSITION'].'</FieldVal>';
              $fieldWrap.='</FieldWrapper>';

              $fieldWrap.= '<FieldWrapper>';
              $fieldWrap.='<FieldName>Проектная роль(б24)</FieldName>';
              $fieldWrap.='<FieldId>354eefc8-cf3d-4628-9e42-03bfa3831abf</FieldId>';
              $fieldWrap.='<FieldVal>'.$userItem['roleb24'].'</FieldVal>';
              $fieldWrap.='</FieldWrapper>';

              $fieldWrap.='</Fields>';
              $fieldWrap.='</ProjectRecordWrapper>';

              $outXml.= $fieldWrap;
            }
$outXml.='
      </projectRecords>
    </InsertDirectoryRecords>
  </soap:Body>
</soap:Envelope>';

    return $outXml;
     
    }
}