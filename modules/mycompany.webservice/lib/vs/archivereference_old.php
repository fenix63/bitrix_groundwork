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
    public $workflowId = '';
    public $replyTo = '';
    public $nodeValue = '';
    public $vsCode = 'IQEpguMyCompanyArchSpgu';
	private $mess = [
		"ulName" => "Наименование юридического лица",
		"OGRN" => "ОГРН",
		"INN" => "ИНН",
		"KPP" => "КПП",
		"ulHeadSurname" => "Фамилия руководителя",
		"ulHeadName" => "Имя руководителя",
		"ulHeadPatronymic" => "Отчество руководителя",
		"ulFactAddress" => "Фактический адрес",
		"ulPhone" => "Контактный телефон",
		"ulEmail" => "Email",
		"myOption" => "Получения справки",
		"FamiliaPolychatelya" => "Фамилия получателя",
		"ImyaPolychatelya" => "Имя получателя",
		"OtchestvoPolychatelya" => "Отчество получателя",
		"DateRogdPolychatelya" => "Дата рождения получателя",
		"SeriyaPolychatelya" => "Серия паспорта получателя",
		"NomerPolychatelya" => "Номер паспорта получателя",
		"DateVidachiPolychatelya" => "Дата выдачи паспорта получателя",
		"KemVidanPolychatelya" => "Кем выдан паспорт получателя",
		"NomerDoc" => "Номер документа",
		"DateDoc" => "Дата документа",
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
	];

    private $orderData = [];

    public function setData(array $request)
    {
        $this->messageId = trim($this->getNodeFromXmlArray($request, 'MessageID'));
        //Проверка на уникальный MessageId
        if (\MyCompany\WebService\Helper::checkUniqueMessageId(static::IBLOCK_ID, $this->messageId)) {
            die('MessageId не уникальный');
        }

		$this->replyTo = $this->getNodeFromXmlArray($request, 'ReplyTo');
        $messagePrimaryContent = $this->getNodeFromXmlArray($request, 'MessagePrimaryContent');
     
		$this->orderData["IBLOCK_ID"] = static::IBLOCK_ID;
		$this->orderData["NAME"] = $messagePrimaryContent[$this->vsCode]["OrderId"];   
		$this->orderData["XML_ID"] = $this->messageId;
		$this->orderData["CODE"] = $this->messageId;

		$orderDataProps['ReplyTo'] = $this->replyTo;
		$orderDataProps['Request'] = file_get_contents("php://input");
		$orderDataProps['ServiceCode'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ServiceCode');
		$orderDataProps['Department'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'Department');
		$orderDataProps['TargetCode'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'TargetCode');
		$orderDataProps['StatementDate'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'StatementDate');

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

        $requestType = '';
		$iQEpguMyCompanyArchSpguFl = '';
		$iQEpguMyCompanyArchSpguFlData = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyArchSpguFl');
		if(is_array($iQEpguMyCompanyArchSpguFlData)){
			foreach($iQEpguMyCompanyArchSpguFlData as $key => $value) {
				if(is_array($value)){
					$value = implode('<br>', $value);
				}

                if($key == 'myOption'){
                    $requestType = self::getRequestType($value);
                    continue;
                }

				$iQEpguMyCompanyArchSpguFl .= "<u>" .($this->mess[$key]) ?? $key;
                $iQEpguMyCompanyArchSpguFl .= "</u>" . ': ' . $value . '<br>'; 
			}
		}
		$orderDataProps['IQEpguMyCompanyArchSpguFl'] = $iQEpguMyCompanyArchSpguFl;
		
		$iQEpguMyCompanyArchSpguUl = '';
		$this->nodeValue = '';
		$iQEpguMyCompanyArchSpguUlData = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyArchSpguUl');
		if(is_array($iQEpguMyCompanyArchSpguUlData)){
			foreach($iQEpguMyCompanyArchSpguUlData as $key => $value) {
				if(is_array($value)){
					$value = implode('<br>', $value);
				}

                if($key == 'myOption'){
                    $this->orderData['PREVIEW_TEXT'] = trim($value);
                    $requestType = self::getRequestType(trim($value));
                    continue;
                }

				$iQEpguMyCompanyArchSpguUl .= "<u>" .($this->mess[$key]) ?? $key;
                $iQEpguMyCompanyArchSpguUl .= "</u>" . ': ' . $value . '<br>'; 
			}
		}
		$orderDataProps['IQEpguMyCompanyArchSpguUl'] = $iQEpguMyCompanyArchSpguUl;
        $orderDataProps['TypeRequest'] = $requestType;
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
    public function getMessageId() : string
	{
		return $this->messageId;
	}
    public function getIblockId() : int
	{
		return static::IBLOCK_ID;
	}
    public function getWorkflowId() : int
	{
		return static::WORKFLOW_ID;
	}
    public function add() : int
    {
        $data = $this->orderData;
        $el = new \CIblockElement();
        $newOrderId = $el->add($data);
        if(!$newOrderId){
            throw new \Exception('Ошибка создания элемента инфоблока:' . $el->LAST_ERROR);
        }

        return $newOrderId;
    }

    private function getNodeFromXmlArray(array $xmlArray,string $nodeName) : string | array
    {
		//$resultNodeValue=($nodeValue)?$nodeValue:false;
		foreach($xmlArray as $key=>$value){
			if($key === $nodeName){
				$this->nodeValue = $value;
				break;
			}else{
				if(gettype($value)=='array'){
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
            $newPath = $currentFilePath . '.' . explode( '/' , $mimeType)[1];
            rename($currentFilePath, $currentFilePath . '.' . explode( '/' , $mimeType)[1]);
            $fileData = \CFile::MakeFileArray($newPath);
        };

        return $fileData;
    }

    private function getRequestType($key){
        switch(trim($key)){
            case 'r1':{
                return "О подтверждении награждения в период работы на предприятиях атомной отрасли";
            }
            case 'r2':{
                return "О подтверждении участия в ликвидации последствий аварии на Чернобыльской АЭС";
            }
            case 'r3':{
                return "О предоставлении архивной информации по зарплате в период работы на предприятиях атомной отрасли";
            }
            case 'r5':{
                return "О подтверждении стажа работы на предприятиях атомной отрасли";

            }
            default:
            {
                return "TypeRequest is not equal to r1, r2, r3 or r5";
            }
        }
    }
}
