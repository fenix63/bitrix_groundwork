<?php

namespace Vdgb\Resources\Helpers\Notify;

class NotifyHelper
{
    public static function buildNotifyMessageText(array $projectData): string
    {
        $text = '';
        switch($projectData['statuscode']){
            case '2b8387a4-42b7-4996-bc77-7026dde41a94':
                //Отклонена
                $text = 'Ваши трудозатраты по проекту "'.$projectData['projectname'].'" за период '.$projectData['timeelapseddate'].' отклонены '.$projectData['statuschangedate'].' по причине '.$projectData['managercomment'].'. Вам необходимо скорректировать ваши трудозатраты';
                break;

            case 'c9283ac8-33aa-4b86-9912-aa21139d9035':
                //На согласовании
                $text = 'Корректировка и удаление трудозатрат не возможны. Трудозатраты на согласовании. Обратитесь к РП/ Функциональному руководителю для отклонения ваших трудозатрат';
                break;

            case 'aa1bb8a4-eceb-4dc4-af69-885ca144f99e':
                //Согласовано
                $text = 'Корректировка и удаление трудозатрат не возможны. Трудозатраты согласованы';
                break;
        }

        return $text;
    }
}