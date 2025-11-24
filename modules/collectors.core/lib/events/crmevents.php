<?php

namespace Collectors\Core\Events;

use Collectors\Core\Debug;
use Collectors\Core\Helpers\DealHelper;
use Collectors\Core\Helpers\UserFieldHelper;
use Collectors\Core\Helpers\Crmhelper;


class CrmEvents
{
	public static function onCrmDealUpdateHandler()
	{
		Debug::dbgLog('onCrmDealUpdateHandler','_onCrmDealUpdateHandler_');

	}

	//После обновления сделки
	public static function onAfterCrmDealUpdateHandler(&$arFields)
	{
		//Debug::dbgLog('onAfterCrmDealUpdateHandler','_onAfterCrmDealUpdateHandler_');

		Debug::dbgLog($arFields,'_arFields_');

		//Надо убедиться, что измененная сделка была в воронке "Лиды"
		$dealCategoryName = DealHelper::getDealNameById($arFields['CATEGORY_ID']);

		if($dealCategoryName=='Лиды'){

			//Проверяем, что статус сделки "В работе"
			$dealInfo = DealHelper::getDealInfo($arFields['ID']);


			$dealStatusName = DealHelper::getDealStatusNameByStatusId($arFields['CATEGORY_ID'],$dealInfo[$arFields['ID']]['STAGE_ID']);


			if($dealStatusName=='В работе'){
				//Проверяем поле "Результат общения с клиентом"
				$communicationResultFieldName = UserFieldHelper::getUserFieldCodeByXmlId('COMMUNICATION_RES');

				if(!empty($communicationResultFieldName) && empty($arFields[$communicationResultFieldName])){
					Debug::dbgLog('Скрываем поля Дата назначенной встречи, Точка продаж и Причина отказа','_HIDE_3_Fields_');
				}else{
					Debug::dbgLog('Отображаем 3 поля','_Show_3_fields_');
				}


				//Проверяем, что поле "Дата назначенной встречи" заполнено
				$meetingDateFieldName = UserFieldHelper::getUserFieldCodeByXmlId('MEET_DATE');
				if(!empty($meetingDateFieldName) && !empty($arFields[$meetingDateFieldName])){


					//Прееносим измененную сделку в воронку "Продажи" (0) и задаём статус "Назначенные встречи"
					DealHelper::moveDealToCategory($arFields['ID'], 0, 'NEW');
				}else{
					//Смотрим поле "Причина отказа". Если оно заполнено - то переводим сделку в статус "Отказ". Воронку не меняем.
					$cancelReasonFieldCode = UserFieldHelper::getUserFieldCodeByXmlId('CANCEL_REASON');
					if(!empty($cancelReasonFieldCode) && !empty($arFields[$cancelReasonFieldCode])){
						$cancelStatusId = Crmhelper::getCRMStatusIdByName('Отказ','DEAL_STAGE_2');

						//Устанавливаем статус "Отказ"
						DealHelper::setDealStatus($arFields['ID'], $cancelStatusId);
					}
				}

			}
			
		}

	}
}