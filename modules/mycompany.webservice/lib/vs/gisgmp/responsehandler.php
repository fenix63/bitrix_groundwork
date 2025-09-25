<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**
 * Обработчик ответа
 * Interface ResponseHandler
 * @package MyCompany\WebService\VS\Gisgmp
 */
interface ResponseHandler
{
    /**Обработать запрос с ответом
     * @return string
     */
    function prepareMessage(): string;
}
