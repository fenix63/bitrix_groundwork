<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService\VS;

class ArchiveReference implements \MyCompany\WebService\VS
{
    const IBLOCK_ID = 17;
    const WORKFLOW_ID = 8;

    public $messageId = '';
    public $replyTo = '';
    public $nodeValue = '';
    public $vsCode = 'IQEpguMyCompanyArchSpgu';

    private $orderData = [];
    private $mess = [
        "ulFullName" => "Полное наименование юридического лица",
        "ulKratkoeName" => "Сокращенное наименование юридического лица",
        "OGRN" => "ОГРН",
        "INN" => "ИНН",
        "KPP" => "КПП",
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
        "FamiliaPolychatelya" => "Фамилия получателя",
        "ImyaPolychatelya" => "Имя получателя",
        "OtchestvoPolychatelya" => "Отчество получателя",
        "DateRogdPolychatelya" => "Дата рождения получателя",
        "SeriyaPolychatelya" => "Серия паспорта получателя",
        "NomerPolychatelya" => "Номер паспорта получателя",
        "DateVidachiPolychatelya" => "Дата выдачи паспорта получателя",
        "KemVidanPolychatelya" => "Орган выдачи паспорта получателя",
        "AdresPolychatelya" => "Адрес получателя",
        "TelefonPolychatelya" => "Телефон получателя",
        "EmailPolychatelya" => "Email получателя",
        "NomerAttestata" => "Номер аттестата",
        "DataAttestata" => "Дата выдачи аттестата",
        "OrganizationType" => "Тип организации",
        "AddressMOD" => "Адрес",
        "PolnoeNaimenovanieLicza" => "Полное наименование лица",
        "KratkoeNaimenovanieLicza" => "Краткое наименование лица",
        "OGRNLicza" => "ОГРН лица",
        "INNLicza" => "ИНН лица",
        "ulYuridichAdres" =>"Юридический адрес",
        "ulFaktAdres" =>"Фактический адрес",
        "SotrudnikSurname" => "Фамилия сотрудника",
        "SotrudnikName" => "Имя сотрудника",
        "SotrudnikPatronymic" => "Отчество сотрудника (при наличии)",
        "SotrudnikBirthdate" => "Дата рождения сотрудника",
        "SotrudnikPasportSeriya" => "Серия паспорта РФ сотрудника",
        "SotrudnikPasportNomer" => "Номер паспорта РФ сотрудника",
        "SotrudnikPasportData" => "Дата выдачи паспорта РФ сотруднику",
        "SotrudnikPasportOrgan" => "Название подразделения, выдавшего паспорт РФ сотруднику",
    ];
    private $messagePrimaryContent;
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
            )]['ZayavitelqInfo']['IQEpguMyCompanyArchSpguFl'];
        } else {
            $this->zayavitelqInfo = $this->messagePrimaryContent[array_key_first(
                $this->messagePrimaryContent
            )]['ZayavitelqInfo']['IQEpguMyCompanyArchSpguUl'];
        }

        $orderDataProps = $this->getOrderDataProps();
        if(!empty($orderDataProps['IQEpguMyCompanyArchSpguFl'])){
            $orderDataProps['IQEpguMyCompanyArchSpguFl'] .= "";
        }else{
            $orderDataProps['IQEpguMyCompanyArchSpguUl'] .= "";
        }

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
		if ((is_array($fSAttachment)) && (!$orderDataProps["AppliedDocuments"])) {
            var_dump($fSAttachment);
            throw new \Exception('Вложения, ожидаемые в виде сведений, не обнаружены в системе:');
        }

        $this->orderData["PROPERTY_VALUES"] = $orderDataProps;
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
        $orderDataProps['SelectType'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'SelectType');
        $this->nodeValue = false;
        $selectAddress = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'SelectAddress');
        if($selectAddress){
            $orderDataProps['SelectType'] .= '<br>' . $selectAddress;
        }
        $this->nodeValue = false;
        $orderDataProps['myOption'] = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'myOption');

        /* IQEpguMyCompanyArchSpguFl */
        $IQEpguMyCompanyArchSpguFl = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'IQEpguMyCompanyArchSpguFl');

        if (is_array($IQEpguMyCompanyArchSpguFl)) {
            $orderDataProps['IQEpguMyCompanyArchSpguFl'] = '';
            foreach ($IQEpguMyCompanyArchSpguFl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyArchSpguFl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyArchSpguFl'] .= "</u>" . ': ' . $val . '<br>';
            }
        }
        $this->nodeValue = '';
        $IQEpguMyCompanyArchSpguUl = $this->getNodeFromXmlArray($this->messagePrimaryContent, 'IQEpguMyCompanyArchSpguUl');
        if (is_array($IQEpguMyCompanyArchSpguUl)) {
            $orderDataProps['IQEpguMyCompanyArchSpguUl'] = '';
            foreach ($IQEpguMyCompanyArchSpguUl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyArchSpguUl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyArchSpguUl'] .= "</u>" . ': ' . $val . '<br>';
            }
        }

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
        $el = new \CIblockElement();
        $data['PROPERTY_VALUES']['StatusSended'] = 0;
        $newOrderId = $el->add($data);
        if (!$newOrderId) {
            throw new \Exception('Ошибка создания элемента инфоблока:' . $el->LAST_ERROR);
            Log::info(
                $_SERVER["DOCUMENT_ROOT"].'/logs/trigger-logs-' . date("j-n-Y") . '.txt',
                var_export($el->LAST_ERROR, 1));
        }

        return $newOrderId;
    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName)
    {
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

    public function searchElement()
    {
        // TODO: Implement searchElement() method.
    }

    public function updateElement()
    {
        // TODO: Implement updateElement() method.
    }
}
