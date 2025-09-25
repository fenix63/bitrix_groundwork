<?
namespace MyCompany\WebService;

class ResponseHandler{

        public function get() : string
        {
            //Не предусмотрен сценарий обработки(влияния) асинхронного ответа
            return http_response_code('200');  
        }
}
