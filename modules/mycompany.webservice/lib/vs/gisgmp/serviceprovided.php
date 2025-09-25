<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Запрос на принудительный учет платежа
 * Class AnnulmentServiceProvided
 * @package MyCompany\WebService\VS\Gisgmp
 */
class ServiceProvided implements ForcedAcknowledgementRequest
{
    private string $id;
    private \DateTime $requestDate;
    private string $rqId;
    private string $urn;
    private bool $isSelectionEnd;
    private Quittance $quittance;

    /**Обработать запрос по получению квитанций
     * @return string
     */
    function sendRequest(): string
    {
        return '';
    }

    /**получить данные по квитанции
     * @return array
     */
    function getQuittance(): array
    {
        $this->quittance = new Quittance();
        return $this->quittance->get();
    }
}
