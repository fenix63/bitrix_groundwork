<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	use Bitrix\Main\Config\Configuration;
?>


<?
	//Метод получает данные по названию группы (названию Адресной базы в DashaMail - https://lk.dashamail.ru/?page=lists)
	function getGroupId($data, $group_name){
		foreach($data['response']['data'] as $group){
			if($group['name']==$group_name){
				$group_id = $group['id'];
			}
		}

		return $group_id;
	}

	//метод получает список номеров групп по названиям групп.
	//$data - полученный по API список рассылок. $groupNamesArr - Список названий подписок, для которых нужно получить ID
	function getMailingIds($data, $groupNamesArr){
		$groupNumbers = Array();
		$list_id_numbers = Array();
		$correct_data = json_decode($data, true);
		foreach($groupNamesArr as $group_name){


			foreach($correct_data['response']['data'] as $response_item){

				if($response_item['name']==$group_name){
					$groupNumbers[] = $response_item['id'];
					$list_id_numbers[] = $response_item['list_id'];
				}
			}
		}

		$list_id_numbers_correct = Array();
		foreach($list_id_numbers as $list_item){
			//$list_id_numbers_correct[] = $list_item;
			//$start_pos = mb_stristr($list_item, ';i:', false);
			$start_pos = stripos($list_item,';i:');
			$start_pos+=3;
			$finish_pos = stripos($list_item,';}');
			$finish_pos+=2;
			$id = substr($list_item, $start_pos, $finish_pos - $start_pos);

			if(!in_array((int)$id, $list_id_numbers_correct))
				$list_id_numbers_correct[] = (int)$id;
		}

		return ['mail_ids'=>$groupNumbers,'list_ids'=>$list_id_numbers_correct];
	}

	//метод для добавления email'а в выбранные адресные базы
	function addEmailToAddressBases($email, $arrListIds, $api_key){
		$response = Array();
		foreach($arrListIds as $listId){
			$response[] = request('lists.add_member', $api_key, 'json', 'POST', $listId, $email);
		}

		return $response;
	}

	function request($api_method, $key, $format, $method_type, $api_group_id, $email){
		$ch = curl_init();
		$url ='https://api.dashamail.com/?method='.$api_method.'&api_key='.$key.'&format='.$format;
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if($method_type=='POST'){

			curl_setopt($ch, CURLOPT_POST, true);//Для POST-запроса
			curl_setopt($ch, CURLOPT_POSTFIELDS, "list_id=".$api_group_id."&email=".$email."&merge_1=".$_POST['name']."&merge_2=".$_POST['surname']."&merge_3=".$_POST['org_name']."&merge_4=".$_POST['position']);
		}


		$output = curl_exec($ch);
		curl_close($ch);

		return $output;
	}

	//Получаем список рассылок dashaMail, в том числе и тех, которые лежат в шаблонах
	function getMailingList($api_method, $key, $format){
		$ch = curl_init();
		$url ='https://api.dashamail.com/?method='.$api_method.'&api_key='.$key.'&format='.$format;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
?>


<?
	// echo '$_POST:';
	// echo '<pre>';
	// print_r($_POST);
	// echo '</pre>';
	$config = Configuration::getValue('dashamail');
	$key = $config['api_key'];
	//echo $key;
	
	$MailingList = getMailingList('campaigns.get', $key, 'json');


	//Определяем номера почтовых рассылок, на которые хочет подписаться пользователь
	$groups_to_subscribe = Array();
	$groups_to_subscribe = getMailingIds($MailingList, $_POST['feeds']);

	$user_is_added = addEmailToAddressBases($_POST['email'], $groups_to_subscribe['list_ids'], $key);

	echo json_encode($user_is_added);
	




	//$add_result = request($method, $key, 'json', 'POST', $group_id, $_POST['email']);
	//$data =  json_decode($AutoMailingList, true);
	//echo json_encode($AutoMailingList);
	
	//echo json_encode($data);

	/*
	$method='lists.get';

	$format = 'json';
	
	$output = request($method, $key, $format, '', '', '');

	
	$data = json_decode($output,true);//Обычный ассоциативный массив
	$group_id = getGroupId($data, 'Отдел интернет [MC]');



	//Теперь нужно добавить новый email в эту базу
	$method = 'lists.add_member';

	$add_result = request($method, $key, $format, 'POST', $group_id, $_POST['email']);


	echo $add_result;//ID добавленного пользователя
	*/
	
?>




<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>