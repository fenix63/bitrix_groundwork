<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Отправитель запроса
 * Interface RequestSender
 * @package MyCompany\WebService\VS\Gisgmp
 */
interface RequestSender
{
    /**Отправить запрос
     * @return mixed
     */
    function sendRequest(): string;
}
