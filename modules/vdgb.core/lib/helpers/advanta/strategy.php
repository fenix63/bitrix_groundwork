<?

namespace Vdgb\Core\Helpers\Advanta;


interface Strategy
{
    public static function buildXmlToRequest(array $presaleInfo, string $sessId = '');
}