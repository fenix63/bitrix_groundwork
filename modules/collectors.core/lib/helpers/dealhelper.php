<?php

namespace Collectors\Core\Helpers;

use Collectors\Core\Debug;

class DealHelper
{
	public static function getDealNameById(int $dealCategoryId)
    {
		if(empty($dealCategoryId))
			return null;

        $dbResult = \Bitrix\Crm\Category\DealCategory::getList([
			'filter' => ['=ID' => $dealCategoryId],
			'select' => ['NAME']
		]);

		$categoryName = '';

		if($item = $dbResult->fetch()){
			$categoryName = $item['NAME'];
		}

		return $categoryName;
    }


	//Переместить сделку в нужную категорию
	public static function moveDealToCategory(int $dealId, int $dealCategoryId ,string $crmStatusId)
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		$item = $factory->getItem($dealId);
		$item->set("CATEGORY_ID", $dealCategoryId);
		$item->set("STAGE_ID", $crmStatusId);
		$item->save();
	}

	public static function setDealStatus(int $dealId, string $crmStatusId)
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		$item = $factory->getItem($dealId);
		$item->set("STAGE_ID", $crmStatusId);
		$item->save();
	}

	public static function getDealInfo(int $dealId): array
    {
        $dbDocumentList = \CCrmDeal::GetList(
            [],
            [
                'ID' => $dealId,
                "CHECK_PERMISSIONS" => "N"
            ],
            //['ID','TITLE']
            []
        );

        $data = [];
        while($item = $dbDocumentList->fetch()){
            $data[$item['ID']] = $item;
        }

        return $data;
    }

    public static function getDealStatusNameByStatusId(int $dealCategoryId, string $dealStatusId)
    {
    	$entityId = 'DEAL_STAGE_'.$dealCategoryId;
    	$statuses = \CCrmStatus::GetStatus($entityId);

    	$dealStatusName = $statuses[$dealStatusId]['NAME'];

    	return $dealStatusName;
    }

}