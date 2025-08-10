<?

namespace Vdgb\Core\Helpers\Advanta;

use Vdgb\Core\Helpers\Advanta\Strategy;

class BuildXmlGetClassifierRecords implements Strategy
{
    //UID классификатора статусов жизненного цикла пресейла
    const PRESALE_CLASSIFIER_ID = '8f239169-9148-4128-84f9-83851c196c53';

    public static function buildXmlToRequest(string $sessId, array $presaleInfo)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetClassifierRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

    <classifierId>'.self::PRESALE_CLASSIFIER_ID.'</classifierId>


    </GetClassifierRecords>
  </soap:Body>
</soap:Envelope>';
    }
}