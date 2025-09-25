<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Доп реквизиты платежа
 * Class BudgetIndex
 * @package MyCompany\WebService\VS\Gisgmp
 */
class BudgetIndex
{
    private string $budgetIndexStatus;
    private string $budgetIndexPayReason;
    private string $budgetIndexTaxPeriod;
    private string $budgetIndexTaxDocNumber;
    private string $budgetIndexTaxDocDate;

    /**получить доп реквизиты платежа
     * @return array
     */
    public function get(): array
    {
        return [
            'budgetIndexStatus' => $this->budgetIndexStatus,
            'budgetIndexPayReason' => $this->budgetIndexPayReason,
            'budgetIndexTaxPeriod' => $this->budgetIndexTaxPeriod,
            'budgetIndexTaxDocNumber' => $this->budgetIndexTaxDocNumber,
            'budgetIndexTaxDocDate' => $this->budgetIndexTaxDocDate,
        ];
    }
}
