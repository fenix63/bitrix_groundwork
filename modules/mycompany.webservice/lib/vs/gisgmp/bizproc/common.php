<?php


namespace MyCompany\WebService\VS\Gisgmp\Bizproc;

use MyCompany\Rest\Response;
use MyCompany\Rest\Helper;
use MyCompany\WebService\VS\Gisgmp\ImportedCharge;

class Common
{
    public static function getBizprocStageCommands(int $elementId, bool $toFront = false) : array
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        $commands = [];
        $docId = ['iblock', 'CIBlockDocument', $elementId];
        $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument($docId);
        if($workflowIds){
            $i = 0;
            foreach ($workflowIds as $workflowId){
                $stages = \CBPDocument::GetDocumentState($docId, $workflowId);
                $commands[$i]['workflowId'] = $workflowId;
                $actions = $stages[$workflowId]['STATE_PARAMETERS'];
                $commands[$i]['statusName'] = $stages[$workflowId]['STATE_TITLE'];
                $commands[$i]['readonly'] = false;
                if (str_contains($stages[$workflowId]['STATE_TITLE'], 'readonly')) {
                    $commands[$i]['readonly'] = true;
                    $commands[$i]['statusName'] = str_replace('_readonly', '', $commands[$i]['statusName']);
                }
                $j = 0;
                foreach ($actions as $actionItem) {
                    if ($toFront) {
                        if (!str_contains($actionItem['TITLE'], 'System_')) {
                            if (str_contains($actionItem['TITLE'], 'Link_')) {
                                $commands[$i]['actions'][$j]['TITLE'] = str_replace('Link_', '', $actionItem['TITLE']);
                                $commands[$i]['actions'][$j]['link'] = '/gisgmp/match/?charge=' . $elementId;
                            }else{
                                $commands[$i]['actions'][$j]['TITLE'] = $actionItem['TITLE'];
                                $commands[$i]['actions'][$j]['NAME'] = $actionItem['NAME'];
                            }
                        }
                    } else {
                        $commands[$i]['actions'][$j]['TITLE'] = $actionItem['TITLE'];
                        $commands[$i]['actions'][$j]['NAME'] = $actionItem['NAME'];
                    }

                    $j++;
                }

                $i++;
            }
        }

        return $commands;
    }

    /**
     * @param string $workflowId - ID экземпляра бизнес процесса, а НЕ шаблон
     * @param string $commandName
     * @param array|null $params
     * @return bool
     */
    public static function startCommand(string $workflowId, string $commandName, array $params = null) : bool
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        \CBPDocument::SendExternalEvent($workflowId, $commandName, $params, $errors);
        if ($errors) {
            print_r($errors);
            return false;
            //die('Ошибка исполнения БП');
        }

        return true;
    }

    public static function executeCommand(array $params): Response
    {
        global $USER;
        $userId = $USER->GetID();
        $workFlowInstanceId = self::getWorkFlowInstanceIdByElementId($params["elementid"]);
        if ($workFlowInstanceId == 0)
            return Response::createError('Для элемента инфоблока ' . $params["elementid"] . ' не запущен бизнес-процесс');

        $commands = self::getBizprocStageCommands($params["elementid"], false);

        $commandIndex = self::getCommandIndexByTitle($commands[0]["actions"], $params["commandname"]);
        if ($commandIndex >= 0) {
            $bpResult = self::startCommand($workFlowInstanceId, $commands[0]["actions"][$commandIndex]['NAME'], ['User' => $userId]);

            return Response::createSuccess(['result' => $bpResult]);
        }

        return Response::createError(
            'Команда '.$params["commandname"].' не найдена',
            Response::ERROR_ARGUMENT,
            Response::STATUS_WRONG_REQUEST
        );
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

    public static function getCommandIndexByTitle(array $commandList, string $commandTitle): int
    {
        $index = -1;
        foreach ($commandList as $commandItemKey => $commandItem) {
            if ($commandItem['TITLE'] == $commandTitle) {
                $index = $commandItemKey;
                break;
            }
        }

        return $index;
    }

    //public static function getExecutionStatus(array $params): array
    public static function getExecutionStatus(array $params): Response
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        $docId = ['iblock', 'CIBlockDocument', $params['elementid']];
        if (!Helper\Iblock::isElementExist($params['elementid']))
            return Response::createError('Элемент инфоблока c ID ' . $params['elementid'] . ' не найдден');


        //Может быть запущено одновременно несколько потокоов бизнес-процессов
        $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument($docId);
        $statusList = [];
        if ($workflowIds) {
            $i = 0;
            foreach ($workflowIds as $workflowId) {
                $stages = \CBPDocument::GetDocumentState($docId, $workflowId);
                $statusList[$i]['workflowId'] = $workflowId;
                $statusList[$i]['statusName'] = $stages[$workflowId]['STATE_TITLE'];
                $statusList[$i]['readonly'] = false;
                if (str_contains($stages[$workflowId]['STATE_TITLE'], 'readonly')) {
                    $statusList[$i]['readonly'] = true;
                    $statusList[$i]['statusName'] = str_replace('_readonly', '', $statusList[$i]['statusName']);
                }
                $i++;
            }

            return Response::createSuccess(['result' => $statusList]);
        }

        return Response::createError('Для элемента инфоблока '.$params['elementid'].' не запущен бизнес-процесс');
    }

    /**
     * @OA\Post(
     *   tags={"Actions"},
     *   path="/actions/setstatus/",
     *   summary="Сдвинуть статус БП на нужный",
     *   @OA\Parameter(
     *     name="entityclass",
     *     in="query",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       enum={"ImportedCharge","PayInfo"},
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function changeWorkFlowStatus(array $params)
    {
        switch($params['entityclass']){
            case 'ImportedCharge':
                $iblockCode = 'ImportedCharge';
                $className = '\\MyCompany\\WebService\\VS\\Gisgmp\\' . $params['entityclass'];
                break;
            case 'PayInfo':
                $iblockCode = 'gis-gmp-PaymentInfo';
                $className = '\\MyCompany\\WebService\\VS\\Gisgmp\\PayInfo';
                break;
        }
        $iblockId = Helper\Iblock::getIblockIdByCode($iblockCode);
        $dbItems = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'IBLOCK_ID'],
            'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'IBLOCK_SECTION_ID' => false],
        ]);
        $entityInfoList = [];
        while ($item = $dbItems->fetch()) {
            //TODO придумать как получать свойства у инфоблоков, где очень много свойств (>100)
            $dbProperty = \CIBlockElement::GetProperty($iblockId, $item['ID'], [], ['CODE' => 'BIZPROC_IMPORT_STATUS']);
            if ($propItem = $dbProperty->fetch())
                if (!empty($propItem['VALUE']))
                    $entityInfoList[$item['ID']] = $propItem['VALUE'];
        }


        $workflowTemplateId = $className::getWorkflowTemplateIdByTemplateName(
            $className::WORKFLOW_TEMPLATE_NAME
        );
        foreach ($entityInfoList as $entityId => $finishStatus) {
            //Проверяем, есть ли у элемента уже запущенные бизнес-процессы, если нет - запускаем новый бизнес-процесс
            $workflowIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument(['iblock', 'CIBlockDocument', $entityId]);
            if (!$workflowIds) {
                $wfId = \CBPDocument::StartWorkflow(
                    $workflowTemplateId,
                    ["iblock", "CIBlockDocument", $entityId],
                    [],
                    $errors
                );
                if (count($errors) > 0) {
                    Response::createError('Для элемента ', $entityId . ' не удалось запустить бизнес-процесс');
                } else {
                    switch ($finishStatus) {
                        case 'Подготовлен':
                            $commandName = "System_Статус_подготовлен";
                            break;
                        case 'Оплачено':
                            $commandName = "System_Статус_оплачено";
                            break;
                        case 'Оплачено частично':
                            $commandName = "System_Статус_оплачено_частично";
                            break;
                        case 'Ошибка приема получателем':
                            $commandName = "System_Статус_ошибка_приема_получателем";
                            break;
                        case 'Аннулировано_readonly':
                            $commandName = "System_Статус_аннулировано";
                            break;
                        case 'Изменение начисления':
                            $commandName = "System_Статус_изменение_начисления";
                            break;
                        case 'Импортировано':
                            $commandName = "System_Статус_импортировано";
                            break;
                        case 'Учтен':
                            $commandName = "System_Статус_учтен";
                            break;
                    }
                    if (!empty($commandName))
                        Common::executeCommand(['elementid' => $entityId, 'commandname' => $commandName]);
                }
            }
        }

    }
}
