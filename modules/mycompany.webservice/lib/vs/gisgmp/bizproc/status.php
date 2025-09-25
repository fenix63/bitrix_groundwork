<?php


namespace MyCompany\WebService\VS\Gisgmp\Bizproc;

use MyCompany\WebService\VS\Gisgmp\Bizproc\Common;
use MyCompany\Rest\Response;

class Status
{

    /**
     * @OA\Get(
     *   tags={"Actions"},
     *   path="/actions/status/",
     *   summary="Получить текущий статус выполнения бизнес-процесса",
     *   @OA\Parameter(
     *     name="elementid",
     *     in="query",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function getBPStatus(array $params): Response
    {
        return Common::getExecutionStatus($params);
    }
}
