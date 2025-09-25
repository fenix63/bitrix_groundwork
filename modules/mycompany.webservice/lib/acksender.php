<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"
Класс отправки подтерждения получения заявки в Атом.Мост -> СМЭВ*/

namespace MyCompany\WebService;

use Exception;
use MyCompany\WebService\VS;

\Bitrix\Main\Loader::includeModule('MyCompany.webservice');
\Bitrix\Main\Loader::includeModule('MyCompany.logger');

use MyCompany\Logger\DataReader;

class AckSender implements Sender
{
    const ATOMMOST_URL_ACK = 'http://127.0.0.1/SMEV/SPGUARCH/ack/?senderService=gk_spgu&api-version=1.0&category=1';
    const ATOMMOST_PORT = '8088';

    private $curlOptHttpHeader;

    public function __construct(string $messageId)
    {
        $this->curlOptHttpHeader = [
            "Content-Type: text/plain; charset=UTF-8",
            "messageId: " . $messageId
        ];
    }

    public function send(): bool|string
    {
        $ackMessage = file_get_contents('php://input');
        if ($ackMessage != '') {
            //CURL
            $ch = curl_init();
            $options = [
                CURLOPT_URL => static::ATOMMOST_URL_ACK,
                CURLOPT_PORT => static::ATOMMOST_PORT,
                CURLOPT_HTTPHEADER => $this->curlOptHttpHeader,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_PRIVATE => true,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
            ];
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            Log::info(
                $_SERVER["DOCUMENT_ROOT"] . '/logs/sender/ack-' . date("j-n-Y_H_i_s") . '.txt',
                var_export($response, true)
            );

            //TODO тут нужно добавить элемент в HL блок "Отправка сообщения"
            //$messageId = DataReader::getMessageId($response);
            //DataReader::writeToLog($ackMessage, 'Logger', ['UF_MSG_TYPE' => 'ack', 'UF_DATATYPE' => 'Системные данные', 'UF_CURL_RESPONSE' => $response, 'UF_INVOKE_PLACE' => __FILE__ . ':' . __LINE__ . ' Метод: ' . __METHOD__, 'UF_MSG_ID' => $messageId]);

            if ($httpCode != 200) {
                //DataReader::writeToLog($ackMessage, 'Logger', ['UF_MSG_TYPE' => 'ack', 'UF_DATATYPE' => 'Системные данные', 'UF_CURL_RESPONSE' => 'Ошибка отправки подтверждения.' . "Return code is {$httpCode} \n" . curl_error($ch), 'UF_INVOKE_PLACE' => __FILE__ . ':' . __LINE__ . ' Метод: ' . __METHOD__, 'UF_MSG_ID' => $messageId]);
                throw new \Exception('Ошибка отправки подтверждения.' . "Return code is {$httpCode} \n" . curl_error($ch));
            }

            return $response;
        }

        return false;
    }
}
