<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService\VS;

class Opo implements \MyCompany\WebService\VS
{
    const IBLOCK_ID = 21;
    const WORKFLOW_ID = 16;

    public $messageId = '';
    public $replyTo = '';
    public $nodeValue = '';
    public $celqUslugi;
    public $vsCode = 'IQEpguMyCompanyOpo';
    private $orderData = [];
    private $propertyKey = [
        'StatementDate',
        'ResultState',
        'Department',
        'ServiceCode',
        'TargetCode',
        'CelqUslugi',
    ];

   private $mess = [
        'ulNameZayavitelq' => 'Наименование юридического лица',
        'ulShortNameZayavitelq' => 'Наименование юридического лица',
        'ulOGRNZayavitelq' => 'ОГРН',
        'ulINNZayavitelq' => 'ИНН',
        'ulKPPZayavitelq' => 'КПП',
        'ulHeadRoleZayavitelq' => 'Должность руководителя',
        'ulHeadSurnameZayavitelq' => 'Фамилия руководителя',
        'ulHeadNameZayavitelq' => 'Имя руководителя',
        'ulHeadPatronymicZayavitelq' => 'Отчество руководителя',
        'ulPhone' => 'Контактный телефон',
        'ulEmail' => 'Email',
        'ulYuridichAddress' => 'Юридический адрес',
        'ulFactAddress' => 'Фактический адрес',
        'IQEpguMyCompanyOpoEdit' => 'Внесение изменений в сведения, ранее зарегистрированные в Реестре',
        'Prichina' => 'Причины внесения изменений',
        'IQEpguOpoEditOpoList' => 'Перечень ОПО',
        'IQEpguMyCompanyOpoEditOrgList' => 'Сведения о старой эксплуатирующей организации',
        'IQEpguMyCompanyOpoDplct' => 'Выдача дубликата свидетельства о регистрации',
        'DetEpguOpoDplctReasonList' => 'Причина запроса дубликата свидетельства',
        'IQEpguMyCompanyOpoRgstr' => 'Регистрация ОПО в государственном реестре опасных производственных объектов',
        'ulOKVEDZayavitelq' => 'ОКВЭД',
        'ulPravoVlad' => 'Сведения о правах владения ОПО',
        'IQEpguOpoRgstrOpoList' => 'Сведения о регистрируемых ОПО',
        'IQEpguMyCompanyOpoExclude' => 'Исключение ОПО из государственного реестра опасных производственных объектов',
        'DetEpguOpoExcludeOpoList' => 'Перечень ОПО',
        'IQEpguOpoEditOpo' => 'Перечень ОПО',
        'Org_Name' => 'Наименование организации',
        'Org_INN' => 'ИНН',
        'Org_OGRN' => 'ОГРН',
        'Org_OPO' => 'ОПО',
        'IQEpguMyCompanyOpoEditOrg' => 'Сведения о старой эксплуатирующей организации',
        'SvedRegN' => 'Сведения о регистрации',
        'OpoObjName' => 'Наименование объекта',
        'ActionType' => 'Тип действия',
        'IQEpguOpoDplctReason' => 'Причины внесения изменений',
        'IQEpugOpoRgstrOpo' => 'Регистрация объекта',
        'OpoObjClass' => 'Класс объекта'
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
        $orderDataProps['Request'] = file_get_contents("php://input");
        $selectTypeData = $this->getNodeFromXmlArray($messagePrimaryContent, 'SelectType');
		switch($selectTypeData){
			case 0:{
				$selectType = "В личном кабинете";
				break;
			}
			case 1:{
				$selectType = "Заказным почтовым отправлением";
				break;
			}
			case 2:{
				$selectType = "Получить лично";
				break;
			}
			default:
			{
				$selectType = "selectType is not equal to 0, 1 or 2";
			}
		}
		$orderDataProps['SelectType'] = $selectType;
        $iQEpguMyCompanyOpoUl = '';
        foreach ($messagePrimaryContent[$this->vsCode] as $key => $item) {
            if (in_array($key, $this->propertyKey)) {
                if (is_array($item)) {
                    $orderDataProps[($this->mess[$key]) ?? $key] = $this->getStringFromArray($item);
                } else {
                    $orderDataProps[($this->mess[$key]) ?? $key] = trim($item);
                }
            }
        }
        $iQEpguMyCompanyOpoUlData = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyOpoUl');
		if(is_array($iQEpguMyCompanyOpoUlData)){
			foreach($iQEpguMyCompanyOpoUlData as $key => $value) {
				$iQEpguMyCompanyOpoUl .= "<u>" .($this->mess[$key]) ?? $key;
                $iQEpguMyCompanyOpoUl .= "</u>" . ': ' . $value . '<br>';
			}
		}
		$orderDataProps['IQEpguMyCompanyOpoUl'] = $iQEpguMyCompanyOpoUl;

        $attachmentsData = [];
        $appliedDocuments = $this->getNodeFromXmlArray($request, 'AppliedDocuments');
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

            if (is_numeric($keyName)) {
                $keyName++;
            }

            if (is_array($item)) {
                if (in_array($key, ['IQEpguOpoEditOpo', 'IQEpguOpoExcludeOpo','IQEpguMyCompanyOpoEditOrg'])) {
                    $string .= $this->getStringFromArray($item);
                } else {
                    $string .= '<b>' . $keyName . '</b>' . ' : <br />' . $this->getStringFromArray($item);
                }
            } else {
                $string .= $keyName . ' : ' . $item . '<br />';
            }
        }

        return $string;
    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName): string|array
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
