<?php

namespace Vdgb\Resources\Helpers\Task;
use Bitrix\Main\Loader;
use Vdgb\Resources\Debug;
use Vdgb\Resources\Helpers\AdvantaHelper;

\Bitrix\Main\Loader::IncludeModule("tasks");


class TaskHelper
{
    public static function getTaskFieldById(int $taskId, string $fieldName)
    {
        $task = new \Bitrix\Tasks\Item\Task($taskId);
        $data = $task->getData([$fieldName]);

        return $data[$fieldName];
    }

    public static function getAllElapsedTimeByTaskUID(string $advantaTaskUID)
    {
        $timeDataList = [];

        $elapsedTimeList = AdvantaHelper::sendRequest(
            'GetRecords',
            [
                'directoryId' => '977ff0ae-56f8-40ad-8919-8175788e38c8',
                'projectUID' => $advantaTaskUID,
                'nodeTarget' => 'RecordWrapper',
                'urlTail' => '/components/services/APIService.asmx',
                'curlHeaders' => ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/GetRecords']
            ]
        );

        return $elapsedTimeList;
    }

    public static function getElapsedTimeDataByUID(string $advantaTaskUID, string $recordId): array
    {
        $elapsedTimeList = self::getAllElapsedTimeByTaskUID($advantaTaskUID);

        $index = -1;
        $elapsedTimeRecordIdList = array_column($elapsedTimeList, 'RecordId');
        $index = array_search($recordId, $elapsedTimeRecordIdList);

        $targetData = $elapsedTimeList[$index];

        return $targetData;
    }

    public static function getElapsedTimeRecordField(array $data, string $recordUID, string $fieldName)
    {
        $allFields = $data['Fields']['FieldWrapper'];
        $fieldNameList = array_column($allFields,'FieldName');
        $index = array_search($fieldName, $fieldNameList);

        $fieldValue = $allFields[$index]['FieldVal'];

        return $fieldValue;
    }

    public static function getTaskInfoById(int $taskId, string $UFField = '')
    {
        $info = [];

        $dbTask = \Bitrix\Tasks\Item\Task::getInstance($taskId);

        if(empty($UFField))
            $info = $dbTask->getData();
        else
            $info = $dbTask->getData([$UFField]);

        return $info;
    }
}