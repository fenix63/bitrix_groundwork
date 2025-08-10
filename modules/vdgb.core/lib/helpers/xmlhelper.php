<?

namespace Vdgb\Core\Helpers;
use Vdgb\Core\Debug;

class XMLHelper
{
    private $nodeValue = '';

    public static function getRequestSoap(string $xml)
    {
        $input = $xml;

        //очистка тегов
        $xmlRequest = $input;
        $xmlRequest = str_replace("<soap:","<", str_replace("</soap:","</", $xmlRequest));
        $xmlRequest = str_replace("<soap:","<", str_replace("</soap:","</", $xmlRequest));

        $resultXML = simplexml_load_string($xmlRequest);
        if($resultXML){
            //Debug::dbgLog($resultXML,'_resultXML_');            
            return $resultXML;
        }else{
            $errorMessage = '';
            foreach(libxml_get_errors() as $error){
                $errorMessage.= "\t ".$error->message;
            }

            Debug::dbgLog($errorMessage,'_ErrorMessage_');
        }

        
    }

    public function getNodeFromXmlArray(array $xmlArray, string $nodeName): string | array
    {
        foreach($xmlArray as $key => $value){
            if($key==$nodeName){
                $this->nodeValue = $value;
                break;
            }else{
                if(gettype($value)=='array'){
                    $this->getNodeFromXmlArray($value, $nodeName);
                }
            }
        }

        return $this->nodeValue;
    }
}

?>