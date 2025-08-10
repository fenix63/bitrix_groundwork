<?php

namespace Vdgb\Core\Helpers\Task;
use Bitrix\Main\Loader;
use Vdgb\Core\Debug;

\Bitrix\Main\Loader::IncludeModule("tasks");


class TaskHelper
{
	public static function compareData(array $oldData, array $newData, array $fieldsToWatch): array
	{
		$dataIdentical = array_intersect_key($newData, $oldData);
		$dataIdenticalKeys = array_keys($dataIdentical);


		$modifiedData = [];
		foreach($dataIdenticalKeys as $dataKey){

			if($newData[$dataKey]==$oldData[$dataKey] )
				continue;

			$modifiedData[$dataKey] = $newData[$dataKey];
		}

		foreach($modifiedData as $dataKey => &$dataItem){
			if(!in_array($dataKey, $fieldsToWatch))
				unset($modifiedData[$dataKey]);
		}

		return $modifiedData;
	}

	public static function cutHTMLTags(string $content)
	{
		$finishText = $content;
		$pattern = "/\[(.+?)\]/";
		//$finishText = str_replace(["[/SIZE]","[/COLOR]","[/LIST]","[SIZE=12pt]","[SIZE=16px]","[LIST]","[*]","[COLOR=gray]","&nbsp;","[P]","[/P]"],"", $finishText);
		$finishText = preg_replace($pattern, "", $finishText);
		$finishText = htmlentities($finishText, null, 'utf-8');
		$content = str_replace("&nbsp;", " ", $finishText);
		$content = html_entity_decode($content);


		//$finishText = str_replace(["&nbsp;"], "", $finishText);
        
        //return $finishText;
        return $content;
	}

	public static function setTaskDates(int $taskId, string $startDate, string $finishDate)
	{
		//CModule::IncludeModule('tasks');

		global $USER, $APPLICATION;
		$userId = $USER->getId();
		//$oTask = new \CTaskItem($taskId, $userId);
		$obTask = new \CTasks;
		

		
		try
		{
			$success = $obTask->Update($taskId, [
				'UF_PLAN_STARTDATE' => new \Bitrix\Main\Type\DateTime($startDate),
				'UF_PLAN_FINISHDATE' => new \Bitrix\Main\Type\DateTime($finishDate)
			]);
			if($success){
				Debug::dbgLog('success','_set_dates_success_');		
			}else{
				if($e = $APPLICATION->GetException())
					Debug::dbgLog("Error: ".$e->GetString(),'_Error_set_dates_');	
			}
		}
		catch(\Exception $e)
		{
			Debug::dbgLog($e->getMessage(),'_Exception_Update_Task_Deadline_');
		}

		Debug::dbgLog('setTaskDates','_setTaskDates_');
		
	}

	//Заполнить поле UF_HUMAN (ФИО и ID из битрикса) значениеями из адванты (ресурсы)
	public static function setHumansToTask(int $taskId, array $resources)
	{
		$obTask = new \CTasks;
		foreach($resources as $resouceItem){
			$stringToAdd[] = $resouceItem['fullName'].' ['.$resouceItem['ID'].']';
		}
		$success = $obTask->Update($taskId, [
			'UF_HUMAN' => $stringToAdd
		]);
		if($success){
			Debug::dbgLog('success','_set_resources_success_');		
		}else{
			if($e = $APPLICATION->GetException())
				Debug::dbgLog("Error: ".$e->GetString(),'_Error_set_resources_');	
		}

	}

	
}
