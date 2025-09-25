<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService;

class WebServiceAbstractFactory
{

    function createRequestHandler(): WebServiceRequest
    {
        //фабрика Request
        return new WebServiceRequestFactory();
    }

    function createResponseHandler($type)
    {
        //фабрика Response
        return new WebServiceResponseHandlerFactory($type);
    }

    function createResponseSender()
    {
        //фабрика Response
        return new WebServiceResponseSenderFactory();
    }

    function __call($entityName, $arguments)
    {
        // Замечание: значение $name регистрозависимо.
        throw new \Exception("Вызов несуществующего метода '$entityName'" . implode(', ', $arguments) . "\n");
    }
}
