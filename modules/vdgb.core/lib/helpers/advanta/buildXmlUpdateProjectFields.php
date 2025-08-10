<?

namespace Vdgb\Core\Helpers\Advanta;

use Vdgb\Core\Helpers\Advanta\Strategy;

class BuildXmlUpdateProjectFields implements Strategy
{
    public static function buildXmlToRequest(string $sessId, array $presaleInfo)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <UpdateProjectFields xmlns="http://streamline/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

      <!--ID пресейла-->
      <projectId>'.$presaleInfo['presaleUID'].'</projectId>
      <listParams>
        <FieldWrapper>
          <FieldName>Жизненный цикл предпродажного этапа</FieldName>
          <!--UID поля жизненного цикла пресейла-->
          <FieldId>4e33f5f2-9b39-4d87-8509-4bc5d8a9496c</FieldId>
          <!--Метод GetClassifierRecords-->
          <FieldVal>'.$presaleInfo['stageUID'].'</FieldVal>
        </FieldWrapper>
      </listParams>
    </UpdateProjectFields>
  </soap:Body>
</soap:Envelope>';
    }
}