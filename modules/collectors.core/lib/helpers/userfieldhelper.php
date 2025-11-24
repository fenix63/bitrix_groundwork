<?php

namespace Collectors\Core\Helpers;

use Collectors\Core\Debug;

class UserFieldHelper
{
	public static function getUserFieldCodeByXmlId(string $userFieldXmlId)
    {
		$filter = [
            'ENTITY_ID' => 'CRM_DEAL',
            'XML_ID' => $userFieldXmlId
        ];
        $ufDBData = \CUserTypeEntity::GetList( array($by=>$order), $filter );

        $ufCode = '';
        if($ufInfo = $ufDBData->fetch()){
            $ufCode = $ufInfo['FIELD_NAME'];
        }

        return $ufCode;
    }
}