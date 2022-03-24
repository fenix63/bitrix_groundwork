<?php

//ECHO "\n\nEnable after all testing in dev-intra. EXIT.\n";
//EXIT;

$DIR__ = __DIR__ . '/../../';
$_SERVER['DOCUMENT_ROOT'] = $DIR__;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

function saveProcessLog(string $info)
{
    $fileName = __dir__ . '/../../omni_update/data/xml/process.log';
    $r = fopen($fileName, 'w');
    fwrite($r, $info);
    fclose($r);
}

$suffix = '_omni_v2';

dbgLog('start omni update:', $suffix);

//Получаем список файлов директории
$folder = __dir__ . '/../../omni_update/data/xml/';
$destinationFolder = __dir__ . '/../../omni_update/data/completed/';

//Убираем мусор, который отдаёт функция scandir
$files_list = array_diff(scandir($folder), array('..', '.'));

$xmlFiles = glob($folder.'*.xml');

if (!count($xmlFiles)) {
    dbgLog('Не найдено файлов xml для обновления. Выход из скрипта обновления.', $suffix);
    exit;
}

// Обрабатываем только один файл за раз
$currentXmlFile = $xmlFiles[0];

dbgLog("Начало обработки файла: '$currentXmlFile'", $suffix);

if(!file_exists($currentXmlFile)) {
    dbgLog( __FILE__.':'.__LINE__.':  '.'ERROR. File not found ', $suffix);
    exit('XML File not found');
}

$xml = simplexml_load_file($currentXmlFile);

$countOfRows = count($xml->Persons);

//Получаем всех пользователей портала
$filter = [];
$rsUsers = CUser::GetList(($by="id"), ($order="desc"), $filter, ["SELECT" => ["UF_OT_UID"]]); // выбираем пользователей
$bitrix_users = Array();
$i = 0;

while($arUser = $rsUsers->Fetch()){
    $bitrix_users[$i] = $arUser;//Сохраняем в массив пользователей из Bitrix
    $bitrix_users_ids[$i] = $arUser['ID'];
    $bitrix_users_logins[$i] = $arUser['LOGIN'];
    $bitrix_users_logins_low_case[$i] = mb_strtolower($arUser['LOGIN']);

    $bitrix_users_emails[$i] = $arUser['EMAIL'];
    $bitrix_users_otuid[$i] = $arUser['UF_OT_UID'];
    $i++;
}

dbgLog('bitrix_users_logins length: ' . count($bitrix_users_logins), $suffix);
dbgLog('-------------------------------------------------------------------------------------', $suffix);

dbgLog('Users in XML:' . $countOfRows, $suffix);

//Проходимся по всем пользователям из XML
$xml_user_counter = 0;

ini_set('memory_limit', '2G');
ini_set('max_execution_time', 3600*2); // 2 часа

$updateRowsCounter = 0;
$maxUpdateRows = 7000;
$lastPercents = 0;

try{
	// По пользователям из XML
    foreach($xml->Persons as $user){

        

        if ($updateRowsCounter >= $maxUpdateRows) {
            break;
        }

        $cur_xml_user_attributes = $user->attributes();

        //Обрабатываем свойства, которые в кириллице
        foreach($cur_xml_user_attributes as $key => $value){
            //if ($updateRowsCounter >= $maxUpdateRows) {
                //break 2;
            //}
            
            $new_key = iconv('utf-8','windows-1251', $key);
            $new_value = iconv('utf-8','windows-1251', $value);

            if($new_key=='Фамилия'){
                $last_name = $new_value;
            }

            if($new_key=='Имя'){
                $name = $new_value;
            }

            if($new_key=='Отчество'){
                $second_name = $new_value;
            }
	
            if($new_key=='Телефон_внутренний'){
                $inner_phone = $new_value;
            }

            if($new_key=='Телефон_мобильный'){
                $mobile = $new_value;
            }

            if($new_key=='Подразделение'){
                $department = $new_value;
            }

            if($new_key=='Подразделение_1C_GUID'){
                $ic_guid_dep = $new_value;
            }

            if($new_key=='Должность_текстом'){
                $work_position = $new_value;
            }

            if($new_key=='Статус'){
                if($new_value=='Активный'){
                    $status = 'Y';
                }else{
                    $status = 'N';
                }
            }

			if($new_key=='Есть_доступ_на_intra_nsd_ru'){
            	if($new_value=='Да'){
            		$portal_access = 'Y';
            	}else{
            		$portal_access = 'N';
            	}
            }

            if($new_key=='_GUID_1C'){
                $xml_id = $new_value;
            }

            /*if($new_key=='Категория'){
                $category=$new_value;//Категория из XML
                if($new_value=='Аутсорсер'){
                    $outsourcer = true;
                    $moex_user = false;
                }else{
                    $outsourcer = false;

                    if($new_value=='Штатный сотрудник ГК МБ'){
                        $moex_user = true;
                    }else{
                        $moex_user = false;
                    }
                }
            }*/

            if($new_key=='Категория'){
                $category=$new_value;//Категория из XML

                switch($new_value){
                    case 'Аутсорсер':
                        $outsourcer = true;
                        $moex_user = false;
                    break;


                    default:
                        $outsourcer = false;

                        switch($new_value){
                            case 'Штатный сотрудник ГК МБ':
                                $moex_user = true;
                            break;

                            case 'Практикант':
                                $work_position = 'Практикант';
                            break;

                            default:
                                $moex_user = false;
                            break;
                        }
                        break;
                }
            }




        }//foreach по свойствам в кириллице

        /*
        dbgLog('Step: '.$xml_user_counter.' / '.$countOfRows, $suffix);
        dbgLog(
            $xml_user_counter.': Current user login from XML: '.$cur_xml_user_attributes['AD_Login'].
            ' ('.$last_name.' '.$name.' '.$second_name.'  '.$cur_xml_user_attributes['Email']
            .' '.$cur_xml_user_attributes['OT_UID'].' '.$status.' ) после обработки аттрибутов',
            $suffix
        );
        */


        //dbgLog('-----HERE2-----', $suffix);

        //dbgLog($updateRowsCounter.' '.$cur_xml_user_attributes['AD_Login'] , $suffix);

        //dbgLog('-----HERE1-----', $suffix);

        $login = $cur_xml_user_attributes['AD_Login'];
        if($login==''){
            $login = explode('@',$cur_xml_user_attributes['Email'])[0];
        }

        $email = $cur_xml_user_attributes['Email'];
        if($email ==''){
            $email = $cur_xml_user_attributes['AD_Login'].'@nsd.ru';
        }




        $inPortal = array_search(mb_strtolower($login), $bitrix_users_logins_low_case);
        $is_new_otuid = array_search(mb_strtolower($cur_xml_user_attributes['OT_UID']),$bitrix_users_otuid);


        if($outsourcer && $status=='Y' && !$inPortal && !$is_new_otuid && $portal_access=='Y'){
            dbgLog($last_name.' '.$name.' '.$second_name.' '.$status, $suffix);


			$dep_list = CIBlockSection::GetList( Array('SORT'=>'ASC'), Array('XML_ID' => $ic_guid_dep, 'ACTIVE'=>'Y', 'IBLOCK_ID'=>5), false, Array('ID','ACTIVE','XML_ID'), false);
            $dep_id = '';
			while($ar_result = $dep_list->GetNext()){
			    $dep_id = $ar_result['ID'];
			    //dbgLog("dep_id: $dep_id", $suffix);
			    //dbgLog($ar_result, $suffix);
			}

            //Создаём нового Подрядчика-пользователя
            $user = new CUser;
            $arFields = [
                //"LOGIN" => $cur_xml_user_attributes['AD_Login'],
                "LOGIN" => $login,
                //"EMAIL" => $cur_xml_user_attributes['Email'],
                "EMAIL" => $email,
                "PASSWORD" => "123456",
                "UF_OT_UID" => $cur_xml_user_attributes['OT_UID'],
                "UF_CATEGORY" => $category,
                "LAST_NAME" => $last_name,
                "NAME" => $name,
                "SECOND_NAME" => $second_name,
                "UF_PHONE_INNER" => $inner_phone,
                "PERSONAL_MOBILE" => $mobile,
				//"WORK_DEPARTMENT" => $department,
				//"WORK_DEPARTMENT" => $dep_id,
                "XML_ID" => $xml_id,
                "UF_DEPARTMENT" => array($dep_id),
                "WORK_POSITION" => $work_position,
                "EXTERNAL_AUTH_ID" => 'LDAP#1',
				//"ACTIVE" => $status
				"ACTIVE" => $portal_access
            ];

            $ID = $user->Add($arFields);
            if (intval($ID) > 0) {
                //$updateRowsCounter++;

                $arGroups = CUser::GetUserGroup($ID);
                $arGroups[] = 37;
                CUser::SetUserGroup($ID, $arGroups);

                dbgLog('user : '.$cur_xml_user_attributes['AD_Login'].' added SUCCESS_1', $suffix);
            } else {
                dbgLog('Ошибка при создании пользователя '.$cur_xml_user_attributes["AD_Login"].' ('.$cur_xml_user_attributes["Email"].'): '.$user->LAST_ERROR, $suffix);
                dbgLog( __FILE__.':'.__LINE__.':  '.'Error  create user ', $suffix);
            }
        }


        //------------------------------------------------------------------Новый блок------------------------------------------------------
        //Создаём пользователя, если его нет на портале, но он есть в XML

        //Тут надо проверить есть ли этот аутсорсер на портале. И Если есть, то создавать его повторно не надо
        //dbgLog('Ищем на портале логин '.mb_strtolower($login), $suffix);
        
        //dbgLog('Среди логинов: '."\n".print_r($bitrix_users_logins_low_case, true), $suffix);


        //Тут надо привести к нижнему регистру $cur_xml_user_attributes['AD_Login'] и сравнить с $bitrix_users_logins_low_case
        $user_in_portal = array_search(mb_strtolower($login), $bitrix_users_logins_low_case);

        $is_new_otuid = array_search(mb_strtolower($cur_xml_user_attributes['OT_UID']), $bitrix_users_otuid);
        //dbgLog('-----HERE-----', $suffix);

        //Сюда надо добавить условие !$moex_user

		$dep_list = CIBlockSection::GetList( Array('SORT'=>'ASC'), Array('XML_ID' => $ic_guid_dep, 'ACTIVE'=>'Y', 'IBLOCK_ID'=>5), false, Array('ID','ACTIVE','XML_ID'), false);

		$dep_id = '';
		while($ar_result = $dep_list->GetNext()){
		    $dep_id = $ar_result['ID'];
		    //dbgLog("dep_id: $dep_id", $suffix);
		    //dbgLog($ar_result, $suffix);
		}

        if($status=='Y' && !$user_in_portal && !$moex_user && !$is_new_otuid && $portal_access=='Y'){
            $user = new CUser;
            $arFields = [
                //"LOGIN" => $cur_xml_user_attributes['AD_Login'],
                "LOGIN" => $login,
                //"EMAIL" => $cur_xml_user_attributes['Email'],
                "EMAIL" => $email,
                "PASSWORD" => "123456",
                "UF_OT_UID" => $cur_xml_user_attributes['OT_UID'],
                "UF_CATEGORY" => $category,
                "LAST_NAME" => $last_name,
                "NAME" => $name,
                "SECOND_NAME" => $second_name,
                "UF_PHONE_INNER" => $inner_phone,
                "PERSONAL_MOBILE" => $mobile,
				//"WORK_DEPARTMENT" => $department,
				//"WORK_DEPARTMENT" => $dep_id,
                "UF_DEPARTMENT" => array($dep_id),
                "WORK_POSITION" => $work_position,
                "EXTERNAL_AUTH_ID" => 'LDAP#1',
                "XML_ID"=>$xml_id,
				//"ACTIVE" => $status
				"ACTIVE" => $portal_access
            ];

            $ID = $user->Add($arFields);
            if (intval($ID) > 0) {
                //$updateRowsCounter++;

                $arGroups = CUser::GetUserGroup($ID);
                $arGroups[] = 3;
                CUser::SetUserGroup($ID, $arGroups);
                
                dbgLog('user : '.$cur_xml_user_attributes['AD_Login'].' added SUCCESS_2', $suffix);
            } else {
                dbgLog('Ошибка при создании пользователя '.$cur_xml_user_attributes["AD_Login"].' ('.$cur_xml_user_attributes["Email"].'): '.$user->LAST_ERROR, $suffix);
                dbgLog( __FILE__.':'.__LINE__.':  '.'Error  create user ', $suffix);
            }
        }

        //------------------------------------------------------------------Новый блок------------------------------------------------------

        //Обновляем пользователей по OT_UID
        //Получаем ID пользователя, которого будем обновлять в Битриксе
        //$key = array_search($cur_xml_user_attributes['OT_UID'], $bitrix_users_otuid);

        //Проверяем ПО ЛОГИНУ
        //$key = array_search(mb_strtolower($cur_xml_user_attributes['AD_Login']), $bitrix_users_logins_low_case);

        //Проверяем по OT_UID
        $key = array_search(mb_strtolower($cur_xml_user_attributes['OT_UID']), $bitrix_users_otuid);

        #dbgLog('key перед обновлением: '.$key, $suffix);
        $percents = round( ($xml_user_counter / $countOfRows) * 100 );
        if (($lastPercents + 2) < $percents || (int)$percents === 100) {
            saveProcessLog("$percents %");
            $lastPercents = $percents;
        }

        //dbgLog('Логин в нижнем регистре: '.mb_strtolower($cur_xml_user_attributes['AD_Login']).' Юзеры в нижнем регистре:'."\n".print_r($bitrix_users_logins_low_case, true), $suffix);
        //dbgLog('OT_UID из XML: '.$cur_xml_user_attributes['OT_UID'].' Массив OT_UID с портала:'."\n".print_r($bitrix_users_otuid, true), $suffix);
        //$department
        dbgLog('Перед обновлением: OT_UID:'.$cur_xml_user_attributes['OT_UID'].'  key='.$key.'  moex_user='.print_r($moex_user, true), $suffix);


        if($key!==false && !$moex_user){
            $user_id = $bitrix_users_ids[$key];

            $login = $cur_xml_user_attributes['AD_Login'];
            if($login==''){
                $login = explode('@',$cur_xml_user_attributes['Email'])[0];
            }

            #dbgLog('Login перед обновлением: '.$login, $suffix);

            $email = $cur_xml_user_attributes['Email'];
            if($email ==''){
                $email = $cur_xml_user_attributes['AD_Login'].'@nsd.ru';
            }


            dbgLog('Обновляем пользователя '.$last_name, $suffix);

            //Надо как то из названия подразделения получить его ID

            //$dep_list = CIntranetUtils::GetDeparmentsTree();
            //dbgLog('$dep_list='.print_r($dep_list, true), $suffix);

            //$ic_guid_dep
            //$department

            //$dep_list = CIBlockSection::GetList( Array('SORT'=>'ASC'), Array('UF_DEPARTMENT' => '%'.$department.'%', 'ACTIVE'=>'Y', 'IBLOCK_ID'=>5), false, Array('ID','ACTIVE'), false);

            //Получаем уникальный номер подразделения используя  XML_ID этого подразделения
			//$dep_list = CIBlockSection::GetList( Array('SORT'=>'ASC'), Array('UF_DEP_1C_ID' => $ic_guid_dep, 'ACTIVE'=>'Y', 'IBLOCK_ID'=>5), false, Array('ID','ACTIVE','UF_DEP_1C_ID'), false);
			$dep_list = CIBlockSection::GetList( Array('SORT'=>'ASC'), Array('XML_ID' => $ic_guid_dep, 'ACTIVE'=>'Y', 'IBLOCK_ID'=>5), false, Array('ID','ACTIVE','UF_DEP_1C_ID'), false);

            //Получаем уникальный номер подразделения используя название подразделения
            //$dep_list = CIBlockSection::GetList(Array('SORT'=>'ASC'), Array('NAME' => '%'.$department.'%', 'ACTIVE'=>'Y'), false, Array('ID','ACTIVE'), false);
        //$dep_list = CIBlockSection::GetList( Array('SORT'=>'ASC'), Array('UF_IC_ID' => '%'.$ic_guid_dep.'%', 'ACTIVE'=>'Y', 'IBLOCK_ID'=>5), false, Array('ID','ACTIVE'), false);
			if($ic_guid_dep==''){
                $dep_id = null;
            }else{
                $dep_id = '';
                while($ar_result = $dep_list->GetNext()){
                    $dep_id = $ar_result['ID'];
                    //dbgLog("dep_id: $dep_id", $suffix);
                    //dbgLog($ar_result, $suffix);
                }
            }

            

            dbgLog('$ic_guid_dep='.$ic_guid_dep, $suffix);
            dbgLog('(after while) $dep_id='.print_r($dep_id, true), $suffix);

            //Обновляем пользователя
            $user = new CUser;
            $fields = [ 
                "UF_OT_UID" => $cur_xml_user_attributes['OT_UID'],
                "UF_CATEGORY" => $category, 
                "LAST_NAME" => $last_name,
                "NAME" => $name,
                "SECOND_NAME" => $second_name,
                "LOGIN" => $login,
                "EMAIL" => $email,
                "UF_PHONE_INNER" => $inner_phone,
                "PERSONAL_MOBILE" => $mobile,
                "EXTERNAL_AUTH_ID" => 'LDAP#1',
                //"XML_ID" => $cur_xml_user_attributes['_GUID_1C'],
                //"WORK_DEPARTMENT" => $department,
                //"WORK_DEPARTMENT" => (int)$dep_id,
                "UF_DEPARTMENT" => array($dep_id),
                "XML_ID" => $xml_id,
                "WORK_POSITION" => $work_position,
				//"ACTIVE" => $status
				"ACTIVE" => $portal_access
            ];
            $user->Update($user_id, $fields);
            //$updateRowsCounter++;
            dbgLog('UPDATE SQL:'."\n".print_r($fields, true), $suffix);
            dbgLog('Ошибки: '.$user->LAST_ERROR, $suffix);
            dbgLog( __FILE__.':'.__LINE__.':  '.'User updade SUCCESS ', $suffix);
        }


        $updateRowsCounter++;
        $xml_user_counter++;
    }//foreach по пользователям из XML

    // Перемещение обработанного файла с другую папку
    $newLocationForCurrentXmlFile = $destinationFolder . basename( $currentXmlFile );

    dbgLog("Перемещение файла '$currentXmlFile' в '$newLocationForCurrentXmlFile'", $suffix);

    rename($currentXmlFile, $newLocationForCurrentXmlFile);
    saveProcessLog("$percents %, " . basename($currentXmlFile));
    
    dbgLog("Архивация файла '$newLocationForCurrentXmlFile'", $suffix);
    $zip = new ZipArchive;
    $filename = "$newLocationForCurrentXmlFile.zip";

    if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
        dbgLog("Невозможно открыть <$filename>\n", $suffix);
        exit;
    }

    if (!$zip->addFile($newLocationForCurrentXmlFile, basename($currentXmlFile))) {
        dbgLog("Ошибка добавления файла '$newLocationForCurrentXmlFile' в архив", $suffix);
        exit;
    }

    dbgLog("Добавлено в архив файлов: " . $zip->numFiles, $suffix);
    dbgLog("Статус Zip-архива (0 - норм): " . $zip->status . "\n", $suffix);

    $zip->close();

    dbgLog("Удаление файла '$newLocationForCurrentXmlFile'", $suffix);
    unlink($newLocationForCurrentXmlFile);

} catch(Exception $e) {
	#echo "\n<pre>\n"; var_dump($e); echo "\n\n";
	dbgLog('Exception:', $suffix); dbgLog($e, $suffix);	
}

dbgLog('finish omni update:', $suffix);
