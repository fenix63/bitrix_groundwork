<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService\VS;

class Accreditation implements \MyCompany\WebService\VS
{
    const IBLOCK_ID = 16;
    const WORKFLOW_ID = 6;

    public $messageId = '';
    public $replyTo = '';
    public $nodeValue = '';
    public $vsCode = 'IQEpguMyCompanyAcrSpgu';

    private $orderData = [];
    private $mess = [
        "ulFullName" => "Полное наименование юридического лица",
        "ulKratkoeName" => "Сокращенное наименование юридического лица",
        "ulOGRN" => "ОГРН",
        "ulINN" => "ИНН",
        "ulKPP" => "КПП",
        "ulHeadSurname" => "Фамилия руководителя",
        "ulHeadName" => "Имя руководителя",
        "ulHeadPatronymic" => "Отчество",
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
        'flAdresRegistraczii' => "Адрес постоянной регистрации",
        'flAdresFakt' => "Фактический адрес",
        "flPasportSeriya" => "Паспорт - серия",
        "flPasportNomer" => "Паспорт - номер",
        "flPasportOrgan" => "Паспорт - кем выдан",
        "flPasportOrganKod" => "Паспорт - код организации",
        "flMestoRozhdeniya" => "Место рождения",
        "flPasportData" => "Дата выдачи",
        "NomerAttestata" => "Номер аттестата",
        "DataAttestata" => "Дата выдачи аттестата",
        "OrganizationType" => "Тип организации",
        "AddressMOD" => "Адрес места осуществления деятельности",
        "PolnoeNaimenovanieLicza" => "Полное наименование лица",
        "KratkoeNaimenovanieLicza" => "Краткое наименование лица",
        "OGRNLicza" => "ОГРН лица",
        "INNLicza" => "ИНН лица",
        "ulYuridichAdres" =>"Юридический адрес",
        "ulFaktAdres" =>"Фактический адрес",
        "NomerPrikaza" =>"Номер приказа",
        "DataPrikaza" =>"Дата приказа",
        "Osnovanie" =>"Основание",
        "Platelqshhik_INN" => "ИНН отправителя",
        "Poluchatelq_INN" => "ИНН получателя",
        "Poluchatelq_KPP" => "КПП получателя",
        "DataPlatezha" => "Дата платежа",
        "Nachislenie_UID" => "Начисление",
        "Platyozh_UID" => "Платеж",
        "Prichina" => "Причина",
        "DataKontrolya" => "Дата контроля",
        "Website" => "Сайт организации в сети Интернет",
        "NomerPredpisaniya" => "Номер предписания",
        "DataPredpisaniya" => "Дата предписания",

        //Цели услуг
        "IQEpguMyCompanyAcrAkkreditacziya" => "Аккредитация в ОИАЭ",
        "IQEpguMyCompanyAcrRasshirenie" => "Расширение области аккредитации",
        "IQEpguMyCompanyAcrSokrashhenie" => "Сокращение области аккредитации",
        "IQEpguMyCompanyAcrKontrolq" => "Проведение планового инспекционного контроля",
        "IQEpguMyCompanyAcrPredpisanie" => "Выполнение предписания об устранении несоответствий",
        "IQEpguMyCompanyAcrAttestat" => "Выдача аттестата аккредитации",
        "IQEpguMyCompanyAcrDublikat" => "Выдача дубликата аттестата аккредитации",
        "IQEpguMyCompanyAcrPereoformlenie" => "Переоформление аттестата аккредитации",
        "IQEpguMyCompanyAcrReestr" => "Запрос сведений из реестра аккредитованных лиц (для ФЛ и ЮЛ)",
        "IQEpguMyCompanyAcrPrekrashhenie" => "Прекращение действия аттестата аккредитации",
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
        $this->celqUslugi = array_key_first(
            $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['CelqUslugi']
        );

        $this->orderData["IBLOCK_ID"] = static::IBLOCK_ID;
        $this->orderData["NAME"] = $this->messagePrimaryContent[$this->vsCode]["OrderId"];
        $this->orderData["XML_ID"] = $this->messageId;
        $this->orderData["CODE"] = $this->messageId;
        $this->orderData['PREVIEW_TEXT'] = $this->celqUslugi;

        $this->ZayavitelqType = $this->messagePrimaryContent[array_key_first(
            $this->messagePrimaryContent
        )]['ZayavitelqType'];

        if ((trim($this->ZayavitelqType) == 'Физическое лицо')||(trim($this->ZayavitelqType) == 'ZayavitelqType1')) {
            $this->zayavitelqInfo = $this->messagePrimaryContent[array_key_first(
                $this->messagePrimaryContent
            )]['ZayavitelqInfo']['IQEpguMyCompanyAcrSpguFl'];
        } else {
            $this->zayavitelqInfo = $this->messagePrimaryContent[array_key_first(
                $this->messagePrimaryContent
            )]['ZayavitelqInfo']['IQEpguMyCompanyAcrSpguUl'];
        }

        $orderDataProps = $this->getOrderDataProps();
        if(!empty($orderDataProps['IQEpguMyCompanyAcrSpguFl'])){
            $orderDataProps['IQEpguMyCompanyAcrSpguFl'].=$orderDataProps['CelqUslugiData'];
        }else{
            $orderDataProps['IQEpguMyCompanyAcrSpguUl'].=$orderDataProps['CelqUslugiData'];
        }
        unset($orderDataProps['CelqUslugiData']);

        /* Вложения */
        $attachmentsData = [];
        $appliedDocuments = $this->getNodeFromXmlArray($request, 'AppliedDocuments');
        if (is_array($appliedDocuments)) {
            if (is_array($appliedDocuments["AppliedDocument"])) {
                foreach ($appliedDocuments["AppliedDocument"] as $docData) {
                    if (is_array($docData)) {
                        $attachmentsData[] = $docData;
                    } else {
                        $attachmentsData[] = $appliedDocuments["AppliedDocument"];
                        break;
                    }
                }
            }
        }
        $orderDataProps["AppliedDocuments"] = \MyCompany\WebService\Attachments::getAttachmentsData(
            $this->messageId,
            $attachmentsData
        );
		$this->nodeValue = '';
		$fSAttachment = $this->getNodeFromXmlArray($request, 'FSAttachment');
		if ((is_array($fSAttachment)) && (!$orderDataProps["AppliedDocuments"])) {var_dump($fSAttachment);
            throw new \Exception('Вложения, ожидаемые в виде сведений, не обнаружены в системе:');
        }

        $this->orderData["PROPERTY_VALUES"] = $orderDataProps;
    }

    /* получаем бланк */
    public function getBlankType()
    {
        $blank = 'accreditation/' . $this->celqUslugi;
        if ($this->celqUslugi == 'IQEpguMyCompanyAcrReestr') {
            $celqUslugiData = $this->messagePrimaryContent[array_key_first(
                $this->messagePrimaryContent
            )]['CelqUslugi'][$this->celqUslugi];
            if (array_key_exists('NomerAttestata', $celqUslugiData)) {
                if ($this->ZayavitelqType == 'Физическое лицо') {
                    return $blank . '_FL_Nomer';
                } else {
                    return $blank . '_UL_Nomer';
                }
            } else {
                if ($this->ZayavitelqType == 'Физическое лицо') {
                    return $blank . '_FL_Liczo';
                } else {
                    return $blank . '_UL_Liczo';
                }
            }
        } else {
            return $blank;
        }
    }

    /* Формируем параметры для создания печатной формы */
    public function getParamsPrintedForm()
    {
        $messagePrimaryContent = $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)];
        $celqUslugiParams = $messagePrimaryContent['CelqUslugi'][$this->celqUslugi];
        if(is_array($celqUslugiParams["AddressMOD"])){
            $addressMod = implode(";", $celqUslugiParams["AddressMOD"]);
            $celqUslugiParams["AddressMOD"] = $addressMod;
        }

        if ($this->ZayavitelqType == 'Физическое лицо') {
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
     *
     * @return array
     */
    private function getOrderDataProps()
    {
        $orderDataProps = [];
        $orderDataProps['ReplyTo'] = \MyCompany\WebService\Helper::removeSpacesAndHyphens($this->replyTo);
        $orderDataProps['Request'] = file_get_contents("php://input");
        $orderDataProps['Department'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'Department');
        $orderDataProps['ServiceCode'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'ServiceCode');
        $orderDataProps['TargetCode'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'TargetCode');
        $orderDataProps['StatementDate'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'StatementDate');
        $orderDataProps['ZayavitelqType'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'ZayavitelqType');
        $orderDataProps['SelectType'] = \MyCompany\WebService\Helper::getSelectType(
            $this->getNodeFromXmlArray($this->messagePrimaryContent, 'SelectType')
        );
        $this->nodeValue = false;
        $selectAddress = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'SelectAddress');
        if($selectAddress){
            $orderDataProps['SelectType'] .= '<br>' . $selectAddress;
        }

        /* IQEpguMyCompanyAcrSpguFl */
        $iQEpguMyCompanyAcrSpguFl = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'IQEpguMyCompanyAcrSpguFl');

        if (is_array($iQEpguMyCompanyAcrSpguFl)) {
            $orderDataProps['IQEpguMyCompanyAcrSpguFl'] = '';
            foreach ($iQEpguMyCompanyAcrSpguFl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyAcrSpguFl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyAcrSpguFl'] .= "</u>" . ': ' . $val . '<br>';
            }
        }
        $this->nodeValue = '';
        $iQEpguMyCompanyAcrSpguUl = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'IQEpguMyCompanyAcrSpguUl');
        if (is_array($iQEpguMyCompanyAcrSpguUl)) {
            $orderDataProps['IQEpguMyCompanyAcrSpguUl'] = '';
            foreach ($iQEpguMyCompanyAcrSpguUl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyAcrSpguUl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyAcrSpguUl'] .= "</u>" . ': ' . $val . '<br>';
            }
        }

        /* CelqUslugi */
        $CelqUslugi = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'CelqUslugi');

        if (!empty($CelqUslugi)) {
            foreach ($CelqUslugi as $key => $val) {
                $orderDataProps['CelqUslugi'] = $this->mess[$key] ?? $key;
                $orderDataProps['CelqUslugiData'] = '<b>Предоставленные данные для оказания услуги:</b><br \>';
                foreach ($val as $key_2 => $val_2) {
                    if(empty($val_2)){continue;}
                    if (is_array($val_2)) {
						$orderDataProps['CelqUslugiData'] .= "<u>" . (($this->mess[$key_2]) ?? $key_2) . ":</u> <br>";
                        foreach ($val_2 as $key_3 => $val_3) {
                            if(empty($val_3)){continue;}
                            if(is_numeric($key_3)){$key_3++;}

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
        $orderDataProps['CelqUslugi'] = \MyCompany\WebService\Helper::removeSpacesAndHyphens(
            $orderDataProps['CelqUslugi']
        );

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
        return (int)static::WORKFLOW_ID;
    }

    public function add(): int
    {
        $data = $this->orderData;
        self::prepareData($data["PROPERTY_VALUES"]["AppliedDocuments"]);
        $el = new \CIblockElement();
        $data['PROPERTY_VALUES']['StatusSended'] = 0;
        $newOrderId = $el->add($data);
        if (!$newOrderId) {
            throw new \Exception('Ошибка создания элемента инфоблока:' . $el->LAST_ERROR);
        }
        $this->setDocument($newOrderId);

        return $newOrderId;
    }

    private function prepareData(array &$data)
    {
        foreach ($data as &$dataItem) {
            if ($dataItem['type'] == 'application/pdf') {
                $tmpNameParts = explode('/', $dataItem['tmp_name']);
                $fileName = end($tmpNameParts);
                $dataItem['name'] = $fileName;
            }
        }
    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName)
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

    private function getFileArrayFromStorage(string $messageId, string $fileName, string $mimeType)
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
