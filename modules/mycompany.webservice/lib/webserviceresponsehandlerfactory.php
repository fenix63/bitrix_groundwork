<?
namespace MyCompany\WebService;

use Exception;

class WebServiceResponseHandlerFactory{

    private $requestData;
    private $vs;
    private $type;

    public function __construct($type){
        $this->type = $type;
        
        //создаем нужный vs
        switch ($this->type){
            case 'fns':
                $this->vs = new \MyCompany\WebService\VS\Fns();
                break;
            case 'gisgmp':
                $this->vs = new \MyCompany\WebService\VS\GisGmp();
                break;
            case 'appeal':
                $this->vs = new \MyCompany\WebService\VS\Appeal();
                break;
        }
    }

    public function checkUpdatable(){
        if ($this->vs){
            return true;
        } else {
            return false;
        }
    }

    public function searchElement(){
        return $this->vs->searchElement();
    }

    public function add(){
        $this->vs->add();
    }

    public function setData($requestData){
        $this->requestData = $requestData;
        if ($this->vs){
            $this->vs->setData($this->requestData);
        } else {
            throw new Exception('Для заданного вида сведений невозможно использовать метод');
        }
    }

    public function updateElement(){
        if ($this->vs){
            $this->vs->updateElement();
        } else {

        }
    }

    public function get() : string
    {
        //Не предусмотрен сценарий обработки(влияния) асинхронного ответа
        return http_response_code('200');  
    }
}
