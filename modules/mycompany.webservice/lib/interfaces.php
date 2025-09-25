<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService;

interface WebServiceRequest
{
    //Первичный запрос с заявкой
    public function getRequestRequest(array $requestHeaders, string $requestBody);
    //Запрос с ответом на наш запрос (т.е. асинхронный ответ)
    public function getResponseRequest(array $requestHeaders, string $requestBody);
}

interface WebServiceResponse
{
    //отправка запроса с ответом
    public function sendRequestResponse($response);
    //отправка запроса с подтверждением получения
    public function sendAck(string $messageId);
}

interface WebServiceHandleRequest
{
    //Обработка запроса
    public function handleRequest(array $requestHeaders, string $requestBody);
}

interface WebServiceHandleResponse
{
    //Обработка ответа на запрос
    public function handleRequest(array $requestHeaders, string $requestBody);
}

interface Sender{

    public function send() : bool | string;
}

interface VS{

    public function add() : int;
    public function setData(array $request);
    public function getMessageId() : string;
    public function getIblockId() : int;
    public function getWorkflowId() : int;
    public function searchElement();
    public function updateElement();
}

/* Расширенный вид сведений с запросами во внешний сервис */
interface VSRequestExternelService extends VS{
    
    /* находим элемент ИБ, который обновляем */
    public function searchElement();

    /* Устанавливаем свойства для записи элемета */
    public function setPropsElement($props);

    // Обновляем элемент в ИБ
    public function updateElement();

    //создаем запрос xml для soap запроса во внешний сервис
    public function createSoapResponseRequest();
}
