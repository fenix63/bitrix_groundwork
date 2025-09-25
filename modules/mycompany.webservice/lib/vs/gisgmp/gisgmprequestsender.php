<?php


namespace MyCompany\WebService\VS\Gisgmp;

/* Отправляем запрос в гис гмп */
class GisgmpRequestSender{
    //данные для запроса в Атом.Мост
    //TODO Актуализировать
    const URL = 'http://127.0.0.1/SMEV/FNS/request/?senderService=gk_spgu&api-version=1.0&category=1';
    const PORT = '8088';
    const AM_REQUEST = '';

    public function __construct() {
        
    }

    public function sendRequest($request){

		\MyCompany\WebService\Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/GIS_GMP/DBG/Request_2-' . date("j-n-Y_H_i_s") . '.txt',
            var_export($request, true)
        );

        $xmlDoc = new \DOMDocument();
        $xmlDoc->preserveWhiteSpace = false;
        $xmlDoc->formatOutput = false;
        $xmlDoc->loadXML($request);
        $soapResponseRequestMinify = trim($xmlDoc->SaveXML());

        \MyCompany\WebService\Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/gisgmpRequestSender/logs-' . date("j-n-Y_H_i_s") . '.txt',
            var_export($soapResponseRequestMinify, true)
        );

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
        
        $sendResponse = curl_exec($ch);
        \MyCompany\WebService\Helper::log('atom_most_request_OptionCURLlog.txt', $options);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        $return = [
            'httpCode' => $httpCode,
            'response' => $sendResponse
        ];
        
        \MyCompany\WebService\Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/gisgmpRequestSender/Return_logs-' . date("j-n-Y_H_i_s") . '.txt',
            json_encode($return)
        );

        
        return $return;
    }
}
