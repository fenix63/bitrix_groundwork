<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("НАЦИОНАЛЬНЫЙ РАСЧЕТНЫЙ ДЕПОЗИТАРИЙ");
$APPLICATION->SetPageProperty('title', "dashamail");
$APPLICATION->SetPageProperty('FULL_PAGE', 'Y');
$APPLICATION->SetPageProperty("NOT_SHARE", "Y");
?>

<style>
	#subscription-success{
		display: none;
		position: fixed;
	    top: 0;
	    left: 0;
	    width: 100%;
	    height: 101vh;
	    background: rgba(0,0,0,0.3);
	    z-index: 999999999999;
	}

	#subscription-success .content{
		padding: 20px;
		width: 400px;
	    height: 100px;
	    margin: 0 auto;
	    position: relative;
	    top: 40%;
	    background: #fff;
	    text-align: center;
	}

	#subscription-success .content .title{
		font-size: 16px;
    	font-weight: 700;
	}

	#subscription-success .content button{
		padding: 8px;
	    text-transform: uppercase;
	    margin-top: 30px;
	    background: #ce1126;
	    color: #fff;
	    border: none;
	    width: 165px;
	}

	.feeds-list > .feeds-list__item{
		width: 40%;
		float: left;
	}

	.form.form_subscription{
		clear:both;
		
	}
</style>

<div class="section-header__top block block_grey">
	<div class="section-header__top block block_grey">
		<div class="section-header__wrapper block-wrapper">
			
			<?
				$arSelect = Array("ID", "IBLOCK_ID","SORT", "NAME", "DATE_ACTIVE_FROM","PROPERTY_*");
				$arFilter = Array("IBLOCK_ID"=>48, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
				$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect);
				$elements = [];
				while($ob = $res->GetNextElement()){
					$arFields = $ob->GetFields();
					$elements[] = $arFields;
					//echo $arFields['NAME'].'<br/>';
				}

				// echo '<pre>';
				// print_r($elements);
				// echo '</pre>';
			?>

			<form id="subscribe_form">
				<div class="section-header__title title-h2">Выберите интересующие вас рассылки</div>
				<div class="feeds-list">

					<?foreach($elements as $element){?>

						<div class="feeds-list__item" style="padding-bottom: 10px;">
							<div class="form__field">
								<label js-inputshadow class="toggle">
									<input type="checkbox" data-name="<?=$element['NAME']?>" name="subscription[]" value="<?=$element['ID']?>" class="toggle__input">
									<span class="toggle__fake"></span>
									<span class="toggle__main">
										<span class="toggle__title"><?=$element['NAME']?></span>
									</span>
								</label>
							</div>
						</div>
					<?}?>
				</div>


				<div class="form form_subscription">
					<div class="form__section form__section_delimiter">
						<div class="form__columns form__columns_width_medium form__columns_2x form__columns_fields">
							<div class="form__columns-item">
								<div class="form__field">
									<label js-inputshadow class="field">
										<span class="field__title">
											Ваше имя
										</span>
										<span class="field__main">
											<input type="text" class="field__input" name="name">
										</span>
									</label>
								</div>
							</div>
							<div class="form__columns-item">
								<div class="form__field">
									<label js-inputshadow class="field">
										<span class="field__title">
											Компания
										</span>
										<span class="field__main">
											<input type="text" class="field__input" name="organization">
										</span>
									</label>
								</div>
							</div>
						</div>
						<div class="form__columns form__columns_width_medium form__columns_2x form__columns_fields">
							<div class="form__columns-item">
								<div class="form__field">
									<label js-inputshadow class="field">
										<span class="field__title">
											Ваша фамилия
										</span>
										<span class="field__main">
											<input type="text" class="field__input" name="surname">
										</span>
									</label>
								</div>
							</div>
							<div class="form__columns-item">
								<div class="form__field">
									<label js-inputshadow class="field">
										<span class="field__title">
											Должность
										</span>
										<span class="field__main">
											<input type="text" class="field__input" name="position">
										</span>
									</label>
								</div>
							</div>
						</div>
						<div class="form__columns form__columns_width_medium form__columns_2x form__columns_fields">
							<div class="form__columns-item">
								<div class="form__field" style="width: 395px;">
									<label js-inputshadow class="field">
										<span class="field__title">Email</span>
										<span class="field__main">
											<input class="field__input" type="text" placeholder="Введите ваш email" name="email" />
										</span>
										
									</label>
								</div>
							</div>
						</div>
					</div>

				</div>


				


				<input type="submit" value="Подписаться" style="border:none;" />
			</form>
		</div>
	</div>
</div>

<div id="subscription-success">
	<div class="content">
		<div class="title">Вы удачно подписаны!</div>
		<button>Ок</button>
	</div>
</div>


<script src="<?=SITE_TEMPLATE_PATH.'/js/jquery_3.4.1.min.js'?>"></script>
<script>
	$(document).ready(function(){
		console.log('dasha mail');
		$email = $('form#subscribe_form input[name="email"]')[0];
		$name = $('form#subscribe_form input[name="name"]')[0];
		$surname = $('form#subscribe_form input[name="surname"]')[0];
		$org_name = $('form#subscribe_form input[name="organization"]')[0];
		$position = $('form#subscribe_form input[name="position"]')[0];

		


		$group_name = $('form#subscribe_form input[name="address_group_name"]')[0];

		$('#subscription-success button').on('click', function(){
			$('#subscription-success').fadeOut(300);
		});


		
		$('form#subscribe_form').submit(function(e){
			e.preventDefault();
			//$form_data = $(this).serializeArray();
			$form_data = $(this).find('input[name="subscription[]"]:checked');


			console.log('email:');
			console.log($email.value);

			console.log('name:');
			console.log($name.value);

			console.log('surname:');
			console.log($surname.value);

			console.log('org_name:');
			console.log($org_name.value);

			console.log('position:');
			console.log($position.value);

			//console.log('form data:');
			//console.log($form_data);
			$feed_names = [];
			$form_data.each(function(index){
				//console.log($form_data[index].dataset.name);
				$feed_names.push($form_data[index].dataset.name);
			});


			//console.log('email:');
			//console.log($email.value);

			//console.log('address_group_name:');
			//console.log($group_name.value);//checked - проверка на установку флажка
			
			$.ajax({
				type: 'POST',
				url: 'api_test.php',
				dataType: 'json',
				//data: 'email='+$email.value+'&group_name='+$group_name.value,
				//data: 'email='+$email.value,
				//processData: false,
				data: {
					email: $email.value,
					feeds: $feed_names,
					name: $name.value,
					surname: $surname.value,
					org_name: $org_name.value,
					position: $position.value
				},
				success: function(msg){
					console.log('success');
					//console.log(msg);

					//console.log($.parseJSON(msg));
					//console.log(msg.trim());
					//console.log(JSON.parse(msg));
					//console.log(msg.trim());
					//console.log(msg);
					if(JSON.parse(msg).response.msg.err_code==0){
						//alert('Вы успешно подписаны на новости');
						$('#subscription-success').fadeIn(300);
					}else{
						alert('Ошибка подписки');
					}
					//console.log(JSON.parse(msg).response.msg.err_code);
				},
				error: function(msg){
					console.log('error');
					console.log(msg);
				}
			});
			
			
		});
		
		
	});
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>