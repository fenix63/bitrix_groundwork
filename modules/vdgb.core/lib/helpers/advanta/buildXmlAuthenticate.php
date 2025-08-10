<?

namespace Vdgb\Core\Helpers\Advanta;
use Vdgb\Core\Helpers\Advanta\Strategy;

class BuildXmlAuthenticate implements Strategy
{
    private $login;
    private $password;

    public function __construct(string $login, string $password)
    {
      $this->login = $login;
      $this->password = $password;
    }

    public static function buildXmlToRequest(array $presaleInfo, string $sessId)
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:str="http://streamline/">
   <soapenv:Header/>
   <soapenv:Body>
      <str:Authenticate>
         <!--Optional:-->
         <str:login>'.$this->login.'</str:login>
         <!--Optional:-->
         <str:password>'.$this->password.'</str:password>
      </str:Authenticate>
   </soapenv:Body>
</soapenv:Envelope>';
    }
}