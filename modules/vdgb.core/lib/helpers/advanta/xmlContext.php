<?

namespace Vdgb\Core\Helpers\Advanta;
use Vdgb\Core\Helpers\Advanta\BuildXmlAuthenticate;
use Vdgb\Core\Helpers\Advanta\BuildXmlCreateProject;
use Vdgb\Core\Helpers\Advanta\BuildXmlGetClassifierRecords;
use Vdgb\Core\Helpers\Advanta\BuildXmlInsertDirectoryRecords;
use Vdgb\Core\Helpers\Advanta\BuildXmlUpdateProjectFields;
use Vdgb\Core\Helpers\Advanta\Strategy;

use Vdgb\Core\Debug;

class XmlContext
{
    private $strategy;
    private $sessId;
    private $presaleInfo;

    public funciton __construct(Strategy $inputStrategy/*, string $sessId, array $presaleInfo*/)
    {
        $this->strategy = $inputStrategy;
        /*$this->sessId = $sessId;
        $this->presaleInfo = $presaleInfo;
        */
    }

    public function setStrategy(Strategy $inputStrategy)
    {
        $this->strategy = $inputStrategy;
    }

    public function chooseStrategy(string $methodName)
    {
        $className = "Vdgb\\Core\\Helpers\\Advanta\\".$methodName;
        if(class_exists($className))
            $className::buildXmlToRequest($this->sessId, $this->presaleInfo);
        else{
            Debug::dbgLog('Класс '.$className.' не существует', '_ClassNotFound_');
        }
    }
}