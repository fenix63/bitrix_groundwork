<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arMonths_r = array();
for ($i = 1; $i <= 12; $i++)
	$arMonths_r[$i] = ToLower(GetMessage('MONTH_'.$i.'_S'));
?>
<div class="bx-birthday-layout-include">
<?if ($arParams['bShowFilter']):?>
	<div class="bx-birthday-officelink">
<?
	if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):
		if ($arResult['ONLY_MINE'] == 'Y'):
?>
		<a href="<?echo $APPLICATION->GetCurPageParam('', array('department'))?>"><?echo GetMessage('INTR_ISBN_TPL_FILTER_ALL');?></a><br />
<?
		else:
?>
		<a href="<?echo $APPLICATION->GetCurPageParam('department='.$arResult['CURRENT_USER']['DEPARTMENT_TOP'], array('department'))?>"><?echo GetMessage('INTR_ISBN_TPL_FILTER_MINE')?></a><br />
<?
		endif;
	endif;
?>
	</div>
<?
endif;

foreach ($arResult['USERS'] as $arUser)
{
	$birthday = FormatDateEx(
		$arUser['PERSONAL_BIRTHDAY'], 
		false, 
		$arParams['DATE_FORMAT'.($arParams['SHOW_YEAR'] == 'Y' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'M' ? '' : '_NO_YEAR')]
	);
?>
	<div class="bx-user-info">
		<div class="bx-user-info-inner">
			<div class="bx-user-image<?=$arUser['PERSONAL_PHOTO'] ? '' : ' bx-user-image-default'?>"><a href="<?=$arUser['DETAIL_URL']?>"><?=$arUser['PERSONAL_PHOTO'] ? $arUser['PERSONAL_PHOTO'] : '' ?></a></div>
			<div class="bx-user-birthday<?echo $arUser['IS_BIRTHDAY'] ? ' bx-user-birthday-today' : ''?> intranet-date"><?echo $birthday;?></div>
			<div class="bx-user-name">
			<?
			$APPLICATION->IncludeComponent("bitrix:main.user.link",
				'',
				array(
					"ID" => $arUser["ID"],
					"HTML_ID" => "structure_birthday_nearest_".$arUser["ID"],
					"NAME" => $arUser["NAME"],
					"LAST_NAME" => $arUser["LAST_NAME"],
					"SECOND_NAME" => $arUser["SECOND_NAME"],
					"LOGIN" => $arUser["LOGIN"],
					"USE_THUMBNAIL_LIST" => "N",
					"INLINE" => "Y",
					"PATH_TO_SONET_USER_PROFILE" => $arParams["~DETAIL_URL"],
					"PROFILE_URL" => $arUser["DETAIL_URL"],
					"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PM_URL"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"SHOW_YEAR" => $arParams["SHOW_YEAR"],
					"CACHE_TYPE" => $arParams["CACHE_TYPE"],
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
					"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
				),
				false,
				array("HIDE_ICONS" => "Y")
			);
			?>
			</div>
			<div class="bx-user-post"><?echo htmlspecialcharsbx($arUser['WORK_POSITION'])?></div>
			<div class="bx-users-delimiter"></div>
		</div>
	</div>
<?
}
?>
</div>