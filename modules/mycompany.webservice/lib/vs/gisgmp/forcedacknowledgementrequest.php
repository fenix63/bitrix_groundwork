<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Отправитель запроса принудительного учета
 * Interface ForcedAcknowledgementRequest
 * @package MyCompany\WebService\VS\Gisgmp
 */
interface ForcedAcknowledgementRequest extends RequestSender
{
    //TODO::непонятно что тут должно быть, и зачем этот промежуточный интерфейс нужен?
    //Метод "Отправить запрос" и так тут будет, т.к. применяется наследование
}
