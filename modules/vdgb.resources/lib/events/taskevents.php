<?php

namespace Vdgb\Resources\Events;
use Bitrix\Main\Loader;
use Vdgb\Resources\Debug;
use Vdgb\Resources\Helpers\AdvantaHelper;
use Vdgb\Resources\Curl;
use Vdgb\Resources\Helpers\User\UserHelper;
use Vdgb\Resources\Helpers\Task\TaskHelper;
use Vdgb\Resources\Entity\MappingTable;


Loader::includeModule("tasks");
Loader::includeModule("timeman");

class TaskEvents
{
    public static function onTaskElapsedTimeAddHandler(&$elapsedTimeItemId)
    {
        //Debug::dbgLog('onTaskElapsedTimeAddHandler','_onTaskElapsedTimeAddHandler_');
        //Debug::dbgLog($params,'_paramsAdd_');

        
        $elapsedTimeItemData = self::getElapsedTimeData($elapsedTimeItemId);
        //Debug::dbgLog($elapsedTimeItemData,'_elapsedTimeItemData_');

        //$date = new \Bitrix\Main\Type\DateTime($elapsedTimeItemData[0]['CREATED_DATE'], "Y-m-d H:i:s");//18.06.2025 20:55:00
        $date = new \Bitrix\Main\Type\DateTime($elapsedTimeItemData[0]['CREATED_DATE']);

        //Debug::dbgLog($date,'_date_');
        //Debug::dbgLog($date->format("Y-m-d H:i:s"),'_date_format_');
        

        //$elapsedTimeByTaskId = self::getElapsedTimeData(-1, 48766);
        //Debug::dbgLog($elapsedTimeByTaskId,'_elapsedTimeByTaskId_');

        //$timeByFilter = self::getAmountTimeByFilter(["USER_ID" => 14682, "TASK_ID" => 48766]);
        //Debug::dbgLog($timeByFilter,'_timeByFilter_');
        
        $sessId = AdvantaHelper::getSessionId();
        
        //Получить записи справочника (Пользователи)
        $allAdvantaUsers = AdvantaHelper::sendRequest(
            'GetPersons',
            [
                'nodeTarget' => 'SlPerson',
                'urlTail' => '/components/services/persons.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons']
            ]
        );

        

        $curUserUID = UserHelper::getCurUserUID($allAdvantaUsers);

        //UF_ADVANTA_TASK
        $advantaTaskUID = TaskHelper::getTaskFieldById($elapsedTimeItemData[0]['TASK_ID'], 'UF_ADVANTA_TASK');
        $hours = round($elapsedTimeItemData[0]['MINUTES'] / 60, 2);
        

        $addTimeItemResult = AdvantaHelper::sendRequest(
            'InsertDirectoryRecord',
            [
                'projectUID' => $advantaTaskUID,
                'userUID' => $curUserUID,
                'time' => $hours,
                'comment' => $elapsedTimeItemData[0]['COMMENT_TEXT'],
                'urlTail' => '/components/services/APIService.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/InsertDirectoryRecord'],
                'nodeTarget' => 'InsertDirectoryRecordResult',
                'date' => str_replace(" ","T",$date->format("Y-m-d H:i:s"))
            ]
        );
        
        
        MappingTable::add([
            'ID' => $elapsedTimeItemData[0]['ID'],
            'ADVANTA_TASK_RECORD_UID' => $addTimeItemResult
        ]);

        //Записываем в таблицу resources_mapping новую запись, где ID - это ID из таблицы b_tasks_elapsed_time (это строки с записями о затраченном времени), а ADVANTA_TASK_RECORD_UID - это UID записи о трудозатрате из адванты


        /*$records = AdvantaHelper::sendRequest(
            'GetRecords',
            [
                'projectUID'=>'6f33d183-c6ac-4419-b7df-ced8fe9c7b07',
                'directoryId' => '977ff0ae-56f8-40ad-8919-8175788e38c8',
                'urlTail' => '/components/services/APIService.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/GetRecords']
            ]
        );
        */
        

        //Debug::dbgLog($records,'_records_');

        //Отправляем запись о трудозатрате в адванту
        //AdvantaHelper::sendRequest();

    }

    public static function onBeforeTaskElapsedTimeUpdateHandler($elapsedTimeItemId)
    {
        //Debug::dbgLog('onBeforeTaskElapsedTimeUpdateHandler','_onBeforeTaskElapsedTimeUpdateHandler_');
        //Debug::dbgLog($elapsedTimeItemId,'_elapsedTimeItemId_before_update__');
        //Если трудозатрата в Адванте в статусе "На согласовании" или "Согласовано" - то редактировать такую трудозатрату нельзя
        
        $elapsedTimeItemData = self::getElapsedTimeData($elapsedTimeItemId);

        $taskId = $elapsedTimeItemData[0]['TASK_ID'];
        $advantaTaskUID = TaskHelper::getTaskInfoById($taskId, 'UF_ADVANTA_TASK')['UF_ADVANTA_TASK'];


        $advantaElapsedTimeRecordUID = MappingTable::getList([
            'select' => ['ADVANTA_TASK_RECORD_UID'],
            'filter' => ['ID' => $elapsedTimeItemId]
        ])->fetch()['ADVANTA_TASK_RECORD_UID'];     

        $elapsedTimeData = TaskHelper::getElapsedTimeDataByUID($advantaTaskUID, $advantaElapsedTimeRecordUID);
        

        //2b8387a4-42b7-4996-bc77-7026dde41a94 - не согласовано
        //c9283ac8-33aa-4b86-9912-aa21139d9035 - на согласовании
        //aa1bb8a4-eceb-4dc4-af69-885ca144f99e - Согласовано
        $elapsedTimeItemStatus = TaskHelper::getElapsedTimeRecordField($elapsedTimeData, $advantaElapsedTimeRecordUID, 'Статус согласования факта');

        /*if($elapsedTimeItemStatus=='c9283ac8-33aa-4b86-9912-aa21139d9035' ||
            $elapsedTimeItemStatus=='aa1bb8a4-eceb-4dc4-af69-885ca144f99e')
        {
            throw new \Bitrix\Main\SystemException("Корректировка и удаление трудозатрат не возможны. Трудозатраты согласованы");
        }
        */

        switch($elapsedTimeItemStatus){
            case 'c9283ac8-33aa-4b86-9912-aa21139d9035':
                throw new \Bitrix\Main\SystemException("Корректировка и удаление трудозатрат не возможны. Трудозатраты на согласовании. Обратитесь к РП/ Функциональному руководителю для отклонения ваших трудозатрат");
            break;

            case 'aa1bb8a4-eceb-4dc4-af69-885ca144f99e':
                throw new \Bitrix\Main\SystemException("Корректировка и удаление трудозатрат не возможны. Трудозатраты согласованы.");
            break;
        }
        

    }

    public static function onTaskElapsedTimeUpdateHandler($elapsedTimeItemId)
    {
        //Debug::dbgLog('onTaskElapsedTimeUpdateHandler','_onTaskElapsedTimeUpdateHandler_');
        //Debug::dbgLog($elapsedTimeItemId,'_elapsedTimeItemId_');

        $elapsedTimeItemData = self::getElapsedTimeData($elapsedTimeItemId);
        //Debug::dbgLog($elapsedTimeItemData,'_elapsedTimeItemData_update_');
        $advantaItemUID = MappingTable::getList([
            'select' => ['ADVANTA_TASK_RECORD_UID'],
            'filter' => ['ID' => $elapsedTimeItemId]
        ])->fetch()['ADVANTA_TASK_RECORD_UID'];

        $sessId = AdvantaHelper::getSessionId();
        $hours = round($elapsedTimeItemData[0]['MINUTES'] / 60, 2);
        $date = new \Bitrix\Main\Type\DateTime($elapsedTimeItemData[0]['CREATED_DATE']);


        $allAdvantaUsers = AdvantaHelper::sendRequest(
            'GetPersons',
            [
                'nodeTarget' => 'SlPerson',
                'urlTail' => '/components/services/persons.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons']
            ]
        );
        $curUserUID = UserHelper::getCurUserUID($allAdvantaUsers);


        $updateAdvantaRecord = AdvantaHelper::sendRequest(
            'ChangeDirectoryRecord',
            [
                'directoryRecordId' => $advantaItemUID,
                'resourceUID' => $curUserUID,
                'date' => str_replace(" ","T",$date->format("Y-m-d H:i:s")),
                'time' => $hours,
                'comment' => $elapsedTimeItemData[0]['COMMENT_TEXT'],
                'urlTail' => '/components/services/APIService.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/ChangeDirectoryRecord'],
                'nodeTarget' => 'ChangeDirectoryRecordResult'
            ]
        );

        //Debug::dbgLog($updateAdvantaRecord,'_updateAdvantaRecord_update_');

    }

    public static function onBeforeTaskElapsedTimeDeleteHandler($elapsedTimeItemId)
    {
        //Debug::dbgLog('onBeforeTaskElapsedTimeDeleteHandler','_onBeforeTaskElapsedTimeDeleteHandler_');
        //Debug::dbgLog($elapsedTimeItemId,'_elapsedTimeItemId_');

        $elapsedTimeItemData = self::getElapsedTimeData($elapsedTimeItemId);
        $taskId = $elapsedTimeItemData[0]['TASK_ID'];
        $advantaTaskUID = TaskHelper::getTaskInfoById($taskId, 'UF_ADVANTA_TASK')['UF_ADVANTA_TASK'];

        $advantaElapsedTimeRecordUID = MappingTable::getList([
            'select' => ['ADVANTA_TASK_RECORD_UID'],
            'filter' => ['ID' => $elapsedTimeItemId]
        ])->fetch()['ADVANTA_TASK_RECORD_UID'];
        $elapsedTimeData = TaskHelper::getElapsedTimeDataByUID($advantaTaskUID, $advantaElapsedTimeRecordUID);
        $elapsedTimeItemStatus = TaskHelper::getElapsedTimeRecordField($elapsedTimeData, $advantaElapsedTimeRecordUID, 'Статус согласования факта');

        //throw new \Bitrix\Tasks\ActionFailedException("Отмена обновления трудозатраты");
        /*if($elapsedTimeItemStatus=='c9283ac8-33aa-4b86-9912-aa21139d9035' ||
            $elapsedTimeItemStatus=='aa1bb8a4-eceb-4dc4-af69-885ca144f99e')
        {
            throw new \Bitrix\Main\SystemException("Корректировка и удаление трудозатрат не возможны. Трудозатраты согласованы");
        }
        */

        switch($elapsedTimeItemStatus){
            case 'c9283ac8-33aa-4b86-9912-aa21139d9035':
                throw new \Bitrix\Main\SystemException("Корректировка и удаление трудозатрат не возможны. Трудозатраты на согласовании. Обратитесь к РП/ Функциональному руководителю для отклонения ваших трудозатрат");
            break;

            case 'aa1bb8a4-eceb-4dc4-af69-885ca144f99e':
                throw new \Bitrix\Main\SystemException("Корректировка и удаление трудозатрат не возможны. Трудозатраты согласованы.");
            break;
        }

    }

    public static function onTaskElapsedTimeDeleteHandler($elapsedTimeItemId)
    {
        //Debug::dbgLog('onTaskElapsedTimeDeleteHandler','_onTaskElapsedTimeDeleteHandler_');
        //Debug::dbgLog($elapsedTimeItemId,'_elapsedTimeItemId_delete_');

        $advantaItemUID = MappingTable::getList([
            'select' => ['ADVANTA_TASK_RECORD_UID'],
            'filter' => ['ID' => $elapsedTimeItemId]
        ])->fetch()['ADVANTA_TASK_RECORD_UID'];
        $deleteAdvantaRecord = AdvantaHelper::sendRequest(
            'DeleteDirectoryRecord',
            [
                'directoryRecordId' => $advantaItemUID,
                'urlTail' => '/components/services/APIService.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/DeleteDirectoryRecord'],
                'nodeTarget' => 'DeleteDirectoryRecordResult'
            ]
        );


        //Debug::dbgLog($deleteAdvantaRecord,'_deleteAdvantaRecord_');
        if($deleteAdvantaRecord=='Error: У Вас нет прав на удаление записей справочника'){
            //Debug::dbgLog($deleteAdvantaRecord,'_PermissionError_');
        }else{
            //Удаляем запись из таблицы MappingTable
            MappingTable::delete(
                $elapsedTimeItemId
            );
        }
        
    }

    //Получить трудозатраты по конкретной записи из таблицы b_tasks_elapsed_time конкретной задачи
    public static function getElapsedTimeData(int $elapsedTimeItemId = -1, int $taskId = -1): array
    {
        if (\CModule::IncludeModule("tasks"))
        {
            $filter = [];

            if($elapsedTimeItemId!=-1)
                $filter["ID"] = $elapsedTimeItemId;

            if($taskId!=-1)
                $filter["TASK_ID"] = $taskId;

            $res = \CTaskElapsedTime::GetList(
                [], 
                $filter
            );
            $elapsedTime = 0;
            $elepsedTimeData = [];
            while ($arElapsed = $res->Fetch())
            {
                //$elapsedTime += $arElapsed["MINUTES"];
                $elepsedTimeData[] = $arElapsed;
            }
            //echo "Затраченное время: ".floor($elapsedTime / 60)."ч. ".($elapsedTime % 60)."м.";

            return $elepsedTimeData;
        }
    }

    public static function getAmountTimeByFilter(array $filter): array
    {
        if (\CModule::IncludeModule("tasks")){
            $res = \CTaskElapsedTime::GetList(
                [], 
                $filter
            );

            $result = [];
            while($timeItem = $res->fetch()){
                $result[] = $timeItem;
            }

            return $result;
        }
    }
}