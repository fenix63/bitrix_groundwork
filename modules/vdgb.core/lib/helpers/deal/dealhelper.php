<?php

namespace Vdgb\Core\Helpers\Deal;
use Bitrix\Main\Loader;
use Vdgb\Core\Helpers\Advanta\AdvantaHelper;
use Vdgb\Core\Helpers\Curl;
use Vdgb\Core\Helpers\XMLHelper;
use Vdgb\Core\Debug;

Loader::includeModule('crm');
Loader::IncludeModule("tasks");

class DealHelper
{
    public static function sendCurlAuth()
    {
        $headers = [];
        array_push($headers, "Content-Type: text/xml; charset=utf-8");
        array_push($headers, "SOAPAction: http://streamline/Authenticate");

        $credentials = [
            'login'    => 'yu.maratkanov_ext',
            'password' => 'XQ7a10XJ'
        ];

        $xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:str="http://streamline/">
       <soapenv:Header/>
       <soapenv:Body>
          <str:Authenticate>
             <!--Optional:-->
             <str:login>'.$credentials['login'].'</str:login>
             <!--Optional:-->
             <str:password>'.$credentials['password'].'</str:password>
          </str:Authenticate>
       </soapenv:Body>
    </soapenv:Envelope>';      
        $url = 'https://advanta-app.rgaz.ru:442/components/services/login.asmx';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);


        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);


        return ['response'=> $response,'info'=> $info];
    }

    public static function getDealListByFilter(array $filter, array $selectedFields)
    {
        $entityResult = \CCrmDeal::GetListEx(
            [],
            $filter,
            false,
            false,
            $selectedFields,
            []
        );

        $data = [];
        while($entity = $entityResult->fetch()){
            $data[] = $entity;
        }

        return $data;
    }

    public static function getDealInfo(int $dealId): array
    {
        $dbDocumentList = \CCrmDeal::GetList(
            [],
            [
                'ID' => $dealId,
                "CHECK_PERMISSIONS" => "N"
            ],
            //['ID','TITLE']
            []
        );

        $data = [];
        while($item = $dbDocumentList->fetch()){
            $data[$item['ID']] = $item;
        }

        return $data;
    }

    public static function getDealInfoShort(int $dealId, array $fieldsToSelect): array
    {
        $entityResult = \CCrmDeal::GetListEx(
            [],
            [
                "ID" => $dealId,
                "CHECK_PERMISSIONS" => "N"
            ],
            false,
            false,
            $fieldsToSelect,
            []
        );

        $data = [];
        while($entity = $entityResult->fetch()){
            $data[] = $entity;
        }

        return $data;
    }

    public static function getActivity($dealId)
    {
        $listActivity = \CCrmActivity::GetList(
            $arOrder = [],
            $arFilter = [
                'OWNER_ID' => $dealId, // ID сделки
                'OWNER_TYPE_ID' => 2, // 1-Лид, 2-Сделка, 3-Контакт, 4-Компания
                //'TYPE_ID' => 3, // 1-Встреча, 2-Звонок, 3-Задача, 4-Email
                //'COMPLETED' => 'N'
            ],
            //$arGroupBy = false,
            false,
            //$arNavStartParams = false,
            false,
            //$arSelectFields = [],
            []
            
        );

        $arActivity = [];
        while ($activity = $listActivity->fetch()) {
            $arActivity[] = $activity;
        }

        return $arActivity;
    }


    public static function getDealNameByCode(string $statusCode): string
    {
        $entityId = 'DEAL_STAGE_3';
        $statusTitle = '';
        $statuses = \CCrmStatus::GetStatus($entityId);

        $statusTitle = $statuses[$statusCode]['NAME'];
        return $statusTitle;
    }

    public static function getTaskInfoById($taskId)
    {
        $info = [];
        /*$dbTask = \Bitrix\Tasks\Item\Task::find([
            'select' => ['ID','TITLE'],
            'filter' => ['=ID' => $taskId]
        ]);
        */
        
        //$dbTask = new \Bitrix\Tasks\Item\Task($taskId);

        //var_dump($taskId);
        $dbTask = \Bitrix\Tasks\Item\Task::getInstance($taskId);
        $info = $dbTask->getData();

        

        


        return $info;
    }

    public static function getTaskListByDeal(int $dealId)
    {
        /*
        $taskList = [];
        $select = [];
        //$filter = ['UF_CRM_TASK' => $dealId];
        $filter = ['ID' => 48511];
        
        $res = \CTasks::GetList([], $filter, $select);

        while($taskItem = $res->fetch()){
            $taskList[] = $taskItem;
        }
        */
        //$task = new \Bitrix\Tasks\Item\Task(48511); // получение сущности с выбранным id
        //$taskData = $task->getData(['UF_CRM_TASK']);
        $tasks = \Bitrix\Tasks\Item\Task::find([
            'select' => ['ID', 'TITLE','UF_CRM_TASK'],
            'filter' => ['=ID' => 48511]
        ]);




        return $tasks;
    }

    public static function updateDealField(int $dealId, string $fieldCode, string $fieldValue = '')
    {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $item = $factory->getItem($dealId);
        $item->set($fieldCode, $fieldValue);
        $item->save();
    }

	/**
	 * @param string $dealStatusCode
	 * @param string $sessId
	 * @return string
	 */
    public static function getDealStatusUID(string $dealStatusCode, string $sessId): string
	{
        $credentials = AdvantaHelper::getAdvantaCredentials();
		$deadStatusTitle = DealHelper::getDealNameByCode($dealStatusCode);
		$classificatorAllStagesRecordsXml = AdvantaHelper::buildXmlToRequest(
			'GetClassifierRecords',
			$sessId,
			['classificatorUID'=>'8f239169-9148-4128-84f9-83851c196c53']
		);
		$classificatorAllStagesRecordsResult = Curl::sendRequest(
			$credentials['advantaServerURL'].'/components/Services/APIService.asmx',
			['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
			'GetClassifierRecords',
			$classificatorAllStagesRecordsXml
		);
		$classificatorRequestSoap = XMLHelper::getRequestSoap($classificatorAllStagesRecordsResult['response']);
		$classificatorJson = json_encode($classificatorRequestSoap);
		$classificatorRequestData = json_decode($classificatorJson, true);

		$classificatorXmlObj = new XMLHelper();
		$classificatorRecords = $classificatorXmlObj->getNodeFromXmlArray($classificatorRequestData, 'RecordWrapper');
		$classificatorUID = '';
		$classificatorUID = AdvantaHelper::getClassificatorUidByTitle($deadStatusTitle, $classificatorRecords);

		return $classificatorUID;
	}
}
?>
