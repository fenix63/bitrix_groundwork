<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"
Класс отправки ответов на заявки*/

namespace MyCompany\WebService;
\Bitrix\Main\Loader::IncludeModule("iblock");
\Bitrix\Main\Loader::includeModule('MyCompany.logger');

use MyCompany\Logger\DataReader;
use MyCompany\WebService\Helper;

class ResponseSender implements Sender
{

    const FTP_LOGIN = 'anonymous';
    const FTP_PASSWORD = 'smev';
    const ATOM_MOST_URL = 'http://127.0.0.1';
    const AM_REQUEST = '/SMEV/SPGUARCH/request/?senderService=gk_spgu&api-version=1.0&category=1';
    const AM_DOC = '/SMEV/SPGUARCH/doc/?senderService=gk_spgu&api-version=1.0&category=1';
    const PORT = '8088';
    const VSDATA = [
        'ArchiveReference' => [
            'XSDNAME' => 'OREpguMyCompanyArchSpgu',
            'XSDTYPE' => 'urn://x-artefacts-MyCompany/arch/',
            'XSDVERSION' => '1.3.2',
            'IBLOCK_ID' => '17',
            'WORKFLOW_ID' => '28',
        ],
        'Accreditation' => [
            'XSDNAME' => 'OREpguMyCompanyAcrSpgu',
            'XSDTYPE' => 'urn://x-artefacts-MyCompany/acr/',
            'XSDVERSION' => '1.2.1',
            'IBLOCK_ID' => '16',
            'WORKFLOW_ID' => '14',
        ],
        'Attestation' => [
            'XSDNAME' => 'OREpguMyCompanyExpSpgu',
            'XSDTYPE' => 'urn://x-artefacts-MyCompany/exp/',
            'XSDVERSION' => '1.1.0',
            'IBLOCK_ID' => '18',
            'WORKFLOW_ID' => '18',
        ],
        'OPO' => [
            'XSDNAME' => 'OREpguMyCompanyOpo',
            'XSDTYPE' => 'urn://x-artefacts-MyCompany/opo/',
            'XSDVERSION' => '1.0.5',
            'IBLOCK_ID' => '21',
            'code' => '',
        ],
        'Transit' => [
            'XSDNAME' => 'OREpguMyCompanyTransit',
            'XSDTYPE' => 'urn://x-artefacts-MyCompany/transit/',
            'XSDVERSION' => '1.0.5',
            'IBLOCK_ID' => '22',
            'code' => '',
        ],
        'FNS' => [
            'XSDNAME' => 'FNSVipULRequest',
            'XSDTYPE' => 'urn://x-artefacts-fns-vipul-tosmv-ru/311-14/',
            'XSDVERSION' => '4.0.6',
            'IBLOCK_ID' => '',
            'code' => '',
            ],
        'Appeal' => [
            'XSDNAME' => 'OREpguMyCompanyAppealsSpgu',
            'XSDTYPE' => 'urn://x-artefacts-MyCompany/appeals/',
            'XSDVERSION' => '1.0.0',
            'IBLOCK_ID' => '43',//Вычислить динамически
            'WORKFLOW_ID' => '29',//Вычислить динамически
        ]
    ];
    private $iblockId;
    private $elementId;
    private $data;
    private $soapResponseRequest;
    
    public $sendResponse;

    public function __construct($iblockId, $elementId)
    {
        $this->iblockId = $iblockId;
        $this->elementId = $elementId;
    }

    public function setResponseSenderDataParam(string $paramName, $paramValue = null)
    {
        $this->data[$paramName] = $paramValue;
    }

    public function getFilesId($type = '')
    {
        $ids = [];
        $files = \CIBlockElement::GetProperty($this->iblockId, $this->elementId, "sort", "asc", ['CODE' => $type]);
        while ($file = $files->fetch()) {
            if($file['VALUE']){
                $ids[] = $file['VALUE'];
            }
        }
        if(count($ids) == 0){

			return false;
		}

        return $ids;
    }

    public function getStatusText($type = '')
    {
        $element = \CIBlockElement::getlist([],
            [
                'IBLOCK_ID' => $this->iblockId,
                'ID' => $this->elementId
            ],
            false,
            false,
            ['IBLOCK_CODE', 'PREVIEW_TEXT'])->fetch();

        $text = \CIBlockElement::getlist([],
            [
                'IBLOCK_CODE' => $element['IBLOCK_CODE'] . '-templates',
                'CODE' => $element['PREVIEW_TEXT']
            ],
            false,
            false,
            ['DETAIL_TEXT'])->fetch();

        return $text['DETAIL_TEXT'];
    }

    public function prepareDataForSending()
    {
        $informType = $this->getInformType();
        $arrayOfFileForXml = false;

        $data = $this->data;

        //AttachmentsSended - для окончательного ответа
        if ($data['AttachmentsSended'] > 0) {
            //Сжатие файлов в zip
            $zip = new \ZipArchive();
            $zipName = 'answerAttach_' . $data['messageId'] . '.zip';
            $zipPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/attachments/response/' . $zipName;
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            if (is_array($data['AttachmentsSended'])) {
                $files = $data['AttachmentsSended'];
            } else {
                $files[] = $data['AttachmentsSended'];
            }
            foreach ($files as $fileId) {
                $fileObj = \CFile::GetById($fileId);
                $fileInfo = $fileObj->Fetch();
                if (is_array($fileInfo)) {
                    $dirPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $fileInfo['SUBDIR'] . '/';
                    $filePath = $dirPath . $fileInfo['FILE_NAME'];
                    $zip->addFile($filePath, $fileInfo['ORIGINAL_NAME']);
                } else {
                    throw new \Exception("Неверный формат файла: ". $fileInfo);
                }
            }
            $zip->close();

            //Отправка zip в Атом.Мост
            $url = self::ATOM_MOST_URL . self::AM_DOC;
            $zipContent = file_get_contents($zipPath);
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => self::PORT,
                CURLOPT_HTTPHEADER => array('Content-Type: application/octet-stream;', 'guid:' . $data['messageId'], 'filenamenew:' . $zipName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_PRIVATE => true,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $zipContent
            );

            curl_setopt_array($ch, $options);

            $this->sendResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode == 200) {
                $arrayOfFileForXml = ['guid' => $data['messageId'], 'filename' => $zipName];
            } else {
                $this->error();
                die();
            }
        }


        //Для предварительного ответа
        if ($data['PreAttachmentsSended'] > 0) {
            //Сжатие файлов в zip
            $zip = new \ZipArchive();
            $zipName = 'answerAttach_' . $data['messageId'] . '.zip';
            $zipPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/attachments/response/' . $zipName;
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            if (is_array($data['PreAttachmentsSended'])) {
                $files = $data['PreAttachmentsSended'];
            } else {
                $files[] = $data['PreAttachmentsSended'];
            }
            foreach ($files as $fileId) {
                $fileObj = \CFile::GetById($fileId);
                $fileInfo = $fileObj->Fetch();
                if (is_array($fileInfo)) {
                    $dirPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $fileInfo['SUBDIR'] . '/';
                    $filePath = $dirPath . $fileInfo['FILE_NAME'];
                    $zip->addFile($filePath, $fileInfo['ORIGINAL_NAME']);
                } else {
                    throw new \Exception("Неверный формат файла: ". $fileInfo);
                }
            }
            $zip->close();

            //Отправка zip в Атом.Мост
            $url = self::ATOM_MOST_URL . self::AM_DOC;
            $zipContent = file_get_contents($zipPath);
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => self::PORT,
                CURLOPT_HTTPHEADER => array('Content-Type: application/octet-stream;', 'guid:' . $data['messageId'], 'filenamenew:' . $zipName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_PRIVATE => true,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $zipContent
            );

            curl_setopt_array($ch, $options);

            $this->sendResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode == 200) {
                $arrayOfFileForXml = ['guid' => $data['messageId'], 'filename' => $zipName];
            } else {
                $this->error();
                die();
            }
        }

        //Конец  Для предварительного ответа


        if ($arrayOfFileForXml) { 
            $soapResponseRequest = '<?xml version="1.0" encoding="UTF-8"?>
            <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:exc14n="http://www.w3.org/2001/10/xml-exc-c14n#">
                <S:Header xmlns:atom-smev="urn://x-artefacts-smev-atom-most-common">
                <atom-smev:Attachments> 
				<atom-smev:Attachment name="' . $arrayOfFileForXml['filename'] . '" type="file">' . $arrayOfFileForXml['guid'] . '/' . $arrayOfFileForXml['filename'] . '</atom-smev:Attachment> 
                </atom-smev:Attachments>
                </S:Header> 
                <S:Body>
                    <xz1:' . $informType['XSDNAME'] . ' xmlns:xz1="' . $informType['XSDTYPE'] . $informType['XSDVERSION'] . '" xmlns="urn://x-artefacts-smev-gov-ru/services/message-exchange/types/1.2" xmlns:date="http://exslt.org/dates-and-times" xmlns:ns2="http://smev.gosuslugi.ru/rev120315" xmlns:ns3="urn://x-artefacts-smev-gov-ru/services/message-exchange/types/faults/1.2" xmlns:pgufg="http://idecs.atc.ru/pgufg/ws/fgapc/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xz2="urn://x-artefacts-MyCompany/acr/fl/' .  $informType['XSDVERSION'] . '" xmlns:xz3="urn://x-artefacts-MyCompany/acr/attach/' .  $informType['XSDVERSION']  . '">';
            $resultAnswer = $data['StatusComment'];
            if (empty($data['StatusComment']))
                $resultAnswer = $data['PreliminaryStatusComment'];

            $soapResponseRequest.='
                        <xz1:StatusComment>' . strip_tags($resultAnswer) . '</xz1:StatusComment>
                        <xz1:ResultState>' . $data['ResultState'] . '</xz1:ResultState>
                    </xz1:' . $informType['XSDNAME'] . '>
                </S:Body>
            </S:Envelope>';
        } else {
         $soapResponseRequest = '<?xml version="1.0" encoding="UTF-8"?>
        <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:exc14n="http://www.w3.org/2001/10/xml-exc-c14n#">
            <S:Header xmlns:atom-smev="urn://x-artefacts-smev-atom-most-common"> 
            </S:Header> 
            <S:Body>
                <xz1:' . $informType['XSDNAME'] . ' xmlns:xz1="' . $informType['XSDTYPE'] . $informType['XSDVERSION'] . '" xmlns="urn://x-artefacts-smev-gov-ru/services/message-exchange/types/1.2" xmlns:date="http://exslt.org/dates-and-times" xmlns:ns2="http://smev.gosuslugi.ru/rev120315" xmlns:ns3="urn://x-artefacts-smev-gov-ru/services/message-exchange/types/faults/1.2" xmlns:pgufg="http://idecs.atc.ru/pgufg/ws/fgapc/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xz2="urn://x-artefacts-MyCompany/acr/fl/' .  $informType['XSDVERSION'] . '" xmlns:xz3="urn://x-artefacts-MyCompany/acr/attach/' .  $informType['XSDVERSION']  . '">';
            $resultAnswer = $data['StatusComment'];
            if(empty($data['StatusComment']))
                $resultAnswer = $data['PreliminaryStatusComment'];
            $soapResponseRequest.='
                    <xz1:StatusComment>' . strip_tags($resultAnswer) . '</xz1:StatusComment>
                    <xz1:ResultState>' . $data['ResultState'] . '</xz1:ResultState>
                </xz1:' . $informType['XSDNAME'] . '>
            </S:Body>
        </S:Envelope>';
        }
        $this->soapResponseRequest = $soapResponseRequest;
    } 

    public function send() : bool
    {
        $soapResponseRequest = $this->soapResponseRequest;
        $xmlDoc = new \DOMDocument();
        $xmlDoc->preserveWhiteSpace = false;
        $xmlDoc->formatOutput = false;
        $xmlDoc->loadXML($soapResponseRequest);
        $soapResponseRequestMinify = trim($xmlDoc->SaveXML());
        Log::info(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/sender/logs-' . date("j-n-Y") . '.txt',
            var_export(['Отправлено: ' => $soapResponseRequestMinify.PHP_EOL.'==================='.PHP_EOL], true)
        );


        //Отправка Soap в Атом.Мост
        $url = static::ATOM_MOST_URL . static::AM_REQUEST;
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_PORT => static::PORT,
            CURLOPT_HTTPHEADER => array('Content-Type: application/xml; charset=UTF-8', 'replyTo: ' . $this->data['ReplyTo'], 'messageId: ' . $this->data['messageId']), 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_PRIVATE => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => str_replace(array('\n', '\r', '\r\n'), '', $soapResponseRequestMinify)
        );
        curl_setopt_array($ch, $options);
        $this->sendResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (($httpCode == 200) || ($httpCode == 100)) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseBody = substr($this->sendResponse, $header_size);

            //Нужно проверять на соответствие паттерну (Корректный GUID)
            /*
            function isValidGuid($guid) {
            // Регулярное выражение для проверки формата GUID
            $pattern = '/^\{?([0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})\}?$/i';
            return preg_match($pattern, $guid) === 1;
        }
        */
            $clearXml = self::clearXML($soapResponseRequestMinify);
            $entityName = self::extractMyCompanyElement($clearXml)->tagName;

            if(self::isValidGuid($responseBody)){
                /* 

                в $responseBody содержится messageId 
                Request::getPost('messageId');
                */
                \CIBlockElement::SetPropertyValuesEx(
                    $this->elementId,
                    $this->iblockId,
                    [
                        'StatusSended' => 1
                    ]
                );

                //Логировать responseBody (получено)

                //Логируем только ResponseBody
                Log::info(
                    $_SERVER["DOCUMENT_ROOT"] . '/logs/sender/logs-'.$entityName.'-' . date("j-n-Y") . '.txt',
                    var_export([
                        'Получено: ' => $this->sendResponse,
                        'Тело полученного ответа: ' => $responseBody
                    ], true)
                );

                curl_close($ch);

                return true;

            } else {

                //Логируем только ResponseBody + сообщение о том, что длина тела больше 50 символов (тело запроса не соответствует GUID)
                $this->error('ErrorBodyGuid-', $entityName);

                return false;
            }
        } else {
            $this->error();

            curl_close($ch);

            return false;
        }
    }

    /* Вызываем, если запрос с ошибкой */
    private function error(string $errorType = 'HttpCodeError', string $entityType = '')
    {
        Log::error(
            $_SERVER["DOCUMENT_ROOT"] . '/logs/sender/'.$errorType.'logs-'. $entityType . date("j-n-Y") . '.txt',
            var_export($this->sendResponse, 1)
        );


        \CIBlockElement::SetPropertyValuesEx(
            $this->elementId, 
            $this->iblockId, 
            [
                'StatusSended' => -1
            ]
        ); 
    }
    
    private function getInformType(){
        foreach (static::VSDATA as $item){
            if ($item['IBLOCK_ID'] == $this->iblockId){
                return $item;
            }
        }
        throw new \Exception("Указанный в рамках отправки ответа вид сведений не зарегистрирован по данному IBLOCK_ID: ". $this->iblockId);
    }

    private function extractMyCompanyElement($xmlString)
    {
        // Загружаем XML
        $dom = new \DOMDocument();
        $dom->loadXML($xmlString);

        // Находим элемент Body
        $body = $dom->getElementsByTagName('Body')->item(0);

        if ($body) {
            // Получаем первый дочерний элемент Body (пропускаем текстовые узлы)
            foreach ($body->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    return $child;
                }
            }
        }

        return null;
    }

    private static function clearXML(string $requestBody)
    {
        $cleanedXML = $requestBody;
        $cleanedXML = str_replace("<S:", "<", str_replace("</S:", "</", $cleanedXML ));
        $cleanedXML = str_replace("<xz1:", "<", str_replace("</xz1:", "</", $cleanedXML ));


        for ($i = 0; $i < 100; $i++) {
            $cleanedXML = str_replace("<ns$i:", "<", str_replace("</ns$i:", "</", $cleanedXML));
        }

        //Поиск и замена по регулярному выражению
        $cleanedXML = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $cleanedXML);

        return $cleanedXML;
    }

    public static function isValidGuid($guid)
    {
        // Регулярное выражение для проверки формата GUID
        $pattern = '/^\{?([0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})\}?$/i';
        return preg_match($pattern, $guid) === 1;
    }


}
