<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService\VS;

class Attestation implements \MyCompany\WebService\VS
{
    const IBLOCK_ID = 18;
    const WORKFLOW_ID = 7;

    public $messageId = '';
    public $workflowId = '';
    public $replyTo = '';
    public $nodeValue = '';

    // public $vsCode = 'IQEpguRosatoIQEpguMyCompanyExpSpgumAcrSpgu';
    public $vsCode = 'IQEpguMyCompanyExpSpgu';

    private $orderData = [];
    private $mess = [
        "ulFullName" => "Полное наименование юридического лица",
        "ulKratkoeName" => "Сокращенное наименование юридического лица",
        "ulOGRN" => "ОГРН",
        "ulINN" => "ИНН",
        "ulKPP" => "КПП",
        "ulHeadSurname" => "Фамилия руководителя",
        "ulHeadName" => "Имя руководителя",
        "ulHeadPatronymic" => "Отчество адрес",
        "ulHeadRole" => "Должность заявителя",
        "ulEmail" => "Email",
        "ulPhone" => "Контактный телефон",
        "ulPhoneSotrudnik" => "Контактный телефон сотрудника",
        "ulEmailSotrudnik" => "Email сотрудника",
        "ulYuridichAddress" => "Отчество получателя",
        "ulFactAddress" => "Фактический адрес",
        "flSurname" => "Фамилия",
        'flName' => "Имя",
        "flPatronymic" => "Отчество",
        "flBirthdate" => "Дата рождения",
        "flSeriya" => "Серия паспорта",
        "flNomer" => "Номер паспорта",
        "flDateVidachi" => "Дата выдачи паспорта",
        "flKemVidan" => "Кем выдан паспорт",
        "flFactAddress" => "Фактический адрес",
        "flPhone" => "Контактный телефон",
        "flEmail" => "Email",
        "flMestoRozhdeniya" => "Место рождения",
        "flSNILS" => "СНИЛС",
        "flPasportSeriya" => "Паспорт - серия",
        "flPasportNomer" => "Паспорт - номер",
        "flPasportOrgan" => "Паспорт - кем выдан",
        "flPasportOrganKod" => "Паспорт - код организации",
        "flAdresRegistraczii" => "Адрес регистрации",
        "NomerSvidetelqstva" => "Номер свидетельства",
        "DataVyhdachi" => "Дата выдачи",
        "Vladelecz_FIO" => "ФИО владельца",
        "UtvDok_Naimenovanie" => "Нименование документа",
        "UtvDok_Nomer" => "Номер документа",
        "UtvDok_Data" => "Дата документа",
        "Platelqshhik_INN" => "ИНН отправителя",
        "Poluchatelq_INN" => "ИНН получателя",
        "Poluchatelq_KPP" => "КПП получателя",
        "DataPlatezha" => "Дата платежа",
        "Nachislenie_UID" => "Начисление",
        "Platyozh_UID" => "Платеж",
        "ulYuridichAdres" => "Юридический адрес",
        "Ehkspert_Familiya" => "Эксперт - Фамилия",
        "Ehkspert_Name" => "Эксперт - Имя",
        "Ehkspert_Otchestvo" => "Эксперт - Отчество",
        "NomerPrikaza" => "Номер приказа",
        "DataPrikaza" => "Дата приказа",
		"OblastqAttestaczii" => "Область аккредитации",
		"ulAdresResult" => "Адрес отправления",

        //Цели услуг
        "IQEpguMyCompanyExpAttestacziya" => "Пройти аттестацию эксперта в ОИАЭ",
        "IQEpguMyCompanyExpCompetence" => "Подтвердить компетентность в ОИАЭ",
        "IQEpguMyCompanyExpStop" => "Прекратить действие свидетельства об аттестации эксперта",
        "IQEpguMyCompanyExpOriginal" => "Выдача свидетельства об аттестации эксперта в ОИАЭ",
        "IQEpguMyCompanyExpEdit" => "Переоформление свидетельства об аттестации эксперта",
        "IQEpguMyCompanyExpDublikat" => "Выдача дубликата свидетельства об аттестации эксперта",
        "IQEpguMyCompanyExpKopiya" => "Выдача копии свидетельства об аттестации эксперта в ОИАЭ",
        "IQEpguMyCompanyExpReestr" => "Предоставление сведений из реестра экспертов",
    ];

    private $messagePrimaryContent;
    private $celqUslugi;
    private $zayavitelqInfo;
    private $ZayavitelqType;

    public function setData(array $request)
    {
        $this->messageId = trim($this->getNodeFromXmlArray($request, 'MessageID'));
        //Проверка на уникальный MessageId
        if (\MyCompany\WebService\Helper::checkUniqueMessageId(static::IBLOCK_ID, $this->messageId)) {
            die('MessageId не уникальный');
        }

        $this->replyTo = $this->getNodeFromXmlArray($request, 'ReplyTo');
        $this->messagePrimaryContent = $this->getNodeFromXmlArray($request, 'MessagePrimaryContent');
        $this->celqUslugi = array_key_first($this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['CelqUslugi']);

        $this->orderData["IBLOCK_ID"] = static::IBLOCK_ID;
        $this->orderData["NAME"] = $this->messagePrimaryContent[$this->vsCode]["OrderId"];
        $this->orderData["XML_ID"] = $this->messageId;
        $this->orderData["CODE"] = $this->messageId;
        $this->orderData['PREVIEW_TEXT'] = $this->celqUslugi;

        /* проверяем заявителя */
        if ($this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqType']){
            $this->ZayavitelqType = $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqType'];
        } else {
            if (substr(array_key_first($this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqInfo']), -2) == 'Fl'){
                $this->ZayavitelqType = 'Физическое лицо';
            } else {
                $this->ZayavitelqType = '';
            }
        }

        $zayavitelqInfo = $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqInfo'];

        //TODO Будент баг печтаной формы, если у заявителя отсутствует отчество
        $this->zayavitelqInfo = $zayavitelqInfo[array_key_first($zayavitelqInfo)];
        
        $orderDataProps = $this->getOrderDataProps($this->messagePrimaryContent);
        $orderDataProps['ZayavitelqInfo'].=$orderDataProps['CelqUslugiData'];
        unset($orderDataProps['CelqUslugiData']);

        /* Вложения */
        $attachmentsData = [];
        $appliedDocuments = $this->getNodeFromXmlArray($request, 'AppliedDocuments');
        //Сделать такой же блок получения вложений из тега (RefAttachmentHeaderList - узел в XML)
        if(is_array($appliedDocuments)){
            if(is_array($appliedDocuments["AppliedDocument"])){
                 foreach($appliedDocuments["AppliedDocument"] as $docData){
					if(is_array($docData)){ 
						$attachmentsData[] = $docData;
					}else{
						$attachmentsData[] = $appliedDocuments["AppliedDocument"];
                        break;
					}
                }
            }
        }
		$orderDataProps["AppliedDocuments"] = \MyCompany\WebService\Attachments::getAttachmentsData($this->messageId, $attachmentsData);
		$this->nodeValue = '';
		$fSAttachment = $this->getNodeFromXmlArray($request, 'FSAttachment');
		if ((is_array($fSAttachment)) && (!$orderDataProps["AppliedDocuments"])) {
            throw new \Exception('Вложения, ожидаемые в виде сведений, не обнаружены в системе:');
        }

        $this->orderData["PROPERTY_VALUES"] = $orderDataProps;
    }

    /* получаем бланк */
    public function getBlankType(){
		if(!$this->celqUslugi){
			$this->celqUslugi = 'IQEpguMyCompanyExpReestr';
		}

        if ($this->celqUslugi == 'IQEpguMyCompanyExpReestr' && !empty($this->ZayavitelqType)) {
            $this->celqUslugi = 'IQEpguMyCompanyExpReestrFL';
        }

        $blank = 'attestation/'.$this->celqUslugi;
        return $blank;
    }

    /* Формируем параметры для создания печатной формы */
    public function getParamsPrintedForm(){
        $messagePrimaryContent = $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)];
        $celqUslugiParams = $messagePrimaryContent['CelqUslugi'][$this->celqUslugi];
		if(!$celqUslugiParams){
 			$celqUslugiParams = [];
		}

        if (($this->ZayavitelqType == 'Физическое лицо') || ($this->ZayavitelqType == '1')){
            $FIOAbbreviated = \MyCompany\WebService\Helper::getFIOAbbreviated(
                $this->zayavitelqInfo['flSurname'], 
                $this->zayavitelqInfo['flName'], 
                $this->zayavitelqInfo['flPatronymic']
            );
        } else {
            $FIOAbbreviated = \MyCompany\WebService\Helper::getFIOAbbreviated(
                $this->zayavitelqInfo['ulHeadSurname'], 
                $this->zayavitelqInfo['ulHeadName'], 
                $this->zayavitelqInfo['ulHeadPatronymic']
            );
        }

        $additionalParams = [
            'OPF' => \MyCompany\WebService\Helper::getOPF($this->zayavitelqInfo),
            'OPFLicza' => \MyCompany\WebService\Helper::getOPF([]),
            'FIOAbbreviated' => $FIOAbbreviated,
            'StatementDate' => $messagePrimaryContent['StatementDate'],
            'OrderId' => $messagePrimaryContent['OrderId']
        ];
        
        return array_merge(
            $this->zayavitelqInfo, 
            $celqUslugiParams,
            $additionalParams
        );
    }

    /**
     * Обходим messagePrimaryContent и заполняем $orderDataProps для сохранения в елементе ИБ
     * @param mixed $messagePrimaryContent
     *
     * @return array
     */
    private function getOrderDataProps($messagePrimaryContent)
    {
        $orderDataProps = [];
        $orderDataProps['ReplyTo'] = \MyCompany\WebService\Helper::removeSpacesAndHyphens($this->replyTo);
        $orderDataProps['Request'] = file_get_contents('php://input');
        $orderDataProps['Department'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'Department');
        $orderDataProps['ServiceCode'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ServiceCode');
        $orderDataProps['TargetCode'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'TargetCode');
        $orderDataProps['StatementDate'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'StatementDate');

        /* IQEpguMyCompanyAcrSpguFl */
        $iQEpguMyCompanyExpSpguFl = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyExpSpguFl');
        if (is_array($iQEpguMyCompanyExpSpguFl)) {
            $orderDataProps['IQEpguMyCompanyExpSpguFl'] = "<b>Физическое лицо:</b><br>";
            foreach ($iQEpguMyCompanyExpSpguFl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyExpSpguFl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyExpSpguFl'] .= "</u>" . ": " . $val . "<br>";
            }
        }
        $this->nodeValue = '';
        /* IQEpguMyCompanyAcrSpguUl */
        $iQEpguMyCompanyExpSpguUl = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyExpSpguUl');
        if (is_array($iQEpguMyCompanyExpSpguUl)) {
            $orderDataProps['IQEpguMyCompanyExpSpguUl'] = "<b>Юридическое лицо:</b><br>";
            foreach ($iQEpguMyCompanyExpSpguUl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyExpSpguUl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyExpSpguUl'] .= "</u>" . ": " . $val . "<br>";
            }
        }
        $orderDataProps['ZayavitelqInfo'] = ($orderDataProps['IQEpguMyCompanyExpSpguFl']) ?? $orderDataProps['IQEpguMyCompanyExpSpguUl'];

        $orderDataProps['SelectType'] = \MyCompany\WebService\Helper::getSelectType($this->getNodeFromXmlArray($this->messagePrimaryContent, 'SelectType'));

        /* CelqUslugi */
        $CelqUslugi = $this->getNodeFromXmlArray($messagePrimaryContent, 'CelqUslugi');
        if (!empty($CelqUslugi)) {
            foreach ($CelqUslugi as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['CelqUslugi'] = $this->mess[$key] ?? $key;
                $orderDataProps['CelqUslugiData'] = '<b>Предоставленные данные для оказания услуги:</b><br \>';
                foreach ($val as $key_2 => $val_2) {
                    if(empty($val_2)){continue;}
                    if (is_array($val_2)) {
                        foreach ($val_2 as $key_3 => $val_3) {
                            if(empty($val_3)){continue;}
                            $orderDataProps['CelqUslugiData'] .= "<u>" . (($this->mess[$key_3]) ?? $key_3);
                            $orderDataProps['CelqUslugiData'] .= "</u>: " . $val_3 . "<br \>";
                        }
                    } else {
                        $orderDataProps['CelqUslugiData'] .= "<u>" . (($this->mess[$key_2]) ?? $key_2);
                        $orderDataProps['CelqUslugiData'] .= "</u>: " . $val_2 . "<br \>";
                    }
                }
                unset($val);
            }
        }

        unset($key, $key_2, $val_2);
        $orderDataProps['CelqUslugi'] = \MyCompany\WebService\Helper::removeSpacesAndHyphens($orderDataProps['CelqUslugi']);

        return $orderDataProps;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getIblockId(): int
    {
        return static::IBLOCK_ID;
    }

    public function getWorkflowId(): int
    {
        return static::WORKFLOW_ID;
    }

    public function add(): int
    {
        $data = $this->orderData;
        $el = new \CIblockElement();
        $data['PROPERTY_VALUES']['StatusSended'] = 0;
        $newOrderId = $el->add($data);

        if ($newOrderId) {
            $this->setDocument($newOrderId);
            echo $newOrderId;
        } else {
            throw new \Exception('Ошибка создания элемента инфоблока:' . $el->LAST_ERROR);
        }

        return $newOrderId;
    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName): string | array
    {
        //$resultNodeValue=($nodeValue)?$nodeValue:false;
        foreach ($xmlArray as $key => $value) {
            if ($key === $nodeName) {
                $this->nodeValue = $value;
                break;
            } else {
                if (gettype($value) == 'array') {
                    $this->getNodeFromXmlArray($value, $nodeName);
                }
            }
        }
        return $this->nodeValue;
    }

    private function getFileArrayFromStorage(string $messageId, string $fileName, string $mimeType): array | false
    {
        $fileData = false;
        $storagePath = $_SERVER["DOCUMENT_ROOT"] . "/upload/attachments/";
        $currentFilePath = $storagePath . $messageId . '/' . $fileName;
        if (file_exists($currentFilePath)) {
            $newPath = $currentFilePath . '.' . explode('/', $mimeType)[1];
            rename($currentFilePath, $currentFilePath . '.' . explode('/', $mimeType)[1]);
            $fileData = \CFile::MakeFileArray($newPath);
        };

        return $fileData;
    }

    private function setDocument($element_id){
        $formParams = $this->getParamsPrintedForm();
        $formParams['form_id'] = $element_id;
        $formParams['Date_registration'] = date('d.m.Y');

        /* Печатная форма */
        $printedForm = new \MyCompany\WebService\PrintedForm(
            $formParams,
            $this->getBlankType(),
            $this->mess
        );

        if(file_exists($printedForm->getPath())){
            $orderDataProps['PrintedForm'] = \CFile::MakeFileArray($printedForm->getPath());
            \CIBlockElement::SetPropertyValuesEx(
                $element_id,
                $this->getIblockId(),
                $orderDataProps
            );
        }
    }

    public function searchElement()
    {
        // TODO: Implement searchElement() method.
    }

    public function updateElement()
    {
        // TODO: Implement updateElement() method.
    }
}
