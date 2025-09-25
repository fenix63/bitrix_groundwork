<?php


namespace MyCompany\WebService\VS\Gisgmp;


/**Плательщие
 * Class Payer
 * @package MyCompany\WebService\VS\Gisgmp
 */
class Payer
{
    private $id;
    private $name;

    /**
     * Предоставить данные по плательщику
     */
    public function get(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }
}
