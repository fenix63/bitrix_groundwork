<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService;

use Exception;
use MyCompany\WebService\VS;

\Bitrix\Main\Loader::includeModule('MyCompany.logger');

use MyCompany\Logger\DataReader;

/* Внешний запрос в ведомства */
class RequestSender
{
    const IBLOCK_ID = '20';
    const URL = 'http://127.0.0.1/SMEV/FNS/request/?senderService=gk_spgu&api-version=1.0&category=1';
    const PORT = '8088';
    const AM_REQUEST = '';

    public $soapResponseRequest;
    public $elementId;

    private $sendResponse;
    private $type;
    private $vs;

    function __construct($type)
    {
        $this->type = $type;

        switch ($this->type){
            case 'fns':
                $this->vs = new \MyCompany\WebService\VS\Fns();
                break;
            case 'PaymentInfo':
            case 'faQuittance':
            case 'Quittance':
                $this->vs = new \MyCompany\WebService\VS\GisGmp();
                break;
            default:
                throw new Exception('Неверный вид сведений');
                break;
        }
    }

    public function setData($request){
        $this->elementId = $this->vs->setData($request);
    }

    public function add(){
        $this->elementId = $this->vs->add();
    }

    public function updateElement(){
        $this->elementId = $this->vs->updateElement();
    }

    public function searchElement(){
        return $this->vs->searchElement();
    }

    public function setPropsElement($params){
        //установить свойства для элемента
        $this->vs->setPropsElement($params);
    }

    /* отправляем запрос. Возвращает true, если удачно */
    public function send()
    {        var_dump($this->vs->createSoapResponseRequest());
        //Создали xml для запроса
        $this->soapResponseRequest = $this->vs->createSoapResponseRequest();

        if ($this->soapResponseRequest == '') {
            throw new \Exception('Ошибка генерации запроса');
        }
        Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/sender/fns1-' . date("j-n-Y_H_i_s") . '.txt',
            var_export($this->soapResponseRequest, true)
        );

        /*DataReader::writeToLog($this->soapResponseRequest, 'Logger',
            ['UF_DATATYPE' => 'Бизнес-данные', 'UF_MSG_TYPE' => 'Отправка запроса с ответом', 'UF_INVOKE_PLACE'=>__FILE__.':'.__LINE__.' Метод: '.__METHOD__]);
        */
        $xmlDoc = new \DOMDocument();
        $xmlDoc->preserveWhiteSpace = false;
        $xmlDoc->formatOutput = false;
        $xmlDoc->loadXML($this->soapResponseRequest);
        $soapResponseRequestMinify = trim($xmlDoc->SaveXML());
        Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/sender/fns-' . date("j-n-Y_H_i_s") . '.txt',
            var_export($soapResponseRequestMinify, true)
        );

        /*DataReader::writeToLog($this->soapResponseRequest, 'Logger',
            ['UF_DATATYPE' => 'Бизнес-данные', 'UF_MSG_TYPE' => 'Отправка запроса с ответом (минифицированным)', 'UF_INVOKE_PLACE'=>__FILE__.':'.__LINE__.' Метод: '.__METHOD__]);
        */

        //Отправка Soap в Атом.Мост
        $url = static::URL . static::AM_REQUEST;
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_PORT => static::PORT,
            CURLOPT_HTTPHEADER => array('Content-Type: application/xml; charset=UTF-8' /* , 'replyTo: ' . $this->data['ReplyTo'] */),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_PRIVATE => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => str_replace(array('\n', '\r', '\r\n'), '', $soapResponseRequestMinify)
        );
        curl_setopt_array($ch, $options);
        $this->sendResponse = curl_exec($ch);
        Helper::log('atom_most_request_OptionCURLlog.txt', $options);

        $splitSymbol = "\n";
        $responseParts = explode($splitSymbol, $this->sendResponse);
        $messageId = end($responseParts);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (($httpCode == 200) || ($httpCode == 100)) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseBody = substr($this->sendResponse, $header_size);

            if (strlen($responseBody) < 50) {
                \CIBlockElement::SetPropertyValuesEx(
                    $this->elementId,
                    static::IBLOCK_ID,
                    [
                        'StatusSended' => 1
                    ]
                );

                $el = new \CIBlockElement;
                $el->Update(
                    $this->elementId,
                    [
                        'CODE' => $messageId
                    ]
                );

                Log::info(
                    $_SERVER["DOCUMENT_ROOT"] . '/logs/requestfns/logs-' . date("j-n-Y_H_i_s") . '.txt',
                    var_export($this->sendResponse, true)
                );

                /*DataReader::writeToLog(
                    $this->sendResponse,
                    'Logger',
                    ['UF_MSG_TYPE' => 'Отправка ответа', 'UF_DATATYPE' => 'Системные данные', 'UF_CURL_RESPONSE' => $this->sendResponse, 'UF_INVOKE_PLACE'=>__FILE__.':'.__LINE__.' Метод: '.__METHOD__]
                );
                */
                curl_close($ch);

                return true;
            } else {
                $this->error();

                return false;
            }
        } else {
            $this->error();
            curl_close($ch);

            return false;
        }
    }

    /* Вызываем, если запрос с ошибкой */
    private function error()
    {
        Log::error(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/requestfns/logs-' . date("j-n-Y_H_i_s") . '.txt',
            var_export($this->sendResponse, 1)
        );

        /*DataReader::writeToLog(
            $this->sendResponse,
            'Logger',
            ['UF_DATATYPE' => 'Системные данные', 'UF_CURL_RESPONSE' => $this->sendResponse, 'UF_INVOKE_PLACE'=>__FILE__.':'.__LINE__.' Метод: '.__METHOD__]
        );*/

        \CIBlockElement::SetPropertyValuesEx(
            $this->elementId,
            static::IBLOCK_ID,
            [
                'StatusSended' => 0
            ]
        );
    }
}
