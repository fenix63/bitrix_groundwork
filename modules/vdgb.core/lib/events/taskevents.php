<?php

namespace Vdgb\Core\Events;
use Vdgb\Core\Debug;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Vdgb\Core\Helpers\Task\TaskHelper;
use Vdgb\Core\Helpers\User\UserHelper;
use Vdgb\Core\Helpers\Deal\DealHelper;
use Vdgb\Core\Helpers\Advanta\AdvantaHelper;
use Vdgb\Core\Helpers\Curl;
use Vdgb\Core\Helpers\XMLHelper;
use Vdgb\Core\Helpers\IblockHelper;
use Vdgb\Core\Helpers\Databus\Settings as DatabusSettings;

Loader::includeModule("tasks");

class TaskEvents
{
    public static function onTaskAddHandler($idTask, &$arFields)
    {

        Debug::dbgLog('onTaskAddHandler','_onTaskAddHandler_');
        Debug::dbgLog($arFields,'_arFields_');
        $advantaCredentials = AdvantaHelper::getAdvantaCredentials();

        $taskId = $idTask;
        $dealId = str_replace('D_','',$arFields['UF_CRM_TASK'][0]);
        if(empty($dealId))
            return true;

        Debug::dbgLog($dealId,'_dealId_');

        $presaleUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
        $dealInfo = DealHelper::getDealInfoShort($dealId, [$presaleUFCode]);
        $presaleUID = $dealInfo[0][$presaleUFCode];
        Debug::dbgLog($presaleUID,'_presaleUID_');

        if(empty($presaleUID))
            return true;


        $taskInfo = DealHelper::getTaskInfoById($taskId);

        //Debug::dbgLog('test1','_test1_');
        if(!empty($taskInfo['DEADLINE'])){
			Debug::dbgLog('test dates','_Deadline_change_');

			$presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime();
            $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
            $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);
            $startDateShort = explode("T", $presaleInfo['startDate'])[0];

            $presaleInfo['finishDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['DEADLINE']);
            $presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d H:i:s");
            $presaleInfo['finishDate'] = str_replace(" ","T", $presaleInfo['finishDate']);
            $finishDateShort = explode("T", $presaleInfo['finishDate'])[0];

            //Debug::dbgLog('test2','_test2_');
        }else{
        	//Debug::dbgLog('test dates','_Start_finish_date_change_');

        	$presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['START_DATE_PLAN']);
            $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
            $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);
            $startDateShort = explode("T", $presaleInfo['startDate'])[0];

            $presaleInfo['finishDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['END_DATE_PLAN']);
            $presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d H:i:s");
            $presaleInfo['finishDate'] = str_replace(" ","T", $presaleInfo['finishDate']);
            $finishDateShort = explode("T", $presaleInfo['finishDate'])[0];

            //Debug::dbgLog('test3','_test3_');
        }

        $startDateObj = new \DateTime($startDateShort);
        $finishDateObj = new \DateTime($finishDateShort);
        $duration = $finishDateObj->diff($startDateObj)->days;
        $presaleInfo['duration'] = $duration;

        //Debug::dbgLog($presaleInfo,'_presaleInfo_');

        $advantaHelperObj = new AdvantaHelper();
        $sessId = $advantaHelperObj->getSessionId();
        $executorId = $taskInfo['RESPONSIBLE_ID'];
        $executorInfo = UserHelper::getUserInfo($executorId,['ID','EMAIL']);
        $salesDepartmentUser = UserHelper::getUserInfo($executorId,['NAME','LAST_NAME','SECOND_NAME']);

        $executorInfo['roleb24'] = 'Исполнитель';
        $executorEmail = $executorInfo['EMAIL'];
        //Debug::dbgLog('test4','_test4_');
        //Получаем всех пользователей адванты
        $allUsersInfoXML = AdvantaHelper::buildXmlToRequest('GetPersons', $sessId, []);
        $curlUsersInfoResponse = Curl::sendRequest(
          //AdvantaHelper::ADVANTA_GET_PERSONS_URL,
          $advantaCredentials['advantaServerURL'].'/components/services/persons.asmx',
          ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons'],
          'GetPersons',
          $allUsersInfoXML
        );

        

        $requestUsersSoap = XMLHelper::getRequestSoap($curlUsersInfoResponse['response']);
        $allUsersJson = json_encode($requestUsersSoap);
        $allUsersData = json_decode($allUsersJson, true);

        $xmlObj = new XMLHelper();
        $users = $xmlObj->getNodeFromXmlArray($allUsersData, 'SlPerson');

        //Конец получения всех пользователей адванты

        $executorInfo = AdvantaHelper::getUserInfoByEmail($executorEmail, $users);
        $presaleInfo['executorInfo'] = $executorInfo;
        $executorInfo['roleb24'] = 'Исполнитель';

        //соисполнители
        $coExecutorIdList = $taskInfo['ACCOMPLICES']->toArray();
        $coExecutorsExternalIdList = [];
        foreach($coExecutorIdList as $coExecutorId){
          $info = UserHelper::getUserInfo($coExecutorId,['ID','EMAIL']);
          $email = $info['EMAIL'];
          $coExecutorExternalInfo = AdvantaHelper::getUserInfoByEmail($email, $users);
          $coExecutorExternalInfo['roleb24'] = 'Соисполнитель';
          $coExecutorExternalInfoList[] = $coExecutorExternalInfo;
        }
        $presaleInfo['coExecutorsList'] = $coExecutorExternalInfoList;

        

        $users = [];
        $users[] = $executorInfo;

        if(!empty($coExecutorExternalInfoList)){
            $users = array_merge($users, $coExecutorExternalInfoList);
        }

        $createRecordsToDictionaryXml = AdvantaHelper::buildXmlToRequest(
            'InsertDirectoryRecords',
            $sessId,
            [
              'presaleId'=> $presaleUID,
              'users' => $users
            ]
        );

        
        $presaleInfo['presaleUID'] = $presaleUID;
        //Debug::dbgLog($presaleInfo,'_presaleInfo_');

        $updatePresaleXml = AdvantaHelper::buildXmlToRequest('UpdateProject', $sessId, $presaleInfo);
        //Debug::dbgLog($updatePresaleXml,'_updatePresaleXml_');

        $curlUpdatePresaleXmlResponse = Curl::sendRequest(
          $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
          ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProject'],
          'UpdateProject',
          $updatePresaleXml
        );

        

        //Формируем XML для добавления исполнителей и соисполнителей
        $presaleCommandUsersXml = AdvantaHelper::buildXmlToRequest('InsertDirectoryRecords', $sessId, 
            [
                'presaleId'=> $presaleUID,
                'users' => $users
            ]
        );

        

        AdvantaHelper::deleteAllExecutors($presaleUID, $sessId);

        //Debug::dbgLog('test8','_test8_');
        $addPresaleCommandResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/InsertDirectoryRecords'],
            'InsertDirectoryRecords',
            $presaleCommandUsersXml
        );
        $b24Link = $_SERVER['HTTP_ORIGIN'].'/workgroups/group/'.$taskInfo['GROUP_ID'].'/tasks/task/view/'.$taskInfo['ID'].'/';
        $projectRequisitesUpdateXml = AdvantaHelper::buildXmlToRequest('UpdateProjectFields', $sessId,
            [
                'presaleUID' => $presaleUID,
                'projectBase' => $taskInfo['DESCRIPTION'],
                'projectContent' => $taskInfo['DESCRIPTION'],
                'b24Link' => $b24Link,
                'salesDepartmentUser' => $salesDepartmentUser
            ]
        );

        //Debug::dbgLog('test9','_test9_');

        $projectRequisitesUpdateResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProjectFields'],
            'UpdateProjectFields',
            $projectRequisitesUpdateXml
        );

        //Debug::dbgLog('test10','_test10_');
        
    }

    public static function onTaskUpdateHandler($idTask, &$arFields, &$arTaskCopy)
    {
        //Debug::dbgLog('onTaskUpdateHandler','_onTaskUpdateHandler_');
        Debug::dbgLog(['idTask'=>$idTask,'arFields'=>$arFields, 'arTaskCopy'=>$arTaskCopy],'_onTaskUpdateHandler_');

        //Проверяем, используются прямые запросы до Адванты, или через шину
        $useDatabus = DatabusSettings::dataBusIsUsing();

        if(!empty($arFields['UF_MY_PROJECT'])){

            $sectionCode = IblockHelper::getSectionCodeById('advanta_sections', $arFields['UF_MY_PROJECT']);
            
            $advantaCredentials = AdvantaHelper::getAdvantaCredentials();
            $advantaHelperObj = new AdvantaHelper();
            $sessId = $advantaHelperObj->getSessionId();
            
            if(!$useDatabus)
                $advantaProjectInfo = AdvantaHelper::getAdvantaProjectInfo($sessId, $sectionCode);
            else
                $advantaProjectInfo = AdvantaHelper::getAdvantaProjectInfoByBus($sectionCode);
            //Debug::dbgLog($advantaProjectInfo,'_advantaProjectInfo_');

            TaskHelper::setTaskDates($idTask, $advantaProjectInfo['systemStartDate'], $advantaProjectInfo['systemFinishDate']);
            TaskHelper::setHumansToTask($idTask, $advantaProjectInfo['resources']);
            //Получаем ресурсы

        }


		$advantaCredentials = AdvantaHelper::getAdvantaCredentials();
        //Нужно понять, какие именно поля изменились, и менять в адванте только их
		//Проверяем поля: Описание задачи, Исполнитель, соисполнители, Дата начала, Дата завершения,

		$dealId = (int)str_replace('D_', '', $arFields['UF_CRM_TASK'][0]);
        if(empty($dealId))
            $dealId = (int)str_replace('D_', '', $arFields['META:PREV_FIELDS']['UF_CRM_TASK'][0]);

        if(empty($dealId))
            return true;


		$presaleUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
		$dealInfo = DealHelper::getDealInfoShort($dealId, [$presaleUFCode]);


		$presaleUID = $dealInfo[0][$presaleUFCode];

        if(empty($presaleUID))
            return true;


		$advantaHelperObj = new AdvantaHelper();
		$sessId = $advantaHelperObj->getSessionId();

		$modifiedData = TaskHelper::compareData($arTaskCopy, $arFields, ['TITLE', 'DESCRIPTION','RESPONSIBLE_ID','ACCOMPLICES','START_DATE_PLAN','END_DATE_PLAN','DEADLINE']);


        if(!empty($modifiedData['DEADLINE'])){

            if(!empty($modifiedData['START_DATE_PLAN'])){
                $startDateObj = new \Bitrix\Main\Type\DateTime($modifiedData['START_DATE_PLAN']);
                $startDate = $modifiedData['START_DATE_PLAN']->format("Y-m-d H:i:s");
                $startDate = str_replace(" ","T", $startDate);
                $startDateShort = explode("T", $startDate)[0];
            }else{
                //Сначала нужно посмотреть, может дата начала выставлена, но не менялась
                /*if(empty($arFields['START_DATE_PLAN'])){
                    //$startDateObj = new \Bitrix\Main\Type\DateTime($modifiedData['START_DATE_PLAN']);
                    $startDateObj = new \Bitrix\Main\Type\DateTime();
                    $startDateTimestamp = $startDateObj->getTimestamp();
                    $startDate = new \Bitrix\Main\Type\DateTime();
                    $startDate = $startDate->format("Y-m-d H:i:s");
                    $startDateShort = explode(" ", $startDate)[0];
                    $startDate = str_replace(" ", "T", $startDate);
                }
                */
            }

            $finishDateObj = new \Bitrix\Main\Type\DateTime($modifiedData['DEADLINE']);
            $finishDateTimestamp = $finishDateObj->getTimestamp();
            $finishDate = new \Bitrix\Main\Type\DateTime($modifiedData['DEADLINE']);
            $finishDate = $finishDate->format("Y-m-d H:i:s");

            $finishDate = str_replace(" ","T", $finishDate);
            $finishDateShort = explode("T", $finishDate)[0];
            
            

        }else{
            if(!empty($modifiedData['START_DATE_PLAN'])){
                $startDateObj = new \Bitrix\Main\Type\DateTime($modifiedData['START_DATE_PLAN']);
                $startDate = $modifiedData['START_DATE_PLAN']->format("Y-m-d H:i:s");
                $startDate = str_replace(" ","T", $startDate);
                $startDateShort = explode("T", $startDate)[0];
                Debug::dbgLog($startDateShort,'_startDateShort_');
            }

            if(!empty($modifiedData['END_DATE_PLAN'])){
                $finishDateObj = new \Bitrix\Main\Type\DateTime($modifiedData['END_DATE_PLAN']);
                $finishDate = $modifiedData['END_DATE_PLAN']->format("Y-m-d H:i:s");
                $finishDate = str_replace(" ","T", $finishDate);
                $finishDateShort = explode("T", $finishDate)[0];
                Debug::dbgLog($finishDateShort,'_finishDateShort_');
            }
            //$duration = $finishDateObj->diff($startDateObj)->days;

        }


        $startDateObj = new \DateTime($startDateShort);
        $finishDateObj = new \DateTime($finishDateShort);
        $duration = $finishDateObj->diff($startDateObj)->days;

		$updateXml = AdvantaHelper::buildXmlToRequest('UpdateProject', $sessId,
			[
				'presaleUID' => $presaleUID,
				'TITLE' => $modifiedData['TITLE'],
				'startDate' => $startDate,
				'finishDate' => $finishDate,
                //'duration' => $duration
			]
		);

        


		Debug::dbgLog($updateXml,'_updateXml_');

		$updateXmlResponse = Curl::sendRequest(
			$advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
			['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProject'],
			'UpdateProject',
			$updateXml
		);

        //Debug::dbgLog($updateXmlResponse,'_updateXmlResponse_');


		//Исполнители и соисполнители
		$users = AdvantaHelper::getAllAdvantaUsers($sessId);
		//Debug::dbgLog($users,'_Allusers_');

		$usersToAdd = [];

		if(!empty($modifiedData['RESPONSIBLE_ID']) && !empty($users)) {
			$executorId = $modifiedData['RESPONSIBLE_ID'];
			$salesDepartmentUser = UserHelper::getUserInfo($executorId,['NAME','LAST_NAME','SECOND_NAME']);
			//$executorId = $taskInfo['RESPONSIBLE_ID'];
			$executorInfo = UserHelper::getUserInfo($executorId,['ID','EMAIL']);
			$executorInfo['roleb24'] = 'Исполнитель';
			$executorEmail = $executorInfo['EMAIL'];

			$executorInfo = AdvantaHelper::getUserInfoByEmail($executorEmail, $users);


			$presaleInfo['executorInfo'] = $executorInfo;
			$executorInfo['roleb24'] = 'Исполнитель';
			$usersToAdd[] = $executorInfo;

			//Debug::dbgLog($executorInfo,'_executorInfo_');

		}else{
			//Берём исполнителя из старого массива
			$executorId = $arTaskCopy['RESPONSIBLE_ID'];
			$executorInfo = UserHelper::getUserInfo($executorId,['ID','EMAIL']);
			$executorInfo['roleb24'] = 'Исполнитель';
			$executorEmail = $executorInfo['EMAIL'];
			$executorInfo = AdvantaHelper::getUserInfoByEmail($executorEmail, $users);
			$presaleInfo['executorInfo'] = $executorInfo;
			$executorInfo['roleb24'] = 'Исполнитель';
			$usersToAdd = [];
			$usersToAdd[] = $executorInfo;
		}

		//Обновляем реквизиты пресейла (Поля Основание и Содержание проекта)
		$projectRequisitesUpdateXml = AdvantaHelper::buildXmlToRequest('UpdateProjectFields', $sessId,
			[
				'presaleUID' => $presaleUID,
				'projectBase' => $modifiedData['DESCRIPTION'],
				'projectContent' => $modifiedData['DESCRIPTION'],
				'salesDepartmentUser' => $salesDepartmentUser
			]
		);
		$projectRequisitesUpdateResult = Curl::sendRequest(
			$advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
			['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProjectFields'],
			'UpdateProjectFields',
			$projectRequisitesUpdateXml
		);



		if(!empty($modifiedData['ACCOMPLICES'])) {
			$coExecutorsIdList = $modifiedData['ACCOMPLICES'];
			$coExecutorsExternalIdList = [];
			foreach($coExecutorsIdList as $coExecutorId){
				$info = UserHelper::getUserInfo($coExecutorId,['ID','EMAIL']);
				$email = $info['EMAIL'];
				$coExecutorExternalInfo = AdvantaHelper::getUserInfoByEmail($email, $users);
				$coExecutorExternalInfo['roleb24'] = 'Соисполнитель';
				$coExecutorExternalInfoList[] = $coExecutorExternalInfo;
			}
			$presaleInfo['coExecutorsList'] = $coExecutorExternalInfoList;

			//Debug::dbgLog($coExecutorExternalInfoList,'_coExecutorExternalInfoList_');

			//$usersToAdd[] = $coExecutorExternalInfoList;
			foreach($coExecutorExternalInfoList as $coExecutorItem){
				$usersToAdd[] = $coExecutorItem;
			}

			//Debug::dbgLog($usersToAdd,'_usersToAdd_1_');

		}else{

			if (array_key_exists('ACCOMPLICES', $modifiedData)) {
				//Соисполнителей убрали
			}else{
				//Соисполнителей не трогали - берём старые данные
				$coExecutorsIdList = $arTaskCopy['ACCOMPLICES'];
				$coExecutorsExternalIdList = [];
				foreach($coExecutorsIdList as $coExecutorId){
					$info = UserHelper::getUserInfo($coExecutorId,['ID','EMAIL']);
					$email = $info['EMAIL'];
					$coExecutorExternalInfo = AdvantaHelper::getUserInfoByEmail($email, $users);
					$coExecutorExternalInfo['roleb24'] = 'Соисполнитель';
					$coExecutorExternalInfoList[] = $coExecutorExternalInfo;
					$presaleInfo['coExecutorsList'] = $coExecutorExternalInfoList;
				}
				foreach($coExecutorExternalInfoList as $coExecutorItem){
					$usersToAdd[] = $coExecutorItem;
				}
			}


			if(empty($arTaskCopy['ACCOMPLICES'])){
				//В старом массиве пусто - то есть соисполнителей и не было до изменения задачи
				//Debug::dbgLog('test','_COEXECUTORS_NOT_CHANGE_');
			}else{
				//соисполнители были до изменения задачи, но их ВСЕХ убрали либо вообще не трогали


				/*$coExecutorsIdList = $arTaskCopy['ACCOMPLICES'];
				$coExecutorsExternalIdList = [];
				foreach($coExecutorsIdList as $coExecutorId){
					$info = UserHelper::getUserInfo($coExecutorId,['ID','EMAIL']);
					$email = $info['EMAIL'];
					$coExecutorExternalInfo = AdvantaHelper::getUserInfoByEmail($email, $users);
					$coExecutorExternalInfo['roleb24'] = 'Соисполнитель';
					$coExecutorExternalInfoList[] = $coExecutorExternalInfo;
					$presaleInfo['coExecutorsList'] = $coExecutorExternalInfoList;
				}
				foreach($coExecutorExternalInfoList as $coExecutorItem){
					$usersToAdd[] = $coExecutorItem;
				}
				*/
				//$usersToAdd = $coExecutorExternalInfoList;
			}

		}


		//Debug::dbgLog($usersToAdd,'_usersToAdd_');



		//Debug::dbgLog($users,'_users_');
		/*$usersToAdd = [];
		$usersToAdd[] = $executorInfo;
		if(!empty($coExecutorExternalInfoList)){
			$usersToAdd = array_merge($users, $coExecutorExternalInfoList);
		}
		*/

		//Debug::dbgLog($presaleUID,'_presaleUID_');

		//Если поле "Исполнитель" не менялось, то нужно взять значение из старого массива.
		//Если поле "Соисполнители" не менялось, то также нужно взять значение из старого массива

		AdvantaHelper::deleteAllExecutors($presaleUID, $sessId);
		$presaleCommandUsersXml = AdvantaHelper::buildXmlToRequest('InsertDirectoryRecords', $sessId,
			[
				'presaleId'=> $presaleUID,
				'users' => $usersToAdd
			]
		);
		//Debug::dbgLog($presaleCommandUsersXml,'_presaleCommandUsersXml_');
		$addPresaleCommandResult = Curl::sendRequest(
			$advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
			['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/InsertDirectoryRecords'],
			'InsertDirectoryRecords',
			$presaleCommandUsersXml
		);
		


		//Debug::dbgLog($presaleCommandUsersXml,'_presaleCommandUsersXml_');
		//Debug::dbgLog($addPresaleCommandResult,'_addPresaleCommandResult_');


		//$executorInfo = AdvantaHelper::getUserInfoByEmail($executorEmail, $users);
		//$presaleInfo['executorInfo'] = $executorInfo;
		//$executorInfo['roleb24'] = 'Исполнитель';

		//Конец Исполнители и соисполнители

    }

    //Перед добавлением задачи
    public static function onBeforeTaskAddHandler(&$arTask)
    {
    	return true;
        //Debug::dbgLog('onBeforeTaskAddHandler','_onBeforeTaskAddHandler_');
        //Debug::dbgLog($arTask,'_arTask_');
    }
}
