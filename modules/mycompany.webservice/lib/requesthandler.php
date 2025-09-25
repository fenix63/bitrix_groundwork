<?
/*Модуль Веб-сервиса обмена сообщениями
Поддерживаемые форматы: XML
Правообладатель: "АО MyCompany"*/

namespace MyCompany\WebService;

class RequestHandler
{
    const VIDI_SVEDENIY = [
        'IQEpguMyCompanyAcrSpgu' => ['className' => 'Accreditation', 'id' => 16, 'workflowId' => 6],
        'IQEpguMyCompanyExpSpgu' => ['className' => 'Attestation', 'id' => 18, 'workflowId' => 7],
        'IQEpguMyCompanyArchSpgu' => ['className' => 'ArchiveReference', 'id' => 17, 'workflowId' => 8],
        'IQEpguMyCompanyOpo' => ['className' => 'Opo', 'id' => 21, 'workflowId' => 16],
        'IQEpguMyCompanyTransitSpgu' => ['className' => 'Tranzit', 'id' => 22, 'workflowId' => 17],
        'IQEpguMyCompanyAppealsSpgu' => ['className' => 'Appeal', 'id' => -1, 'workflowId' => -1]
	];

    public $messageId = '';
    public $workflowId = '';
    public $replyTo = '';
    public $vsName = '';
    public $iblockId = '';
    public $requestHeaders = [];
    public $className = '';
    public $requestBody = '';
    private $nodeValue = '';

    public function __construct(array $requestHeaders, string $requestBody)
    {
        $this->requestHeaders = $requestHeaders;
        $this->requestBody = $requestBody;
    }
    
    public function addOrderByRequest(bool $isNeedToLogRequest = true) : int
    {
        $xml = static::getRequestSoap($this->requestBody);
        $json = json_encode($xml);
        $requestData = json_decode($json, true);
        $request = $this->getNodeFromXmlArray($requestData, 'Request');
        $this->messageId = $this->getNodeFromXmlArray($request, 'MessageID');
        if($isNeedToLogRequest){
            file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/logs/orders/' . trim($this->messageId) . '.txt', file_get_contents('php://input'));
        }

		$this->replyTo = $this->getNodeFromXmlArray($request, 'ReplyTo');
        $messagePrimaryContent = $this->getNodeFromXmlArray($request, 'MessagePrimaryContent');

        foreach (static::VIDI_SVEDENIY as $vsName => $vsData) {
            if ($messagePrimaryContent[$vsName]) {
                $this->className = $vsData['className'];
            }
        }

        switch($this->className){
            case 'Accreditation' : {
                $vs = new VS\Accreditation();
                break;
            }
            case 'Attestation' : {
                $vs = new VS\Attestation();
                break;
            } 
            case 'ArchiveReference' : {
                $vs = new VS\ArchiveReference();
                break;
            } 
            case 'Opo' : {
                $vs = new VS\Opo();
                break;
            } 
            case 'Tranzit' : {
                $vs = new VS\Tranzit();
                break;
            }
            case 'Appeal':
                $vs = new VS\Appeal();
                break;
            default : {
                throw new \Exception('Вид сведений не определен в системе:' . $this->className);
                Log::info(
                    $_SERVER["DOCUMENT_ROOT"].'/logs/trigger-logs-' . date("j-n-Y") . '.txt', 
                    var_export('vid ne naiden')); 
			}
		}
        $vs->setData($request);
        $this->messageId = $vs->getMessageId();
        $this->iblockId = $vs->getIblockId();
        $this->workflowId = $vs->getWorkflowId();
        $orderElementId = $vs->add();

        return $orderElementId;
    }

    public function startWorkflow(int $elementId) : bool
	{
        \Bitrix\Main\Loader::includeModule("bizproc");
		$docId = ['iblock', 'CIBlockDocument', $elementId];
		\CBPDocument::StartWorkflow($this->workflowId, $docId, [], $errors);
		if($errors){
			print_r($errors);
			die('Ошибка старта БП');
		}

		return true;
	}

    public function newRequest(bool $isNeedToLogRequest = true)
    {
        $newRequestId = $this->addOrderByRequest($isNeedToLogRequest);
        if ($newRequestId) {
			echo $newRequestId;
            $this->startWorkflow($newRequestId);
            //ack
			$fabric = new WebServiceAbstractFactory();
			$responseSender = $fabric->createResponseSender();
			$responseSender->sendAck($this->messageId)->send();
        } else {
            throw new \Exception('Ошибка создания элемента инфоблока:' . $newRequestId);
        }
    }
    public function triggerRequestByMessageId(string $messageId)
    {
        $this->requestBody = $this->getRequestByLogFile($messageId);
        $this->newRequest(false);
    }

    private function getRequestByLogFile(string $messageId) : string
    {   
        $request = '';
        $requestLogFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/orders/';
        if(file_exists($requestLogFile . $messageId . '.txt')){
            $request = file_get_contents($requestLogFile . $messageId . '.txt');
        }else{
            Log::info(
                $_SERVER["DOCUMENT_ROOT"].'/logs/orders/logs-' . date("j-n-Y") . '.txt', 
                var_export($messageId, 1));  
        }

        return $request;
    }

    public static function getRequestSoap(string $requestBody)
    {
		return Helper::getRequestSoap($requestBody);
    }

    public function getNodeFromXmlArray(array $xmlArray,string $nodeName) : string | array
    {
		foreach($xmlArray as $key=>$value){
			if($key === $nodeName){
				$this->nodeValue = $value;
				break;
			}else{
				if(gettype($value) == 'array'){
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
}
