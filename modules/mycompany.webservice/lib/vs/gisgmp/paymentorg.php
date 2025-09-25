<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Организация, принявшая пдатеж
 * Class PaymentOrg
 * @package MyCompany\WebService\VS\Gisgmp
 */
class PaymentOrg
{
    private $orgAccountAccountNumber;//Номер лицевого счёта
    private Bank $bank;//Данные банка

    /**
     * Предоставить данные по счёту
     */
    public static function get(): array
    {
        return [
            'accountNumber' => self::getAccountNumber(),
            //'bank' => $this->bank->get()
        ];
    }

    public function getAccountNumber()
    {
        return $this->orgAccountAccountNumber;
    }
}
