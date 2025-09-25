<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"
Класс вспомогательных функций*/

namespace MyCompany\WebService;
\Bitrix\Main\Loader::includeModule('highloadblock');
use Bitrix\Highloadblock as HL;
use Bitrix\Main\UserTable;

class Helper
{
    const FTP_URL = 'smev3-n0.test.gosuslugi.ru'; //URL FTP-шары
    const FTP_PORT = '21'; //Порт соединения с FTP-шарой, 21 по умолчанию
    const FTP_TIMEOUT = '15'; //Таймаут соединения с FTP-шарой, секунды
    const TIMEOUT_SEC = 600;
    const AUTOSEEK = FALSE;

    const ACC_STATUS_HISTORY = '7'; //hl-блок для истории статусов аккредитации
    const ATT_STATUS_HISTORY = '10'; //hl-блок для истории статусов аттестации
    const ARCH_STATUS_HISTORY = '12'; //hl-блок для истории статусов архивных справок
    const ACC_STATUS_MSG_HISTORY = '12'; //hl-блок для истории статусов сообщений аккредитации
    const ATT_STATUS_MSG_HISTORY = '13'; //hl-блок для истории статусов сообщений аттестации

    const URN = '395dcc'; //УРН отправителя  (в XML это атрибут senderIdentifier), можно ещё подставить 3eacb7 или 3eb5fa или 3eb646 или 395dcc
    const ORIGINATOR_ID = '395dcc';// в XML это originatorId
    const ROLE_TYPE='1';//В XML это senderRole
    const CHARGES_TAX_DOCNUMBER = '0';
    const CHARGES_BUDGET_INDEX_STAUS = '01';
    const CHARGES_PAYER_NAME = 'Тестовый плательщик';
    const CHARGES_ETALON_BILL_DATE = '2020-09-30T14:06:30.313+03:00';
    const CHARGES_ETALON_KBK = '32111301031016000130';
    const CHARGES_ETALON_OTMO = '45348000';
    const CHARGES_ETALON_INN ='7705401341';
    const CHARGES_ETALON_KPP = '770542151';
    const CHARGES_ETALON_OGRN = '7723819340452';
    const CHARGES_ETALON_SUPPLIER_BILL_ID = '32116102414550976332';
    const CHARGES_ETALON_PAYT_REASON = '0';
    const CHARGES_ETALON_SENDER_ROLE = '3';
    const CHARGES_ETALON_SENDER_IDENTIFIER = '395dcc';


    public static function convertArrayToXML(string $data)
    {

        return [];
    }

    /**Получить ID шаблона бизнес процесса по его названию
     * @param string $workFlowTitle
     */
    public static function getWorkFlowTemplateIdByTitle(string $workFlowTitle)
    {
        $currentTemplate = \Bitrix\Bizproc\WorkflowTemplateTable::getList([
            'select' => ['ID'],
            'filter' => ['NAME' => $workFlowTitle]
        ]);
        $templateId = -1;
        if ($row = $currentTemplate->fetch()) {
            $templateId = $row['ID'];
        }

        return $templateId;
    }

    public static function getElementsByFilter(array $select, array $filter): array
    {
        $props = [];
        \CIBlockElement::GetPropertyValuesArray(
            $props,
            $filter['IBLOCK_ID'],
            $filter,
            ['CODE' => $select]
        );


        return $props;
    }

    public static function getElements(array $select, array $filter): array
    {
        $items = [];
        $dbItems = \CIBlockElement::GetList(['ID'=>'DESC'], $filter, false, false, $select);
        while($item = $dbItems->fetch()){
            $items[$item['ID']] = $item;
        }
        return $items;
    }

    public static function parseXmlToArray(string $xml)
    {
        $parsedXml = xml_parser_create();
        xml_parse_into_struct($parsedXml, $xml, $xmlValues, $index);
        xml_parser_free($parsedXml);
        foreach($xmlValues as $key => $nodeData){
            if(mb_stripos($nodeData['tag'], ':') !== false){
                $xmlValues[$key]['tag'] = explode(':', $nodeData['tag'])[1];
            }
        }

        return $xmlValues;
    }

	public static function parseXml(string $xml){
		$parser = xml_parser_create();
		
		// Set up parsing options
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		
		// Initialize variables for storing parsed data
		$parsedData = [];
		$currentTag = null;
		
		// Set up callback functions for start and end elements
		xml_set_element_handler($parser, function ($parser, $name, $attrs) use (&$parsedData, &$currentTag) {
			$currentTag = $name;
		}, function ($parser, $name) use (&$parsedData, &$currentTag) {
			$currentTag = null;
		});
		
		// Set up callback function for character data
		xml_set_character_data_handler($parser, function ($parser, $data) use (&$parsedData, &$currentTag) {
			if (!empty($currentTag)) {
				$parsedData[$currentTag] = $data;
			}
		});
		
		// Parse the XML data
		xml_parse($parser, $xml);
		
		// Free the XML parser
		xml_parser_free($parser);
		
		// Display the parsed data
		return $parsedData;
		}

    public static function checkUniqueMessageId(int $iblockId, string $messageID)
    {
		$dbItems = \Bitrix\Iblock\ElementTable::getList(array(
			'order' => array('SORT' => 'ASC'), // сортировка
			'select' => array('ID', 'NAME', 'IBLOCK_ID', 'SORT', 'TAGS'), // выбираемые поля, без свойств. Свойства можно получать на старом ядре \CIBlockElement::getProperty
			'filter' => array('IBLOCK_ID' => $iblockId, 'XML_ID'=> $messageID), // фильтр только по полям элемента, свойства (PROPERTY) использовать нельзя
			'group' => array('TAGS'), // группировка по полю, order должен быть пустой
			'limit' => 1, // целое число, ограничение выбираемого кол-ва
			'count_total' => 1, // дает возможность получить кол-во элементов через метод getCount()
		));
		return ($dbItems->getCount()>0);
    }

    //$path- путь до файла, содержимое которого нужно в base64 в Attachment xml 
    public static function getFileContentInBase64($pathToFile)
    {
        $fileConent = file_get_contents($pathToFile);
        if ($fileConent != '') {
            return base64_encode($fileConent);
        }
        return false;
    }

    public static function fixRequestStatusInHistory($iblockId, $requestId, $statusId, $remark = '')
    {
        switch ($iblockId) {
            case 7: //Accreditation
                $hl_blockId = self::ACC_STATUS_HISTORY;
                break;
            case 8: //Attestation
                $hl_blockId = self::ATT_STATUS_HISTORY;
                break;
            case 4: //Attestation
                $hl_blockId = self::ARCH_STATUS_HISTORY;
                break;
            default:
                return;
        }

        $dateNow = new \DateTime();

        $params = [
            'UF_REQUEST' => $requestId,
            'UF_USER' => 1, //TODO возможно заменить на global $USER
            'UF_STATUS' => $statusId,
            'UF_REMARK' => $remark,
            'UF_DATE' => $dateNow->format(\Bitrix\Main\Type\DateTime::getFormat())
        ];

        $rs = \Bitrix\Highloadblock\HighloadBlockTable::getById($hl_blockId)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($rs);
        $entity_data_class = $entity->getDataClass();

        $entity_data_class::add($params);
    }

    public static function fixRequestStatusMsgInHistory(int $iblockId, int $elemID = NULL, string $messageId, string $content)
    {
        switch ($iblockId) {
            case 7: //Accreditation
                $hl_blockId = self::ACC_STATUS_MSG_HISTORY;
                break;
            case 8: //Attestation
                $hl_blockId = self::ATT_STATUS_MSG_HISTORY;
                break;
                // case 4: //Archive-reference
                //     $hl_blockId = self::ARCH_STATUS_MSG_HISTORY;
                //     break;
            default:
                return;
        }

        $dateNow = new \DateTime();

        $hlblockLog = HL\HighloadBlockTable::getById($hl_blockId)->fetch();
        $entityLog = HL\HighloadBlockTable::compileEntity($hlblockLog);
        $entity_data_classLog = $entityLog->getDataClass();

        $rsDataLog = $entity_data_classLog::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "ASC"),
            //filter - не работает

        ));

        $elemLog = '';
        while ($logRow = $rsDataLog->Fetch()) {
            if ($logRow['UF_MESSAGE_ID'] == $messageId) {
                $elemLog = $logRow;
                die();
                break;
            }
        };
        if ($elemLog['ID'] > 0) {
            //Если нашли уже запись - значит расширяем Response
            $paramsLog = [
                'UF_ELEMENT_ID' => $logRow['UF_ELEMENT_ID'],
                'UF_MESSAGE_ID' => $logRow['UF_MESSAGE_ID'],
                'UF_RESPONSE_MESSAGE' => $content,
                'UF_REQUEST_MESSAGE' => $logRow['UF_REQUEST_MESSAGE'],
                'UF_DATE_RESPONSE' => $dateNow->format(\Bitrix\Main\Type\DateTime::getFormat()),
                'UF_DATE_REQUEST' => $logRow['UF_DATE_REQUEST']
            ];
            $entity_data_classLog::update($logRow['ID'], $paramsLog);
        } else {
            //Если нашли уже запись - значит расширяем Response
            $paramsLog = [
                'UF_ELEMENT_ID' => $elemID,
                'UF_MESSAGE_ID' => $messageId,
                'UF_RESPONSE_MESSAGE' => '',
                'UF_REQUEST_MESSAGE' => $content,
                'UF_DATE_RESPONSE' => '',
                'UF_DATE_REQUEST' => $dateNow->format(\Bitrix\Main\Type\DateTime::getFormat())
            ];
            //var_dump($paramsLog);
            $t = $entity_data_classLog::add($paramsLog);
            //die();
        }
    }

    public static function addErrorInLog($errorId, $errorMessage)
    {
        $hl_blockId = 11; //Errorslog

        $dateNow = new \DateTime();

        $params = [
            'UF_ERROR_ID' => $errorId,
            'UF_ERROR_MESSAGE' => $errorMessage,
            'UF_DATE' => $dateNow->format(\Bitrix\Main\Type\DateTime::getFormat()),
            'UF_USER' => 1 //TODO возможно заменить на global $USER
        ];

        $rs = \Bitrix\Highloadblock\HighloadBlockTable::getById($hl_blockId)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($rs);
        $entity_data_class = $entity->getDataClass();

        $entity_data_class::add($params);
    }

    public static function getPropertyListIdByXmlId(int $iblockId, string $propertyCode, string $xmlId = NULL)
    {
        $listElemId = '';
        $res = \CIBlockPropertyEnum::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode));
        while ($elem = $res->GetNext()) {
            if ($elem['XML_ID'] == $xmlId) {
                $listElemId = $elem['ID'];
            }
            if ($elem['DEF'] == 'Y') {
                $defElemId = $elem['ID'];
            }
        }
        return ($listElemId) ?? $defElemId;
    }

    public static function getPropertyListXmlIdById(int $iblockId, string $propertyCode, int $elemId = NULL)
    {
        $listElemXmlId = '';
        $res = \CIBlockPropertyEnum::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode));
        while ($elem = $res->GetNext()) {
            if ($elem['ID'] == $elemId) {
                $listElemXmlId = $elem['XML_ID'];
                break;
            }
            if ($elem['DEF'] == 'Y') {
                $defElemId = $elem['XML_ID'];
            }
        }
        return ($listElemXmlId) ?? $defElemId;
    }

    public static function getPropertyListXmlIdByElementValue(int $iblockId, string $propertyCode, string $elemValue = NULL)
    {
        $listElemXmlId = '';
        $res = \CIBlockPropertyEnum::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode));
        while ($elem = $res->GetNext()) {
            if ($elem['VALUE'] == $elemValue) {
                $listElemXmlId = $elem['XML_ID'];
            }
            if ($elem['DEF'] == 'Y') {
                $defElemId = $elem['XML_ID'];
            }
        }
        return ($listElemXmlId) ?? $defElemId;
    }

    public static function getPropertyListValueByElementId(int $iblockId, string $propertyCode, string $elemId = NULL)
    {
        $listValue = '';
        $res = \CIBlockPropertyEnum::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode));
        while ($elem = $res->GetNext()) {
            if ($elem['ID'] == $elemId) {
                $listValue = $elem['VALUE'];
            }
            if ($elem['DEF'] == 'Y') {
                $defElemId = $elem['VALUE'];
            }
        }
        return ($listValue) ?? $defElemId;
    }

    public static function genUuid($data = null): string
    {
        // Generate 16 bytes (128 bits) of timestamp or use the data passed into the function.
        $data = $data ?? microtime();
        
        //TODO Заккоментировал т.к. на php 8.1 падало в ошибку. Не совсем понял зачем нужна эта строка
        // assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function getIblockElementProperties($iblockID, $elementID): array
    {
        $result = [];
        $rs = \CIBlockElement::GetByID($elementID);
        if ($rsRow = $rs->GetNext()) {

            $result['ID'] = $rsRow['ID'];
            $result['NAME'] = $rsRow['NAME'];
            $result['EXTERNAL_ID'] = $rsRow['EXTERNAL_ID'];
        }
        $rsProps = \CIBlockElement::GetProperty($iblockID, $elementID, [], []);
        while ($arrProps = $rsProps->Fetch()) {
            $resultProps[$arrProps['CODE']][] = $arrProps['VALUE'];
        }
        foreach ($resultProps as $name => $value) {
            if (count($value) == 1) {
                $result[$name] = $value[0];
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public static function getIblockElementProps($iblockID, $elementID, $propertyCode): array
    {
        $result = [];

        $rsProps = \CIBlockElement::GetProperty($iblockID, $elementID, [], ['CODE' => $propertyCode]);
        while ($arrProps = $rsProps->Fetch()) {
            $resultProps[$arrProps['CODE']][] = $arrProps['VALUE'];
        }


        foreach ($resultProps as $name => $value) {
            if (count($value) == 1) {
                $result[$name] = $value[0];
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public static function getFileFromFtpServer(array $attachmentData): mixed
    {
        $attachInfo = false;
        $ftpUrl = self::FTP_URL;
        //Скачивание вложения
        $conn_id = ftp_connect($ftpUrl, self::FTP_PORT, self::FTP_TIMEOUT);
        if ($conn_id) {
            $login_res = ftp_login($conn_id, $attachmentData['UserName'], $attachmentData['Password']);
            if ($login_res) {
                ftp_set_option($conn_id, static::TIMEOUT_SEC, 600);
                ftp_set_option($conn_id, static::AUTOSEEK, FALSE);
                ftp_set_option($conn_id, static::TIMEOUT_SEC, FALSE);
                ftp_pasv($conn_id, true);
                mkdir($_SERVER['DOCUMENT_ROOT'] . '/upload/tmp_files/' . $attachmentData['uuid'], 0775);
                $pathOnServer = $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp_files/' . $attachmentData['uuid'] . $attachmentData['FileName'];
                ftp_get($conn_id, $pathOnServer, $attachmentData['FileName'], FTP_BINARY);
                //print_r(error_get_last());
                $fileContent = file_get_contents($pathOnServer);
                //var_dump($fileContent);
                if ($fileContent) {
                    $attachInfo = array('fullpath' => $pathOnServer, 'directory' => $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp_files/' . $attachmentData['uuid'] . '/');
                } else {
                    $attachInfo = false;
                    print_r(error_get_last());
                }
                ftp_close($conn_id);
            } else {
                throw new \Exception('Не удалось авторизоваться на FTP-сервере. Некорректный Логин или Пароль (' . $attachmentData['UserName'] . ':' . $attachmentData['Password'] . ')');
            }
        } else {
            throw new \Exception('Не удалось установить соединение с сервером FTP');
            //die('Не удалось установить соединение с сервером FTP');
        }

        return $attachInfo;
    }

    public static function uploadFileOnFtp(string $login, string $password, string $filepath, string $filename) : mixed
    {
        $arrayOfFileInfoForXml = false;
        $ftpUrl = self::FTP_URL;
        //Скачивание вложения
        $conn_id = ftp_connect($ftpUrl, self::FTP_PORT, self::FTP_TIMEOUT);
        if ($conn_id) {
            $login_res = ftp_login($conn_id, $login, $password);
            if ($login_res) {
                ftp_pasv($conn_id, true);
                $pathOnFtpServer = self::generateUuid();
                if (ftp_mkdir($conn_id, $pathOnFtpServer)) {
                    //var_dump('/'.$pathOnFtpServer.'/'.$filename);
                    //var_dump($_SERVER['DOCUMENT_ROOT'].$filepath);
                    if (ftp_put($conn_id, '/' . $pathOnFtpServer . '/' . $filename, $_SERVER['DOCUMENT_ROOT'] . $filepath, FTP_BINARY)) {
                        $arrayOfFileInfoForXml = array('uuid' => $pathOnFtpServer, 'fileName' => '/' . $filename);
                    } else {
                        print_r(error_get_last());
                        die('Ошибка загрузки на FTP-ресурс');
                    }
                } else {
                    die('Ошибка создания директории на FTP-ресурс');
                }
                ftp_close($conn_id);
            } else {
                //http_response('500');
                die('Не удалось авторизоваться на FTP-сервере. Некорректный Логин или Пароль (' . $login . ':' . $password . ')');
            }
        } else {
            //http_response('500');
            die('Не удалось установить соединение с сервером FTP');
        }

        return $arrayOfFileInfoForXml;
    }

    public static function log(string $fileName, $logData)
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/logs/' . $fileName;
        if (is_array($logData)) {
            file_put_contents($path, var_export($logData, 1));
        } elseif (is_object($logData)) {
            file_put_contents($path, var_dump($logData));
        } else {
            file_put_contents($path, $logData);
        }
    }

    public static function generateUuid()
    {
        return self::genUuid();
    }

    /* Получить значение поля SelectType */
    public static function getSelectType($id){
        switch($id){
			case 0:{
				return "В личном кабинете";
			}
			case 1:{
				return "Заказным почтовым отправлением";
			}
			case 2:{
				return "Получить лично";
			}
			default:
			{
				return "selectType is not equal to 0, 1 or 2";
			}
		}
    }

    /* Генерация аббревиатуры */
    public static function getFIOAbbreviated($f, $i, $o = ''){
        return substr($i,0,2).'. '.(($o)?substr($o, 0, 2).'. ':'').$f;
    }

    /* Возвращает организационно-правовую форму организации */
    public static function getOPF($zayavitelqInfo){
        //TODO Уточнить нужен ли ОПФ и доделать, если нужен
        return ' ';
    }

    /**
     * Удаляет переносы и лишние пробелы из строки
     */
    public static function removeSpacesAndHyphens($str)
    {
        $str = str_replace(array("\r\n", "\r", "\n"), "", $str);
        $str = preg_replace('/^ +| +$|( ) +/m', '$1', $str);
        return $str;
    }

    public static function getRequestSoap(string $requestBody){        
        $input = $requestBody;
		//Очистка конкретных тегов - будет переписано
		$xmlRequest = $input;
        $xmlRequest = str_replace("<pmnt:", "<", str_replace("</pmnt:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<org:", "<", str_replace("</org:", "</", $xmlRequest ));
        $xmlRequest = str_replace("<com:", "<", str_replace("</com:", "</", $xmlRequest ));
        //$xmlRequest = str_replace("<tns:", "<", str_replace("</tns:", "</", $xmlRequest ));
		for($i=0;$i<100;$i++){
            $xmlRequest = str_replace("<ns$i:", "<", str_replace("</ns$i:", "</", $xmlRequest));
            $xmlRequest = str_replace("<sb$i:", "<", str_replace("</sb$i:", "</", $xmlRequest ));
			$xmlRequest = str_replace(array("<xz$i:"), "<", str_replace("</xz$i:", "</", $xmlRequest));
		}
		$cleanXml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xmlRequest);
		$xml = simplexml_load_string($cleanXml);
        if ($xml){
            return $xml;
        } else {
            $error_message = '';
            foreach(libxml_get_errors() as $error) {
                $error_message .= "\t ".$error->message;
            }
            throw new \Exception('Не удалось преобразовать XML-файл в объкт: '.$error_message);
        }
    }

    /**
     * Получить идентификатор инфоблока по символьному коду
     *
     * @param string $code
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIblockIdByCode(string $code): int
    {
        $result = \Bitrix\Iblock\IblockTable::getRow([
            'filter' => ['=CODE' => $code],
            'select' => ['ID']
        ]);
        if (!empty($result)) {
            return (int)$result['ID'];
        }

        return 0;
    }

    public static function getIblockElementCodeById($iblockCode, int $elementId): string
    {
        $iblockId = self::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'select' => array('ID', 'CODE', 'IBLOCK_ID'), // выбираемые поля, без свойств. Свойства можно получать на старом ядре \CIBlockElement::getProperty
            'filter' => array('IBLOCK_ID' => $iblockId, 'ID' => $elementId), // фильтр только по полям элемента, свойства (PROPERTY) использовать нельзя
        ]);
        $code = '';
        if($item = $dbItems->fetch()){
            $code = $item['CODE'];
        }

        return $code;
    }

    /**
     * Получить параметры инфоблока по id
     *
     * @param integer $filter
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIblockData(array $filter): array
    {
        $result = \Bitrix\Iblock\IblockTable::getList([
            'select' => ['*'],
            'filter' => $filter,
            'limit' => 1]);
            $iblockData = $result->fetch();
            if (!empty($iblockData)) {
    
                return $iblockData;
            }
    
            return [];
    }

    /**
     * Получить свойства инфоблока
     *
     * @param int $iblockId
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIblockProperties(int $iblockId): array
    {
        $iblockProps = [];
        $res = \Bitrix\Iblock\PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, '!CODE' => null],
            'select' => [
                'ID',
                'CODE',
                'PROPERTY_TYPE',
                'LINK_IBLOCK_ID',
                'MULTIPLE',
                'USER_TYPE',
                'USER_TYPE_SETTINGS',
                'IS_REQUIRED'
            ]
        ]);
        $ids = [];
        while ($row = $res->fetch()) {
            if ($row['PROPERTY_TYPE'] == 'L') {
                $ids[] = $row['ID'];
            }
            $row['USER_TYPE_SETTINGS'] = unserialize($row['USER_TYPE_SETTINGS']);
            $iblockProps[$row['CODE']] = $row;
        }

        return self::getEnumItems($iblockProps, $ids);
    }

    public static function getIblockPropertiesByPropCode(int $iblockId, array $propCodes): array
    {
        $iblockProps = [];
        $res = \Bitrix\Iblock\PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $propCodes],
            'select' => [
                'ID',
                'CODE',
                'PROPERTY_TYPE',
                'LINK_IBLOCK_ID',
                'MULTIPLE',
                'USER_TYPE',
                'USER_TYPE_SETTINGS',
                'IS_REQUIRED'
            ]
        ]);
        $ids = [];
        while ($row = $res->fetch()) {
            if ($row['PROPERTY_TYPE'] == 'L') {
                $ids[] = $row['ID'];
            }
            $row['USER_TYPE_SETTINGS'] = unserialize($row['USER_TYPE_SETTINGS']);
            $iblockProps[$row['CODE']] = $row;
        }

        return self::getEnumItemsWithXmlId($iblockProps, $ids);
    }

    /**
     * Получить элементы перечисления
     *
     * @param array $iblockProps
     * @param array $ids
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getEnumItems(array $iblockProps, array $ids): array
    {
        $queryObject = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'order' => ['SORT' => 'ASC', 'VALUE' => 'ASC'],
            'filter' => ['PROPERTY_ID' => $ids],
            'select' => ['ID', 'VALUE', 'PROPERTY_CODE' => 'PROPERTY.CODE']
        ]);
        while ($row = $queryObject->fetch()) {
            $iblockProps[$row['PROPERTY_CODE']]['ENUM'][$row['ID']] = $row['VALUE'];
        }

        return $iblockProps;
    }

    public static function getEnumItemsWithXmlId(array $iblockProps, array $ids): array
    {
        $queryObject = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'order' => ['SORT' => 'ASC', 'VALUE' => 'ASC'],
            'filter' => ['PROPERTY_ID' => $ids],
            'select' => ['ID', 'VALUE', 'PROPERTY_CODE' => 'PROPERTY.CODE', 'XML_ID']
        ]);

        while ($row = $queryObject->fetch()) {
            $iblockProps[$row['PROPERTY_CODE']]['ENUM'][$row['ID']]['VALUE'] = $row['VALUE'];
            $iblockProps[$row['PROPERTY_CODE']]['ENUM'][$row['ID']]['XML_ID'] = $row['XML_ID'];
            $iblockProps[$row['PROPERTY_CODE']]['ENUM'][$row['ID']]['VALUE_ID'] = (int)$row['ID'];
        }

        return $iblockProps;
    }

    public static function getPropertyIdByCode(int $iblockId, string $propertyCode):int
    {
        $propId = -1;
        $dbItems = \Bitrix\Iblock\PropertyTable::getList([
            'select' => ['ID'],
            'filter' => ['CODE' => $propertyCode, 'IBLOCK_ID' => $iblockId],
        ]);

        if($item = $dbItems->fetch()){
            $propId = $item['ID'];
        }

        return $propId;
    }

    public static function getEnumXMLIDById(int $iblockId, string $propertyEnumCode, int $propertyEnumValueId):string
    {
        $propertyId = self::getPropertyIdByCode($iblockId, $propertyEnumCode);
        $dbItems = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'select' => ['XML_ID'],
            'filter' => ['PROPERTY_ID' => $propertyId,'ID' => $propertyEnumValueId],
        ]);

        $enumXmlId = '';
        if($item = $dbItems->fetch()){
            $enumXmlId = $item['XML_ID'];
        }

        return $enumXmlId;
    }

    public static function getPropValues(int $iblockId, array $elementIdList ,array $propCodes): array
    {
        $propValues = [];
        \CIBlockElement::GetPropertyValuesArray(
            $propValues,
            $iblockId,
            ['ID' => $elementIdList],
            ['CODE' => $propCodes]
        );
        $preparedProps = [];
        foreach ($propValues as $elementId => $propItem) {
            foreach ($propItem as $propCode => $propValue) {
                if ($propValue['PROPERTY_TYPE'] == 'S' && $propValue['USER_TYPE'] == 'Date') {
                    $preparedProps[$elementId]['PROPERTY_' . $propCode] = new \DateTime($propValue['VALUE']);
                } else {
                    $preparedProps[$elementId]['PROPERTY_' . $propCode] = $propValue['VALUE'];
                }
            }
        }

        return $preparedProps;
    }

    public static function getUserInfo(array $userIdList): array
    {
        $dbItems = UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME'],
            'filter' => ['ID' => $userIdList]
        ]);
        $userInfo = [];
        while ($user = $dbItems->fetch()) {
            $userInfo[$user['ID']] = $user['LAST_NAME'] . ' ' . $user['NAME'];
        }

        return $userInfo;
    }

    /* Получает заполненный список свойств для ИБ по $requestData */
    public static function getProps($requestData, $itemName, &$arr){
        foreach($requestData as $key => $item){
            if ($key == '@attributes'){
                foreach ($item as $name => $prop){
                    $arr[$itemName.'_'.$name] = $prop;
                }
            } else {
                foreach ($item as $name => $prop){
                    self::getProps($prop, $name, $arr);
                }
            }
        }
    }

    public static function getPropertyEnumValue(int $iblockId, string $propertyCode, string $propertyXmlId)
    {
        $queryObject = \Bitrix\Iblock\PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode],
            'select' => ['ID']
        ]);
        $propertyId = null;
        if($propItem = $queryObject->fetch()){
            $propertyId = $propItem['ID'];
        }

        $propertyEnumValueId = null;
        $queryEnumObject = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $propertyId, 'XML_ID' => $propertyXmlId],
            'select' => ['ID']
        ]);
        if($propEnumItem = $queryEnumObject->fetch()){
            $propertyEnumValueId = $propEnumItem['ID'];
        }

        return $propertyEnumValueId;
    }

    public static function getEnumStringValue(int $iblockId, string $propertyCode, string $propertyXmlId): array
    {
        $queryObject = \Bitrix\Iblock\PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode],
            'select' => ['ID']
        ]);

        $propertyId = null;
        if($propItem = $queryObject->fetch()){
            $propertyId = $propItem['ID'];
        }
        $propertyEnumStringValue = [];
        $queryEnumObject = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $propertyId, 'XML_ID' => $propertyXmlId],
            'select' => ['ID','VALUE']
        ]);
        if ($propEnumItem = $queryEnumObject->fetch()) {
            $propertyEnumStringValue[$propEnumItem['ID']] = $propEnumItem['VALUE'];
        }

        return $propertyEnumStringValue;
    }

    public static function getEnumIdByTextValue(int $iblockId, string $propCode, string $enumTextValue): int
    {
        $queryObject = \Bitrix\Iblock\PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $propCode],
            'select' => ['ID']
        ]);

        $propertyId = null;
        if($propItem = $queryObject->fetch()){
            $propertyId = $propItem['ID'];
        }

        $queryEnumObject = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $propertyId, 'VALUE' => $enumTextValue],
            'select' => ['ID','VALUE']
        ]);
        $propertyEnumId = 0;
        if ($propEnumItem = $queryEnumObject->fetch()) {
            $propertyEnumId = $propEnumItem['ID'];
        }

        return $propertyEnumId;
    }

    public static function getSectionIdByCode(int $iblockId, string $iblockSectionCode): int
    {
        $sectionId = 0;
        $dbItems = \Bitrix\Iblock\SectionTable::getList([
            'select' => ['ID', 'IBLOCK_ID'],
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $iblockSectionCode],
        ]);

        if($sectionItem = $dbItems->fetch())
            $sectionId = $sectionItem['ID'];

        return $sectionId;
    }

    public static function getUsersStringFromGroup(string $groupCode): string
    {
        $result = \Bitrix\Main\UserGroupTable::getList([
            'filter' => ['GROUP_CODE' => $groupCode, 'GROUP.ACTIVE' => 'Y'],
            'select' => ['GROUP_ID', 'GROUP_CODE' => 'GROUP.STRING_ID', 'USER_ID']
        ]);
        $groupInfo = [];
        while ($item = $result->fetch()) {
            $groupInfo[$item['GROUP_ID']]['USER_ID_LIST'][] = $item['USER_ID'];
        }
        $emailListString = '';
        if ($groupInfo) {
            $groupId = array_key_first($groupInfo);
            $result = \Bitrix\Main\UserTable::getList([
                'select' => ['ID', 'EMAIL'],
                'filter' => ['ID' => $groupInfo[$groupId]['USER_ID_LIST']]
            ]);
            while($userItem = $result->fetch()){
                $emailList .= $userItem["EMAIL"].';';
            }
            $emailListString = trim($emailList, ';');
        }


        return $emailListString;
    }
}

/**
 * Логирование запросов в ИБ
 *  */
class IblockLog{

    private $iblock_id;

    public function __construct($iblock_id) {
        $this->iblock_id = $iblock_id;
    }

    public function set($guid, $request)
    {
        $ib = new \CIBlockElement;
        $responseElementId = $ib->Add([
            'NAME' => $guid,
            'IBLOCK_ID' => $this->iblock_id,
            'PROPERTY_VALUES' => [
                'GUID' => $guid,
                'Request' => $request
            ]
        ]);

        return $responseElementId;
    }
}
