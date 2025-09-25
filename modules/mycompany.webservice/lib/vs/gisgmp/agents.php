<?php

namespace MyCompany\WebService\VS\Gisgmp;


use MyCompany\WebService\VS\Gisgmp\ExportPaymentsRequest;


class Agents
{

    public static function dbgLog($data, string $suffix = '_1')
    {
        if (
            !empty($suffix)
            &&
            preg_match('![^-0-9a-zA-Z_]+!', $suffix)
        ) {
            $suffix = '';
        }

        $fileName = $_SERVER["DOCUMENT_ROOT"]."/"."logs/GIS_GMP/AGENTS/dbg-" . date("Ymd") . $suffix . ".txt";
        $r = fopen($fileName, 'a');
        fwrite($r, PHP_EOL);
        fwrite($r, date('Y-m-d H:i:s') . PHP_EOL);
        fwrite($r, print_r($data, 1));
        fwrite($r, PHP_EOL);
        fclose($r);
    }


    public static function sendRequestGisgmpPaymentsAgent()
    {
        $className = '\\MyCompany\\WebService\\VS\\Gisgmp\\ExportPaymentsRequest';
        if (class_exists($className)) {
            $today = getdate();
            $startTime = (int)$today['hours'] - 1 . ':' . $today['minutes'] . ':' . $today['seconds'];
            if ($today['hours'] == '00')
                $startTime = '23' . ':' . $today['minutes'] . ':' . $today['seconds'];

            $finishTime = $today['hours'] . ':' . $today['minutes'] . ':' . $today['seconds'];
            if ($today['seconds'] >= 1 && $today['seconds'] <= 9)
                $finishTime = $today['hours'] . ':' . $today['minutes'] . ':0' . $today['seconds'];

            $finishDate = $today['year'] . '-' . $today['mon'] . '-' . $today['mday'] . 'T' . $finishTime;
            $finishDateObject = new \DateTime($finishDate);
            $finishDate = $finishDateObject->format('Y-m-d H:i:s');
            $finishDate = str_replace(' ', 'T', $finishDate);

            $startDateObject = clone $finishDateObject;
            $startDateObject = $startDateObject->modify('- 1 hours');
            $startDate = $startDateObject->format('Y-m-d H:i:s');
            $startDate = str_replace(' ', 'T', $startDate);

            $object = new $className($startDate, $finishDate);
            $object->sendRequest();
        }else{
            self::dbgLog('error','_Payments_Agent_ERROR_');
        }

        return '\MyCompany\WebService\VS\Gisgmp\Agents::sendRequestGisgmpPaymentsAgent();';
    }

    public static function sendRequestGisgmpQuittanceAgent()
    {
        $className = '\\MyCompany\\WebService\\VS\\Gisgmp\\ExportQuittanceRequest';
        if (class_exists($className)) {
            $today = getdate();
            $startTime = (int)$today['hours'] - 1 . ':' . $today['minutes'] . ':' . $today['seconds'];
            if ($today['hours'] == '00')
                $startTime = '23' . ':' . $today['minutes'] . ':' . $today['seconds'];

            $finishTime = $today['hours'] . ':' . $today['minutes'] . ':' . $today['seconds'];
            if ($today['seconds'] >= 1 && $today['seconds'] <= 9)
                $finishTime = $today['hours'] . ':' . $today['minutes'] . ':0' . $today['seconds'];

            $finishDate = $today['year'] . '-' . $today['mon'] . '-' . $today['mday'] . 'T' . $finishTime;
            $finishDateObject = new \DateTime($finishDate);
            $finishDate = $finishDateObject->format('Y-m-d H:i:s');
            $finishDate = str_replace(' ', 'T', $finishDate);

            $startDateObject = clone $finishDateObject;
            $startDateObject = $startDateObject->modify('- 1 hours');
            $startDate = $startDateObject->format('Y-m-d H:i:s');
            $startDate = str_replace(' ', 'T', $startDate);

            $object = new $className($startDate, $finishDate);
            $object->sendRequest();
        }else{
            self::dbgLog('error','_Quittance_Agent_ERROR_');
        }

        return '\MyCompany\WebService\VS\Gisgmp\Agents::sendRequestGisgmpQuittanceAgent();';
    }

}
