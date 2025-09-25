<?php

namespace MyCompany\WebService\VS\Gisgmp\Bizproc;

class CBPChargesActivity extends \CBPActivity
{

    /**
     * @param string $workflowId - ID экземпляра бизнес процесса, а НЕ шаблон
     * @param string $commandName
     * @param array|null $params
     * @return bool
     */
    public static function startCommand(string $workflowId, string $commandName, array $params = null)// : bool
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        \CBPDocument::SendExternalEvent($workflowId, $commandName, $params, $errors);
        if ($errors) {
            print_r($errors);
            die('Ошибка исполнения БП');
        }

        return true;
    }

    /**Получить название статуса для элемента инфоблока
     * @param int $elementId
     * @return mixed
     */
    public static function getBizprocStageName(int $elementId)
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        $commands = [];
        $docId = ['iblock', 'CIBlockDocument', $elementId];
        $rows = \Bitrix\Bizproc\WorkflowStateTable::getList([
            'select' => ['ID'],
            'order' => ['ID' => 'DESC'],
            'filter' => [
                '=MODULE_ID' => 'iblock',
                '=ENTITY' => 'CIBlockDocument',
                '=DOCUMENT_ID' => $elementId
            ],
            'limit' => 1
        ]);
        if($workflow = $rows->fetch()){
            $stageData = \CBPDocument::GetDocumentState($docId, $workflow["ID"]);
            $stage = $stageData[$workflow["ID"]]['STATE_TITLE'];
        }

        return $stage;
    }

    public static function getBizprocStageCommands(int $elementId) : array
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        $commands = [];
        $docId = ['iblock', 'CIBlockDocument', $elementId];
        $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument($docId);
        if($workflowIds){
            foreach ($workflowIds as $workflowId){
                $stages = \CBPDocument::GetDocumentState($docId, $workflowId);
                $commands[$workflowId] = $stages[$workflowId]['STATE_PARAMETERS'];
            }
        }

        return $commands;
    }

    public static function getCommandIndexByTitle(array $commandList, string $commandTitle): int
    {
        $index = -1;
        foreach($commandList as $commandItemKey=>$commandItem){
            if($commandItem['TITLE']==$commandTitle){
                $index = $commandItemKey;
                break;
            }
        }

        return $index;
    }

    public static function getWorkFlowInstanceIdByElementId(int $elementId): string
    {
        $workFlowInstanceId = 0;
        \Bitrix\Main\Loader::includeModule("bizproc");
        $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument(['iblock', 'CIBlockDocument', $elementId]);
        if ($workflowIds) {
            $workFlowInstanceId = $workflowIds[0];
        }

        return $workFlowInstanceId;
    }

}
