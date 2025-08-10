<?php

namespace Vdgb\Core\Helpers\Project;
use Bitrix\Main\Loader;
use Vdgb\Core\Debug;

Loader::includeModule('crm');
Loader::IncludeModule("tasks");
Loader::IncludeModule("socialnetwork");

class ProjectHelper
{
    public static function getProjectInfoByTaskId(int $taskId)
    {
        $task = new \Bitrix\Tasks\Item\Task($taskId);
        $taskData = $task->getData();

        

        return $taskData;
    }

    public static function getProjectInfoById(int $projectId)
    {
        if(empty($projectId))
            return [];
        
        

        $projectData = \CSocNetGroup::GetList(
            ["ID" => "DESC"],
            ["ID" => $projectId],
            false,
            false,
            ["ID","NAME","UF_*"]
        );

        $data = [];
        while($projectField = $projectData->fetch()){
            $data = $projectField;
        }

        

        return $data;
    }

    public static function buildTreeHTMLElement(array $tree)
    {
        $html = '<span class="fields enumeration field-wrap" data-has-input="no">';
        $html .= '<select name="advanta_link">';
        $html.='<span class="enumeration-select field-item">';
        foreach($tree as $treeNode){
            $html.='<option value="'.$treeNode['UID'].'">'.$treeNode['Name'].'</option>';
        }
        $html.='</select>';
        $html.='</span>';
        $html.='</span>';

        return html_entity_decode($html);
    }
}

?>