<?php

namespace Collectors\Core\Helpers;

use Collectors\Core\Debug;

class Crmhelper
{
	public static function getCRMStatusIdByName(string $crmStatusName, string $crmEntityId)
	{

		$statuses = \CCrmStatus::GetStatusList($crmEntityId);
		$crmStatusId = '';
		foreach($statuses as $statusId => $statusName){
			if($statusName==$crmStatusName)
				$crmStatusId = $statusId;
		}

		return $crmStatusId;
	}
}