<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService;

use MyCompany\WebService\Convertor as Convertor;

class WebServiceResponseFactory implements WebServiceResponse
{

    function __construct()
    {
        //стратегия
        //return new sendRequestResponse($name);
    }

    function sendRequestResponse($response)
    {
        //стратегия
        Convertor::convertToXML($response);
    }

    function sendAck($messageId) : Sender
    {
        return new AckSender($messageId);
    }

    function sendResponse(string $iblockId, string $elementId) : Sender
    {
        return new ResponseSender($iblockId, $elementId, $data);
    }
}

