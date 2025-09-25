<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService;

//Если к нам поступил xml запрос из вне
class WebServiceRequestFactory implements WebServiceRequest
{
    public $requestHeaders = [];
    public $requestBody = '';

    function __construct()
    {
        $this->requestHeaders = getallheaders();
        $this->requestBody = file_get_contents('php://input');
        if($_REQUEST["log"]){
            $this->requestBody = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/logs/orders/" . $_GET["log"]);
            if(!$this->requestBody){

                throw new \Exception('Указанный лог-файл пустой или не найден');
            }
        }
    }

    public function getRequest() : RequestHandler
    {
        //Блок определения стратегии, новый ли request, или request - ответ на наш запрос.        
        if ($this->requestBody) {
            $requestArray = Helper::parseXmlToArray($this->requestBody);
            if ($requestArray[0]['attributes']['ТипИнф'] == "ЕГРЮЛ_ОТКР_СВЕД"){
                //fns
                return $this->getResponseRequest($this->requestHeaders, $this->requestBody);
            }
            foreach ($requestArray as $nodeData) {
                if ($nodeData['tag'] == strtoupper('GetRequestResponse')) {

                    return $this->getRequestRequest($this->requestHeaders, $this->requestBody);
                } elseif ($nodeData['tag'] == strtoupper('GetResponseResponse')) {

                    return $this->getResponseRequest($this->requestHeaders, $this->requestBody);
                }
            }

            throw new \Exception('Невалидная структура XML-запроса:' . var_dump($this->requestBody));
        } else {

            throw new \Exception('Пустой запрос');
        }
    }

    //к нам пришло
    public function getRequestRequest(array $requestHeaders, string $requestBody): RequestHandler
    {
        $webService = new WebService(new RequestRequest());

        return $webService->getRequestHandler($requestHeaders, $requestBody);
    }

    //мы отправили  и к нам пришло
    public function getResponseRequest($requestHeaders, $requestBody): WebServiceHandleResponse
    {
        $webService = new WebService(new ResponseRequest());

        return $webService->getRequestHandler($requestHeaders, $requestBody);
    }
}


class WebService
{

    private $request = '';

    public function __construct(WebServiceHandleRequest $request)
    {
        $this->request = $request;
    }

    public function getRequestHandler(array $requestHeaders, string $requestBody)
    {

        return $this->request->handleRequest($requestHeaders, $requestBody);
    }
}


class RequestRequest implements WebServiceHandleRequest
{

    public function handleRequest(array $requestHeaders, string $requestBody)
    {
        return new RequestHandler($requestHeaders, $requestBody);
    }

}

class ResponseRequest implements WebServiceHandleRequest
{

    public function handleRequest(array $requestHeaders, string $requestBody)
    {
        return new ResponseHandler($requestHeaders, $requestBody);
    }
}
