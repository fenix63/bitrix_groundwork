<?php


namespace MyCompany\WebService\VS\Gisgmp;

/**Получатель
 * Class Payee
 * @package MyCompany\WebService\VS\Gisgmp
 */
class Payee
{
    private array $props;//Имя, инн, кпп, огрн и прочее из диаграммы классов
    private OrgAccount $orgAccount;//Счёт начисления

    /**
     * Предоставить данные по получателю
     */
    public function get(): array
    {
        return [
            'props'=> $this->props,
            'orgAccount' => $this->orgAccount->get()
        ];
    }

    public function setProps(array $data)
    {

    }
}
