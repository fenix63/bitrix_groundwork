<?php


namespace MyCompany\WebService\VS\Gisgmp;


class OrgAccount
{
    private string $accountNumber;
    private Bank $bank;

    /**
     * Предоставить данные по счёту
     */
    public function get(): array
    {
        return [
            'accountNumber' => $this->accountNumber,
            'bank' => $this->bank->get()
        ];
    }
}
