<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Запрос на отмену принудительного квитирования
 * Class AnnulmentServiceProvided
 * @package MyCompany\WebService\VS\Gisgmp
 */
class AnnulmentReconcile implements ForcedAcknowledgementRequest
{
    private string $id;
    private \DateTime $requestDate;
    private string $rqId;
    private string $urn;
    private bool $isSelectionEnd;

    /**Обработать запрос по получению квитанций
     * @return string
     */
    function sendRequest(): string
    {
        return '';
    }
}
