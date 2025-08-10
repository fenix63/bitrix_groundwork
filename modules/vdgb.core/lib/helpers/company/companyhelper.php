<?php

namespace Vdgb\Core\Helpers\Company;

class CompanyHelper
{
    public static function getCompanyRequisites(int $companyId)
    {
        $requisite = new \Bitrix\Crm\EntityRequisite();
        $rs = $requisite->getList([
            "filter" => ["ENTITY_ID" => $companyId, "ENTITY_TYPE_ID" => 4]
        ]);
        $reqData = $rs->fetchAll();

        return $reqData;
    }

    

}

?>