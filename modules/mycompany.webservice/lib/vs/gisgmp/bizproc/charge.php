<?php


namespace MyCompany\WebService\VS\Gisgmp\Bizproc;

use MyCompany\WebService\VS\Gisgmp\Bizproc\Common;
use MyCompany\WebService\VS\Gisgmp\ImportedCharge;
use MyCompany\Rest\Response;
use MyCompany\WebService\Helper;

class Charge
{

    /**
     * @OA\Get(
     *   tags={"Actions"},
     *   path="/actions/charge/{id}/",
     *   summary="Получить набор действий для элемента инфоблока",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     example="1902",
     *     description="ID элемента инфоблока",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function getCommandsList(int $elementId, bool $toFront): array
    {
        $commands = Common::getBizprocStageCommands($elementId, $toFront);
        return $commands;
    }

    /**Запустить команду
     * @param string $commandName
     */

    /**
     * @OA\Post(
     *   tags={"Actions"},
     *   path="/actions/charge/",
     *   summary="Выполнить команду",
     *   @OA\Parameter(
     *     name="elementid",
     *     in="query",
     *     required=true,
     *     example="",
     *     description="ID элемента",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="commandname",
     *     in="query",
     *     required=true,
     *     example="",
     *     description="Название команды",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public static function startCommand(array $params): Response
    {
        return Common::executeCommand($params);
    }

}
