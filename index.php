<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>

<style>
	.ajax-block{
		margin-top: 40px;

	}

	.b-textCnt th{
		border-left: 1px solid #000;
	}

	.company_logo{
		clear: both;
		display: block;
	}
</style>

<form  id="myform">

	<select data-filter name="PROPERTY_COMPANY" style="width: 100px;">
		<option value="">Выберите Компанию</option>
		<option value="National Securities Depository Limited">National Securities Depository Limited</option>
		<option value="National Settlement Depository">National Settlement Depository</option>
		<option value="Merkezi Kayıt Kuruluşu A.Ş.">Merkezi Kayıt Kuruluşu A.Ş.</option>
		<option value="TDCC Taiwan Depository &amp; Clearing Corporation">TDCC Taiwan Depository &amp; Clearing Corporation</option>
		<option value="Central Depository of Armenia">Central Depository of Armenia</option>
		<option value="CSDC (China Securities Depository and Clearing Corporation)">CSDC (China Securities Depository and Clearing Corporation)</option>
		<option value="Euroclear">Euroclear</option>
		<option value="KSD (Korean Securities Depository)">KSD (Korean Securities Depository)</option>
		<option value="SHCH (Shanghai Clearing House)">SHCH (Shanghai Clearing House)</option>
	</select>

	<select data-filter name="PROPERTY_COUNTRY">
		<option value="" >Выберите страну</option>
		<option value="India"  >India</option>
		<option value="Russia"  >Russia</option>
		<option value="Turkey"  >Turkey</option>
		<option value="Taiwan"  >Taiwan</option>
		<option value="Armenia"  >Armenia</option>
		<option value="China"  >China</option>
		<option value="Belgium"  >Belgium</option>
		<option value="Korea"  >Korea</option>
		<option value="Korea"  >Korea</option>
	</select>

<input data-filter type="date" name="PROPERTY_DATE_FROM">
<input data-filter type="date" name="PROPERTY_DATE_TO">


	<select data-filter name="PROPERTY_SUBJECT" style="width: 140px;">
		<option value="">Выберите тему</option>
		<option value="E-Voting System of NSDL (Video)">E-Voting System of NSDL (Video)</option>
		<option value="E-Voting System of NSDL (Presentation)">E-Voting System of NSDL (Presentation)</option>
		<option value="Creating the E-Voting System for Shareholder Meetings in Russia (Article)">Creating the E-Voting System for Shareholder Meetings in Russia (Article)</option>
		<option value="E-voting System (Presentation)">E-voting System (Presentation)</option>
		<option value="Electronic General Meeting (e-GEM)">Electronic General Meeting (e-GEM)</option>
		<option value="Public Disclosure Platform (PDP)">Public Disclosure Platform (PDP)</option>
		<option value="Implementation of Non-Custody Services">Implementation of Non-Custody Services</option>
		<option value="E-Voting Opportunities">E-Voting Opportunities</option>
		<option value="FinTech and Cyber Resilience (Presentation, WFC Board, May 2017)">FinTech and Cyber Resilience (Presentation, WFC Board, May 2017)</option>
		<option value="Better Corporate Governance With E-voting Gradually Gets Adopted in Taiwan. -a Big Data Analysis of Taiwan’s E-voting in AGMs">Better Corporate Governance With E-voting Gradually Gets Adopted in Taiwan. -a Big Data Analysis of Taiwan’s E-voting in AGMs</option>
		<option value="Cash accounts &amp; New opportunities">Cash accounts &amp; New opportunities</option>
		<option value="Government bonds for individuals">Government bonds for individuals</option>
		<option value="Legal Risk Management for Stock Connect">Legal Risk Management for Stock Connect</option>
		<option value="Cross-border links challenges">Cross-border links challenges</option>
		<option value="CSDs’ role in Implementing Cross-border investments">CSDs’ role in Implementing Cross-border investments</option>
		<option value="China Bond Market">China Bond Market</option>
	</select>
</form>


<form id="search-form">
	<?/*$APPLICATION->IncludeComponent("bitrix:search.form","",
		Array(
        	"USE_SUGGEST" => "N",
        	"PAGE" => "#SITE_DIR#search/index.php"
    	)
);*/?>
</form>

<?


//$GLOBALS['filter_ex'] = array('PROPERTY_COUNTRY' => $_GET['PROPERTY_COUNTRY']);//фильтруем по стране

?>

<?
	if($_REQUEST['ajax']){
		$APPLICATION->RestartBuffer();
	}
?>


	<?

		//$GLOBALS['filter_ex'] = array();
		$GLOBALS['filter_ex'] = array(
			'PROPERTY_COUNTRY' => $_GET['PROPERTY_COUNTRY'],
			'PROPERTY_COMPANY' => $_GET['PROPERTY_COMPANY'],
			'PROPERTY_SUBJECT' => $_GET['PROPERTY_SUBJECT'],

			'>=PROPERTY_DATE_OF_PLACEMENT' => $_GET['PROPERTY_DATE_FROM'],
			'<=PROPERTY_DATE_OF_PLACEMENT' => $_GET['PROPERTY_DATE_TO'],

		);//фильтруем по стране

//echo '<pre>';
//var_dump($_GET);
//var_dump($_REQUEST);
//echo '</pre>';
	?>

<div class="ajax-block">
<?$APPLICATION->IncludeComponent(
	"bitrix:news.list", 
	"pdf_list", 
	array(
		"COMPONENT_TEMPLATE" => "pdf_list",
		"IBLOCK_TYPE" => "ru",
		"IBLOCK_ID" => "54",
		"NEWS_COUNT" => "20",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_ORDER1" => "DESC",
		"SORT_BY2" => "SORT",
		"SORT_ORDER2" => "ASC",
		"FILTER_NAME" => "filter_ex",
		"FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"SET_TITLE" => "Y",
		"SET_BROWSER_TITLE" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"SET_LAST_MODIFIED" => "N",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"INCLUDE_SUBSECTIONS" => "Y",
		"STRICT_SECTION_CHECK" => "N",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"PAGER_TEMPLATE" => ".default",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"PAGER_TITLE" => "Новости",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"SET_STATUS_404" => "N",
		"SHOW_404" => "N",
		"MESSAGE_404" => ""
	),
	false
);?>

</div>


<?
	if($_REQUEST['ajax']){
		die();
	}
?>


<script>

	$(document)
	.on('change', '[data-filter]', function() {
	  $(this).closest('form#myform').trigger('submit')
	})
	.on('submit', 'form#myform', function(e) {
		e.preventDefault();
		//console.log('country=' + $('select[name="PROPERTY_COUNTRY"] option:selected').val());
		$data = $(this).serializeArray();
		console.log($data);


		var a = [];
			$('[data-filter]').each(function(i, e) {
				$(this).val() != '' ? a.push($(this).serialize()) : '';
			 })
			history.pushState({}, "", '?'+a.join('&'));



		$.ajax({
		  type: "GET",
		  url: 'https://aecsd.org/test?ajax=Y',

			data: $(this).serializeArray(),
			//data: $data[1],
		  success: function(msg){
			console.log('success');
			console.log('msg=');
			console.log(msg);
			console.log('---msg end---');

			$new_table = $(msg).find('table');

			  //$new_table =  $('.ajax-block').html($(msg).find('table'));
			console.log('$new_table=',$new_table);
			$('.ajax-block').html($new_table);
			  //var state = { 'page_id': 1, 'user_id': 5 };

			  //history.pushState(state, "", location.href + '?' + $(this).serialize());
			  //history.pushState(state, "", $(this)[0]['url']);



		   },
		   error: function(msg){
			  console.log('error');
		   }
		});


	});


</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>