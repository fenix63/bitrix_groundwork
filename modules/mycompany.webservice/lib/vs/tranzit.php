<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService\VS;

class Tranzit implements \MyCompany\WebService\VS
{
    const IBLOCK_ID = 22;
    const WORKFLOW_ID = 24;

    public $messageId = '';
    public $replyTo = '';
    public $nodeValue = '';
    public $celqUslugi = '';
    public $vsCode = 'IQEpguMyCompanyTransitSpgu';
    private $orderData = [];
    private $mess = [
        "ulFullNameZayavitelq" => "Полное наименование юридического лица",
        "ulNameZayavitelq" => "Сокращенное наименование юридического лица",
        "ulOGRNZayavitelq" => "ОГРН",
        "ulINNZayavitelq" => "ИНН",
        "ulKPPZayavitelq" => "КПП",
        "ulHeadSurnameZayavitelq" => "Фамилия руководителя",
        "ulHeadNameZayavitelq" => "Имя руководителя",
        "ulHeadPatronymicZayavitelq" => "Отчество руководителя",
        "ulHeadRoleZayavitelq" => "Должность заявителя",
        "ulEmail" => "Email",
        "ulPhone" => "Контактный телефон",
        "ulPhoneSotrudnik" => "Контактный телефон сотрудника",
        "ulEmailSotrudnik" => "Email сотрудника",
        "ulYuridichAddress" => "Юридический адрес",
        "ulFactAddress" => "Фактический адрес",
        'IQEpguMyCompanyTransitRazr' => 'Получение письменного разрешения на перемещение ядерных материалов, ядерных установок через государственную границу Российской Федерации (с целью транзита по ее территории)',
        'IQEpguMyCompanyTransitDplct' => 'Получение дубликата письменного разрешения',
        'IQEpguMyCompanyTransitSved' => 'Получение сведений из реестра разрешений',
        'IQEpguMyCompanyTransitCncl' => 'Аннулирование разрешения на перемещение',
        'PackType' => 'Тип перемещаемого груза',
        'CRnumber' => 'Номер разрешения',
        'Creason' => 'Причина',
        'CRdate' => 'Дата разрешения',
        'SelectC' => 'Тип',
        'FaxNumber' => 'Факс',
        'GruzNazvanie' => 'Название груза',
        'OONNumber' => 'Номер вещества',
        'EESCode' => 'Код товарной номенклатуры',
        'SenderName' => 'Имя отправителя',
        'SenderAdress' => 'Адрес отправителя',
        'SenderTelefone' => 'Телефон отправителя',
        'IQEpguMyCompanyTransitRazrRec' => 'Блок данных о грузополучателях',
        'IQEpguMyCompanyTransitRazrR' => 'Данные грузополучателей',
        'ReceiverName' => 'Имя получателя',
        'ReceiverAdress' => 'Адрес получателя',
        'ReceiverTelefone' => 'Телефон получателя',
        'ReceiverFax' => 'Фактический номер получателя',
        'Pcrossing' => 'Промежуточные пункты перемещения груза',
        'Transport' => 'Транспорт',
        'SertNumbers' => 'Номера сертификатов',
        'ulFullNamePoluchatelq' => 'Полное наименование организации получателя',
        'ulNamePoluchatelq' => 'Сокращенное наименование организации получателя',
        'ulAdresPoluchatelq' => 'Адрес получателя',
        'ulHeadSurnamePoluchatelq' => 'Фамилия руководителя',
        'ulHeadNamePoluchatelq' => 'Имя руководителя',
        'ulHeadPatronymicPoluchatelq' => 'Отчество руководителя',
        'ulPhonePoluchatelq' => 'Телефон',
        'ulEmailPoluchatelq' => 'Email',
        'DoverennostqNazvanie' => 'Документ, подтверждающий полномочия',
        'DoverennostqNumber' => 'Номер документа',
        'DoverennostqDate' => 'Дата выдачи',
        'SenderFaxNumber' => 'Факс',
        'DoverennostqSeriya' => 'Серия документа',
        'DoverennostqSrok' => 'Срок действия'
    ];
    private $propertyKey = [
        'StatementDate',
        'ResultState',
        'Department',
        'TargetCode',
        'CelqUslugi',
    ];

    public function setData(array $request)
    {
        $this->messageId = trim($this->getNodeFromXmlArray($request, 'MessageID'));
        //Проверка на уникальный MessageId
        if (\MyCompany\WebService\Helper::checkUniqueMessageId(static::IBLOCK_ID, $this->messageId)) {
            die('MessageId не уникальный');
        }

        $this->replyTo = $this->getNodeFromXmlArray($request, 'ReplyTo');
        $messagePrimaryContent = $this->getNodeFromXmlArray($request, 'MessagePrimaryContent');
        $this->celqUslugi = array_key_first($messagePrimaryContent[array_key_first($messagePrimaryContent)]['CelqUslugi']);

        $this->orderData["IBLOCK_ID"] = static::IBLOCK_ID;
        $this->orderData["NAME"] = $messagePrimaryContent[$this->vsCode]["OrderId"];
        $this->orderData["XML_ID"] = $this->messageId;
        $this->orderData["CODE"] = $this->messageId;
        $this->orderData['PREVIEW_TEXT'] = $this->celqUslugi;

        $orderDataProps['ReplyTo'] = trim($this->replyTo);
        $orderDataProps[$this->vsCode] = file_get_contents("php://input");
        $selectTypeData = $this->getNodeFromXmlArray($messagePrimaryContent, 'SelectType');
        switch ($selectTypeData) {
            case 0: {
                    $selectType = "В личном кабинете";
                    break;
                }
            case 1: {
                    $selectType = "Заказным почтовым отправлением";
                    break;
                }
            case 2: {
                    $selectType = "Получить лично";
                    break;
                }
            default: {
                    $selectType = "selectType is not equal to 0, 1 or 2";
                }
        }
        $orderDataProps['SelectType'] = $selectType;
        $iQEpguMyCompanyTransitSpguUl = '';
        $iQEpguMyCompanyTransitSpguUlData = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyTransitSpguUl');
        if (is_array($iQEpguMyCompanyTransitSpguUlData)) {
            foreach ($iQEpguMyCompanyTransitSpguUlData as $key => $value) {
                $iQEpguMyCompanyTransitSpguUl .= "<u>" . ($this->mess[$key]) ?? $key;
                $iQEpguMyCompanyTransitSpguUl .= "</u>" . ': ' . $value . '<br>';
            }
        }
        $orderDataProps['IQEpguMyCompanyTransitSpguUl'] = $iQEpguMyCompanyTransitSpguUl;

        foreach ($messagePrimaryContent[$this->vsCode] as $key => $item) {
            if (in_array($key, $this->propertyKey)) {
                if (is_array($item)) {
                    $orderDataProps[$key] = $this->getStringFromArray($item);
                } else {
                    $orderDataProps[$key] = trim($item);
                }
            }
        }
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
        $orderDataProps["AppliedDocuments"] = \MyCompany\WebService\Attachments::getAttachmentsData($this->messageId, $attachmentsData);
		$this->nodeValue = '';
		$fSAttachment = $this->getNodeFromXmlArray($request, 'FSAttachment');
		if ((is_array($fSAttachment)) && (!$orderDataProps["AppliedDocuments"])) {
            throw new \Exception('Вложения, ожидаемые в виде сведений, не обнаружены в системе:');
        }
        $this->orderData["PROPERTY_VALUES"] = $orderDataProps;
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
            echo $newOrderId;
        } else {
            throw new \Exception('Ошибка создания элемента инфоблока:' . $el->LAST_ERROR);
        }

        return $newOrderId;
    }

    private function getStringFromArray($array): string
    {
        $string = '';
        foreach ($array as $key => $item) {
            if ($this->mess[$key]) {
                $keyName = $this->mess[$key];
            } else {
                $keyName = $key;
            }
            if(is_numeric($keyName)){
                $keyName++;
            }
            if (is_array($item)) {
                $string .= '<b>' . $keyName . '</b>' . ' : '. '<br />' . $this->getStringFromArray($item);
            } else {
                $string .= $keyName . ' : ' . $item . '<br />';
            }
        }

        return $string;
    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName): string|array
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

    private function getFileArrayFromStorage(string $messageId, string $fileName, string $mimeType): array|false
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
