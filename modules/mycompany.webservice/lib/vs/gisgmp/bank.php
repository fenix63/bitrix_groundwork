<?php


namespace MyCompany\WebService\VS\Gisgmp;


use PhpOffice\PhpWord\Exception\Exception;

class Bank
{
    private $bankName;
    private $bankBik;
    private $corrAcount;

    /**
     * Предоставить данные по банку
     * @return array
     */
    public function get(): array
    {
        return [
            'bankName' => $this->bankName,
            'bankBik' => $this->bankBik,
            'corrAccount' => $this->corrAcount
        ];
    }

    /**Задать значения свойств
     * @param array $data
     */
    public function set(string $propertyName, string $propertyValue)
    {
        if (property_exists(get_class($this), $propertyName))
            $this->$propertyName = $propertyValue;
        else
            throw new Exception('Такого свойства ' . $propertyName . ' не существует');
    }
}
