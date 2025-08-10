<?php

namespace Vdgb\Core\Events;
use Vdgb\Core\Debug;
use Vdgb\Core\Helpers\Deal\DealHelper;
use Vdgb\Core\Helpers\User\UserHelper;
use Vdgb\Core\Helpers\Advanta\AdvantaHelper;
use Vdgb\Core\Helpers\Company\CompanyHelper;
use Vdgb\Core\Helpers\Curl;
use Vdgb\Core\Helpers\XMLHelper;
use Vdgb\Core\Helpers\HlblockHelper;
use Vdgb\Core\Helpers\Databus\Settings as DatabusSettings;
use Vdgb\Core\Helpers\Databus\RequestSender;


class CrmEvents
{

    public static function onAfterCrmDealAddHandler(&$arFields)
    {

        //Debug::dbgLog($arFields,'_onAfterCrmDealAddHandler_');
        $useDatabus = DatabusSettings::dataBusIsUsing();
        Debug::dbgLog($useDatabus,'_useDatabus_');


        if($arFields['STAGE_ID']=='NEW')
            return true;


        if(!$useDatabus)
            $advantaCredentials = AdvantaHelper::getAdvantaCredentials();
        else{
            $databusUrl = DatabusSettings::getDataBusServerURL();
            $databusSettings = DatabusSettings::getSettings("b24.presale.created",["ID","UF_API_KEY","UF_TOPIC_NAME"]);
            //Debug::dbgLog($databusSettings,'_databusSettings_');
        }


        $dealId = $arFields['ID'];
        //Создание пресейла - тут нужно получить поля:
        //Название сделки, Руководитель проекта, Предварительная стоимость проекта, Компания, стадия сделки
        $presaleInfo['name'] = $arFields['TITLE'];
        $presaleInfo['dealSum'] = $arFields['OPPORTUNITY'];
        //--Авторизация и получение ID сессии-------------------
        //Debug::dbgLog('test1','_test1_');

        
            


        if(!$useDatabus){
            $authXml = AdvantaHelper::buildXmlToRequest('Authenticate', '',
                [
                    'login' => $advantaCredentials['login'],
                    'password' => $advantaCredentials['password']
                ]
            );
            $curlResponse = Curl::sendRequest(
                $advantaCredentials['advantaServerURL'].'/components/services/login.asmx',
                ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/Authenticate'],
                //AdvantaHelper::advantaHeaders['AuthenticateHeader']
                'Authenticate',
                $authXml
            );

            $requestSoap = XMLHelper::getRequestSoap($curlResponse['response']);
            $json = json_encode($requestSoap);
            $requestData = json_decode($json, true);
            $xmlObj = new XMLHelper();
            $sessId = $xmlObj->getNodeFromXmlArray($requestData, 'ASPNETSessionId');

        }

        

        //Debug::dbgLog('test2','_test2_');
        
        //-----Конец Авторизация и получение ID сессии
        //Debug::dbgLog($sessId,'_sessId_');

        if(!$useDatabus){
            //-------Получение информации по пользователям
            $allUsersInfoXML = AdvantaHelper::buildXmlToRequest('GetPersons', $sessId, []);
            $curlUsersInfoResponse = Curl::sendRequest(
              //AdvantaHelper::ADVANTA_GET_PERSONS_URL,
              $advantaCredentials['advantaServerURL'].'/components/services/persons.asmx',
              ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons'],
              'GetPersons',
              $allUsersInfoXML
            );
        }
        else{
            //Отправка запроса в шину
            //Debug::dbgLog('test3','_test3_');

            try{
                $allUsersInfoXML = RequestSender::buildXMLToRequest('GetPersons',[]);
            }catch(\Exception $e){
                Debug::dbgLog($e->getMessage(),'_buildXMLError_');
            }


            //Debug::dbgLog('test3','_test34_');
            //Debug::dbgLog('test4','_test4_');
            $json = RequestSender::buildJSONToRequest($allUsersInfoXML);
            //Debug::dbgLog('test3','_test35_');
            
            $curlUsersInfoResponse = Curl::sendJSONDataBusRequest(
                $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                $json,
                $databusSettings['UF_API_KEY']
            );
            //Debug::dbgLog('test3','_test36_');

        }



        if(!$useDatabus){
            $requestUsersSoap = XMLHelper::getRequestSoap($curlUsersInfoResponse['response']);
            $allUsersJson = json_encode($requestUsersSoap);
            $allUsersData = json_decode($allUsersJson, true);

            $users = $xmlObj->getNodeFromXmlArray($allUsersData, 'SlPerson');
            $managerUserFieldCode = UserHelper::getUFCodeByXmlId('PROJECT_MANAGER');
            $managerId = $arFields[$managerUserFieldCode];
        }else{
            //TODO: Тут будет получение данных по пользователям от шины и получение ID руководителя

            //return true;
        }
        

        
        if(!empty($managerId)){
            $managerInfo = UserHelper::getUserInfo($managerId,['ID','EMAIL']);
            $managerEmail = $managerInfo['EMAIL'];
            $managerExternalId = AdvantaHelper::getUserUIDByEmail($managerEmail, $users);
            $presaleInfo['managerUID'] = $managerExternalId;
        }
        
        
        //-------------Конец Получение информации по пользователям
        
        //---Классификаторы--------
        $dealStatusCode = $arFields['STAGE_ID'];
        $deadStatusTitle = DealHelper::getDealNameByCode($dealStatusCode);

        

        switch($deadStatusTitle){
            case 'В мониторинге':
                $presaleInfo['dealStatusAdvantaCode'] = 8;
                break;
            case 'Сделка провалена':
                $presaleInfo['dealStatusAdvantaCode'] = 7;
                break;
            case 'Договор подписан':
                $presaleInfo['dealStatusAdvantaCode'] = 6;
                break;

            default:
                $presaleInfo['dealStatusAdvantaCode'] = 3;
                break;
        }
        
        //Debug::dbgLog('test4_3','_test4_3_');
        //Debug::dbgLog($deadStatusTitle,'_dealStatusTitle_');
        

        

        if(!$useDatabus){
            //8f239169-9148-4128-84f9-83851c196c53 - стадии жизненного цикла пресейла
            $classificatorAllRecordsXml = AdvantaHelper::buildXmlToRequest(
                'GetClassifierRecords',
                $sessId,
                ['classificatorUID'=>'8f239169-9148-4128-84f9-83851c196c53']
            );

            $classificatorAllRecordsResult = Curl::sendRequest(
                $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
                ['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
                'GetClassifierRecords',
                $classificatorAllRecordsXml
            );
        }else{
            //Debug::dbgLog('test5','_test5_');
            //8f239169-9148-4128-84f9-83851c196c53 - стадии жизненного цикла пресейла
            $classificatorAllRecordsXml = RequestSender::buildXMLToRequest('GetClassifierRecords',
                ['classificatorUID'=>'8f239169-9148-4128-84f9-83851c196c53']);
            $json = RequestSender::buildJSONToRequest($classificatorAllRecordsXml);

            //Debug::dbgLog('test0','_test0_');
            $classificatorAllRecordsResult = Curl::sendJSONDataBusRequest(
                $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                $json,
                $databusSettings['UF_API_KEY']
            );
            //Debug::dbgLog('test0','_test01_');

        }

        
        if(!$useDatabus){
            $classificatorRequestSoap = XMLHelper::getRequestSoap($classificatorAllRecordsResult['response']);
            $classificatorJson = json_encode($classificatorRequestSoap);
            $classificatorRequestData = json_decode($classificatorJson, true);

            
            $classificatorXmlObj = new XMLHelper();
            $classificatorRecords = $xmlObj->getNodeFromXmlArray($classificatorRequestData, 'RecordWrapper');
            $classificatorUID = AdvantaHelper::getClassificatorUidByTitle($deadStatusTitle, $classificatorRecords);
        }else{
            //TODO: тут получение UID стадии жизненного цикла пресейла от шины
            //return true;
            //Debug::dbgLog('test0','_test0_');
        }


        
        //---Конец Классификаторы

        $presaleInfo['dealStageUID'] = $classificatorUID;
        $parentProjectUFCode = UserHelper::getUFCodeByXmlId('ADVANTA_SECTION');
        

        if(!empty($arFields[$parentProjectUFCode])){
            $parentProjectUFXml_Id = UserHelper::getEnumXmlIdById($arFields[$parentProjectUFCode]);
            //Debug::dbgLog($parentProjectUFXml_Id,'_parentProjectUFXml_Id_');
            $presaleInfo['parentProjectUID'] = $parentProjectUFXml_Id;
        }

        
        
        $companyUFCode = UserHelper::getUFCodeByXmlId('COMPANY');
        $companyId = $arFields['COMPANY_ID'];
        $companyInfo = CompanyHelper::getCompanyRequisites($companyId);
        $companyInn = $companyInfo[0]['RQ_INN'];
        $presaleInfo['companyINN'] = $companyInn;


        //Принадлежность к портфелю проектов
        if(!$useDatabus){
            $portfolioAllListXml = AdvantaHelper::buildXmlToRequest(
                'GetClassifierRecords',
                $sessId,
                ['classificatorUID'=>'d5d01090-73e3-4d26-878f-00e8a45b9774']
            );
            $portfolioAllListRecords = Curl::sendRequest(
                $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
                ['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
                'GetClassifierRecords',
                $portfolioAllListXml
            );
        }else{
            $portfolioAllListXml = RequestSender::buildXmlToRequest(
                'GetClassifierRecords',
                ['classificatorUID'=>'d5d01090-73e3-4d26-878f-00e8a45b9774']
            );
            $json = RequestSender::buildJSONToRequest($portfolioAllListXml);
            $portfolioAllListRecords = Curl::sendJSONDataBusRequest(
                $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                $json,
                $databusSettings['UF_API_KEY']
            );
            Debug::dbgLog('abcd','_abcd_');
            //TODO получение списка от шины. Тут нужно получить аналог $portfolioAllListRecords['response'] прямого запроса

            //return true;
        }
        

        if(!$useDatabus)
            $portfolioAllListRecordsItems = self::getXMLNode($portfolioAllListRecords['response'], 'RecordWrapper');
        else{
            //TODO: сформировать $portfolioAllListRecordsItems от шины
        }

        
        //Debug::dbgLog($portfolioAllListRecordsItems,'_portfolioAllListRecordsItems_');

        //Принадлежность к портфелю проектов
        if(!empty($arFields[$parentProjectUFCode])){
            $parentProjectUFTextValue = UserHelper::getEnumTextValueById($arFields[$parentProjectUFCode]);
            /*$portfolioItemUID = AdvantaHelper::getClassificatorUidByTitle($parentProjectUFTextValue, $portfolioAllListRecordsItems);*/
            //$presaleInfo['portfolioItemUID'] = $portfolioItemUID;
        }else{
            $parentProjectUFTextValue = 'Прочее';
            /*$portfolioItemUID = AdvantaHelper::getClassificatorUidByTitle($parentProjectUFTextValue, $portfolioAllListRecordsItems);*/
            //$presaleInfo['portfolioItemUID'] = $portfolioItemUID;
        }
        

        if(!$useDatabus)
            $portfolioItemUID = AdvantaHelper::getClassificatorUidByTitle($parentProjectUFTextValue, $portfolioAllListRecordsItems);
        else{
            //TODO Получить $portfolioItemUID от шины
        }
        $presaleInfo['portfolioItemUID'] = $portfolioItemUID;
        //---Конец Принадлежность к портфелю проектов
        
        

        //Debug::dbgLog($portfolioItemUID,'_portfolioItemUID_');
        $presaleInfo['dealId'] = $dealId;
        //Конец Принадлежность к портфелю проектов
        
        //Debug::dbgLog($presaleInfo,'_presaleInfo_');
        //------------------Создание пресейла
        try{
            if(!$useDatabus)
                $presaleXML = AdvantaHelper::buildXmlToRequest('CreateProject', $sessId, $presaleInfo);
            else{
                $presaleXML = RequestSender::buildXmlToRequest('CreateProject', $presaleInfo);
                $json = RequestSender::buildJSONToRequest($presaleXML);
            }
            
        }catch(Exception $e){
            Debug::dbgLog($e->getMessage(),'_CreatePresaleException_');
        }


        
        //Debug::dbgLog('test1','_test1_');
        $presaleXML = trim($presaleXML,"\"");

        //Debug::dbgLog($presaleXML,'_presaleXML_');
        if(!$useDatabus)
            $createPresaleResult = Curl::sendRequest(
              $advantaCredentials['advantaServerURL'].'/components/services/APIProjects.asmx',
              ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/CreateProject'],
              'CreateProject',
              $presaleXML
            );
        else{
            //TODO: Получить от шины результат создания пресейла
            $createPresaleResult = Curl::sendJSONDataBusRequest(
                $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                $json,
                $databusSettings['UF_API_KEY']
            );
            Debug::dbgLog($createPresaleResult,'_createPresaleResult_');
        }

        //Debug::dbgLog('test1','_test1_');
        //Debug::dbgLog($createPresaleResult,'_createPresaleResult_');



        if($createPresaleResult['info']['http_code']==200){
            $presaleResponseXml = $createPresaleResult['response'];
            $presaleResponseXmlCleared = XMLHelper::getRequestSoap($presaleResponseXml);
            $responseXmlJson = json_encode($presaleResponseXmlCleared);
            $responseXmlData = json_decode($responseXmlJson, true);
        
            Debug::dbgLog('test1 '.__FILE__.': '.__LINE__,'_test0_');
            Debug::dbgLog($responseXmlData,'_responseXmlData_');
            if(!$useDatabus)
                $presaleUID = $xmlObj->getNodeFromXmlArray($responseXmlData, 'CreateProjectResult');
            else{
                //TODO Написать обработку ответа от шины
            }
            Debug::dbgLog('test1 '.__FILE__.': '.__LINE__,'_test01_');

            //Нужно записать полученный UID пресейла в сделку в пользовательское поле UF_CRM_1742119252832 (XML_ID=PRESALE_UID)
            $presaleUIDUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
            Debug::dbgLog('test1 '.__FILE__.': '.__LINE__,'_test1_');
            Debug::dbgLog($dealId,'_dealId_');
            Debug::dbgLog($presaleUIDUFCode,'_presaleUIDUFCode_');
            Debug::dbgLog($presaleUID,'_presaleUID_');

            //return true;
            try{
                if(empty($presaleUID))
                    $presaleUID = '';

                DealHelper::updateDealField($dealId,$presaleUIDUFCode,$presaleUID);
            }catch(\Exception $e){
                Debug::dbgLog($e->getMessage(),'_test12_');                
            }


            //return true;

            //Классификаторы
            if(!$useDatabus){
                $classificatorAllRecordsXml = AdvantaHelper::buildXmlToRequest(
                    'GetClassifierRecords',
                    $sessId,
                    ['classificatorUID'=>'8f239169-9148-4128-84f9-83851c196c53']
                );
                $classificatorAllRecordsResult = Curl::sendRequest(
                    $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
                    ['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
                    'GetClassifierRecords',
                    $classificatorAllRecordsXml
                );

                $classificatorRequestSoap = XMLHelper::getRequestSoap($classificatorAllRecordsResult['response']);
                $classificatorJson = json_encode($classificatorRequestSoap);
                $classificatorRequestData = json_decode($classificatorJson, true);


                $classificatorXmlObj = new XMLHelper();
                $classificatorRecords = $xmlObj->getNodeFromXmlArray($classificatorRequestData, 'RecordWrapper');
                $classificatorUID = AdvantaHelper::getClassificatorUidByTitle($deadStatusTitle, $classificatorRecords);
            }else{
                $classificatorAllRecordsXml = RequestSender::buildXmlToRequest(
                    'GetClassifierRecords',
                    ['classificatorUID'=>'d5d01090-73e3-4d26-878f-00e8a45b9774']
                );
                $json = RequestSender::buildJSONToRequest($classificatorAllRecordsXml);
                $classificatorAllRecordsResult = Curl::sendJSONDataBusRequest(
                    $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                    $json,
                    $databusSettings['UF_API_KEY']
                );

                //TODO: обработать полученный от шины результат - нужен полный список всех классификаторов, то есть аналог //$classificatorRecords при прямом запросе. Далее нужно по названию стадии жизненного цикла в битриксе получить его //UID из адванты
            }

            

            //Обновление стадии жизненного цикла пресейла
            if(!$useDatabus){
                $stageXml = AdvantaHelper::buildXmlToRequest(
                    'UpdateProjectFields',
                    $sessId,
                    ['stageUID' => $classificatorUID,'presaleUID' => $presaleUID]
                );
                $setStageResult = Curl::sendRequest(
                    $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx?=',
                    ['Content-Type: text/xml;charset=utf-8','SOAPAction: http://streamline/UpdateProjectFields'],
                    'UpdateProjectFields',
                    $stageXml
                );
            }else{
                $stageXml = RequestSender::buildXMLToRequest('UpdateProjectFields',
                    ['stageUID' => $classificatorUID,'presaleUID' => $presaleUID]
                );
                $stageJSON = RequestSender::buildJSONToRequest($stageXml);
                $setStageResult = Curl::sendJSONDataBusRequest(
                    $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                    $stageJSON,
                    $databusSettings['UF_API_KEY']
                );
            }
            //---------------Конец Обновление стадии жизненного цикла пресейла
            Debug::dbgLog('finish','_finish_');  
        }
        //--------------конец Создание пресейла

    }

    public static function getXMLNode(string $curlXmlResult, string $targetNodeName)
    {
        $requestSoap = XMLHelper::getRequestSoap($curlXmlResult);
        //Debug::dbgLog($requestSoap,'_requestSoap_');
        $requestJson = json_encode($requestSoap);
        $requestData = json_decode($requestJson, true);
        //Debug::dbgLog($requestData,'_requestData_');

        $xmlObj = new XMLHelper();
        $entityRecords = $xmlObj->getNodeFromXmlArray($requestData, $targetNodeName);
        
        return $entityRecords;
    }

    //После обновления сделки
    public static function onAfterCrmDealUpdateHandler(&$arFields)
    {
        Debug::dbgLog('onAfterCrmDealUpdateHandler method','_onAfterCrmDealUpdateHandler_');
        //Debug::dbgLog($arFields,'_onAfterCrmDealUpdateHandler_fields_');

        //if($arFields['STAGE_ID'])

        $useDatabus = DatabusSettings::dataBusIsUsing();

        if(!$useDatabus){
            $advantaCredentials = AdvantaHelper::getAdvantaCredentials();
        }else{
            $databusUrl = DatabusSettings::getDataBusServerURL();
            $databusSettings = DatabusSettings::getSettings("b24.presale.updated",["ID","UF_API_KEY","UF_TOPIC_NAME"]);
        }

		

		$presaleInfo['TITLE'] = $arFields['TITLE'];
		$presaleInfo['price'] = $arFields['OPPORTUNITY'];

		$dealId = $arFields['ID'];
		$presaleUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
		$dealInfo = DealHelper::getDealInfoShort($dealId, [$presaleUFCode,'STAGE_ID']);

		//Debug::dbgLog($dealInfo,'_dealInfo_');

		$presaleUID = $dealInfo[0][$presaleUFCode];
        if(empty($presaleUID))
            return true;

		$presaleInfo['presaleUID'] = $presaleUID;
		//Debug::dbgLog($presaleUID,'_presaleUID_');

		$dealStatusCode = $dealInfo[0]['STAGE_ID'];
        //Debug::dbgLog($dealStatusCode,'_dealStatusCode_');


		if(!empty($dealStatusCode))
			$deadStatusTitle = DealHelper::getDealNameByCode($dealStatusCode);

        if(empty($deadStatusTitle))
            return true;

        //Debug::dbgLog($deadStatusTitle,'_deadStatusTitle_');

		$presaleInfo['stageTitle'] = $deadStatusTitle;
        //Debug::dbgLog($presaleInfo['stageTitle'],'_stageTitle_');

        switch($presaleInfo['stageTitle']){
            case 'В мониторинге':
                $presaleInfo['dealStatusAdvantaCode'] = 8;
                break;
            case 'Сделка провалена':
                $presaleInfo['dealStatusAdvantaCode'] = 7;
                break;
            case 'Договор подписан':
                $presaleInfo['dealStatusAdvantaCode'] = 6;
                break;

            default:
                $presaleInfo['dealStatusAdvantaCode'] = 3;
                break;
        }

		//Debug::dbgLog($presaleInfo,'_presaleInfo_');

		$advantaHelperObj = new AdvantaHelper();
        

        if(!$useDatabus){
            $sessId = $advantaHelperObj->getSessionId();

            //Обновляем поля в пресейле
            $updatePresaleXml = AdvantaHelper::buildXmlToRequest('UpdateProject', $sessId, $presaleInfo);
            Debug::dbgLog($updatePresaleXml,'_updatePresaleXml_');

            $curlUpdatePresaleXmlResponse = Curl::sendRequest(
                $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
                ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProject'],
                'UpdateProject',
                $updatePresaleXml
            );
        }else{
            $updatePresaleXml = RequestSender::buildXMLToRequest('UpdateProject',$presaleInfo);
            $updatePresaleJSON = RequestSender::buildJSONToRequest($updatePresaleXml);
            $curlUpdatePresaleXmlResponse = Curl::sendJSONDataBusRequest(
                $databusUrl.'api/v1/send/'.$databusSettings['UF_TOPIC_NAME'].'/',
                $updatePresaleJSON,
                $databusSettings['UF_API_KEY']
            );
            Debug::dbgLog('test1','_test1_');
            return true;
        }

        
		

        //Debug::dbgLog('test3','_test3_');
		Debug::dbgLog($curlUpdatePresaleXmlResponse,'_curlUpdatePresaleXmlResponse_');

		//Классификаторы - получение стадий жизненного цикла
		$dealStatusCode = $arFields['STAGE_ID'];
		//Debug::dbgLog($dealStatusCode,'_dealStatusCode_');

		if(!empty($dealStatusCode))
			$presaleInfo['stageUID'] = DealHelper::getDealStatusUID($dealStatusCode, $sessId);
		//Debug::dbgLog($presaleInfo,'_presaleInfo_');
        //Debug::dbgLog('test0','_test0_');

		//Раздел пресейла
		$parentProjectUFCode = UserHelper::getUFCodeByXmlId('ADVANTA_SECTION');
		//Debug::dbgLog($parentProjectUFCode,'_parentProjectUFCode_');
		$parentProjectEnumValue = $arFields[$parentProjectUFCode];
        //Debug::dbgLog('test4','_test4_');
		//Debug::dbgLog($parentProjectEnumValue,'_parentProjectEnumValue_');
        //Debug::dbgLog('test01','_test01_');

		if(!empty($parentProjectEnumValue)) {
			$parentProjectEnumTextValue = UserHelper::getEnumTextValueById($parentProjectEnumValue);
			//Debug::dbgLog($parentProjectEnumTextValue,'_parentProjectEnumTextValue_');
		}



		//Принадлежность к портфелю проектов
		$portfolioAllListXml = AdvantaHelper::buildXmlToRequest(
			'GetClassifierRecords',
			$sessId,
			['classificatorUID'=>'d5d01090-73e3-4d26-878f-00e8a45b9774']
		);
		$portfolioAllListRecords = Curl::sendRequest(
			$advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
			['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
			'GetClassifierRecords',
			$portfolioAllListXml
		);

        //Debug::dbgLog('test1','_test1_');

		//Debug::dbgLog($portfolioAllListRecords,'_portfolioAllListRecords_');
		$portfolioAllListRecordsItems = self::getXMLNode($portfolioAllListRecords['response'], 'RecordWrapper');

        //Debug::dbgLog('test5','_test5_');

		if(!empty($parentProjectEnumTextValue)) {
			$portfolioItemUID = AdvantaHelper::getClassificatorUidByTitle($parentProjectEnumTextValue, $portfolioAllListRecordsItems);
			//Debug::dbgLog($portfolioItemUID, '_portfolioItemUID_');

			$presaleInfo['projectPortfolioUID'] = $portfolioItemUID;

			//Перемещение проекта
			$advantaSectionCode = UserHelper::getUFCodeByXmlId('ADVANTA_SECTION');
			$enumSectionValueId = $arFields[$advantaSectionCode];
			$enumSectionXmlId = UserHelper::getEnumXmlIdById($enumSectionValueId);

			$presaleInfo['newParentProjectId'] = $enumSectionXmlId;


			$changeParentSectionXml = AdvantaHelper::buildXmlToRequest('ChangeParent', $sessId, $presaleInfo);
			//Debug::dbgLog($changeParentSectionXml,'_changeParentSectionXml_');
			$changeParentSectionResponse = Curl::sendRequest(
				$advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
				['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/ChangeParent'],
				'ChangeParent',
				$changeParentSectionXml
			);
			//Debug::dbgLog($changeParentSectionResponse,'_changeParentSectionResponse_');
			//Конец Перемещение проекта
		}


        //Debug::dbgLog('test2','_test2_');



		//Получение ИНН
		$companyId = $arFields['COMPANY_ID'];
		if(!empty($companyId)) {
			$companyInfo = CompanyHelper::getCompanyRequisites($companyId);
			$companyInn = $companyInfo[0]['RQ_INN'];
			$presaleInfo['companyINN'] = $companyInn;
		}
		//Конец Получение ИНН


		//Конец Раздел пресейла

		$updateProjectFieldsXml = AdvantaHelper::buildXmlToRequest(
			'UpdateProjectFields',
			$sessId,
			$presaleInfo
		);
		$projectRequisitesUpdateResult = Curl::sendRequest(
			$advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
			['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProjectFields'],
			'UpdateProjectFields',
			$updateProjectFieldsXml
		);

		//Debug::dbgLog($updateProjectFieldsXml,'_updateProjectFieldsXml_');
		//Debug::dbgLog($projectRequisitesUpdateResult,'_projectRequisitesUpdateResult_');


		//Ответственный РП
		$managerUserFieldCode = UserHelper::getUFCodeByXmlId('PROJECT_MANAGER');

		$managerId = $arFields[$managerUserFieldCode];
        if(empty($managerId)){
            $managerId = UserHelper::getUserInfoByFilter(['EMAIL'=>'D.Chistyakov@vdgb.ru']);
        }


        Debug::dbgLog($managerId,'_managerId_');

		if(!empty($managerId)) {
			$advantaManagerUID = AdvantaHelper::getUserAdvantaUIDByUserId($managerId, $sessId);
			$presaleInfo['managerUID'] = $advantaManagerUID;
            //Debug::dbgLog($presaleInfo['managerUID'],'_responsibleUserUID_');

            $delegateProjectXml = AdvantaHelper::buildXmlToRequest('DelegateProject', $sessId, $presaleInfo);

            //Debug::dbgLog($delegateProjectXml,'_delegateProjectXml_');
            $delegateProjectResponse = Curl::sendRequest(
                $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
                ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/DelegateProject'],
                'DelegateProject',
                $delegateProjectXml
            );



            //Debug::dbgLog($changeParentSectionXml,'_changeParentSectionXml_');
            //Debug::dbgLog($delegateProjectResponse,'_delegateProjectResponse_');
		}

		
    }

    //При добавлении дела к сущности
    public static function onActivityAddHandler($activityFields, $activityBindings)
    {
        return true;
        Debug::dbgLog('onActivityAddHandler','_onActivityAddHandler_');
        //Debug::dbgLog($activityFields,'_activityFields_');
        //Debug::dbgLog($activityBindings,'_activityBindings_');
        //Debug::dbgLog($newLightTimeDate,'_newLightTimeDate_');
        $advantaCredentials = AdvantaHelper::getAdvantaCredentials();
        $taskId = $activityBindings['ASSOCIATED_ENTITY_ID'];
        $dealId = $activityBindings['OWNER_ID'];


        $presaleUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
        $dealInfo = DealHelper::getDealInfoShort($dealId, [$presaleUFCode]);
        //Debug::dbgLog($dealInfo,'_dealInfo_');

        $presaleUID = $dealInfo[0][$presaleUFCode];

        $taskInfo = DealHelper::getTaskInfoById($taskId);
        Debug::dbgLog($taskInfo,'_taskInfo_');
        if(!empty($taskInfo['DEADLINE'])){

            Debug::dbgLog('test dates','_Deadline_change_');

            $presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime();
            $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
            $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);

            Debug::dbgLog($presaleInfo['startDate'],'_startDate_');

            $presaleInfo['finishDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['DEADLINE']);
            $presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d H:i:s");
            $presaleInfo['finishDate'] = str_replace(" ","T", $presaleInfo['finishDate']);

            Debug::dbgLog($presaleInfo['finishDate'],'_finishDate_');
        }else{
            Debug::dbgLog('test dates','_Start_finish_date_change_');

            $presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['START_DATE_PLAN']);
            $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
            $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);

            $presaleInfo['finishDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['END_DATE_PLAN']);
            $presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d H:i:s");
            $presaleInfo['finishDate'] = str_replace(" ","T", $presaleInfo['finishDate']);
        }

        

        

        //Debug::dbgLog($presaleInfo,'_presaleInfo_');

        $advantaHelperObj = new AdvantaHelper();
        $sessId = $advantaHelperObj->getSessionId();

        
        $executorId = $taskInfo['RESPONSIBLE_ID'];
        $executorInfo = UserHelper::getUserInfo($executorId,['ID','EMAIL']);
        $salesDepartmentUser = UserHelper::getUserInfo($executorId,['NAME','LAST_NAME','SECOND_NAME']);
        //Debug::dbgLog($userFIO,'_userFIO_');

        $executorInfo['roleb24'] = 'Исполнитель';
        $executorEmail = $executorInfo['EMAIL'];

        

        $allUsersInfoXML = AdvantaHelper::buildXmlToRequest('GetPersons', $sessId, []);
        $curlUsersInfoResponse = Curl::sendRequest(
          AdvantaHelper::ADVANTA_GET_PERSONS_URL,
          ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons'],
          'GetPersons',
          $allUsersInfoXML
        );
        //Debug::dbgLog($curlUsersInfoResponse,'_curlUsersInfoResponse_');


        $requestUsersSoap = XMLHelper::getRequestSoap($curlUsersInfoResponse['response']);
        $allUsersJson = json_encode($requestUsersSoap);
        $allUsersData = json_decode($allUsersJson, true);
        //Debug::dbgLog($allUsersData,'_allUsersData_');


        $xmlObj = new XMLHelper();
        $users = $xmlObj->getNodeFromXmlArray($allUsersData, 'SlPerson');
        //Debug::dbgLog($users,'_users_');
        

        $executorInfo = AdvantaHelper::getUserInfoByEmail($executorEmail, $users);

        

        $presaleInfo['executorInfo'] = $executorInfo;
        $executorInfo['roleb24'] = 'Исполнитель';
        

        //соисполнители
        $coExecutorIdList = $taskInfo['ACCOMPLICES']->toArray();
        $coExecutorsExternalIdList = [];
        foreach($coExecutorIdList as $coExecutorId){
          $info = UserHelper::getUserInfo($coExecutorId,['ID','EMAIL']);
          $email = $info['EMAIL'];
          $coExecutorExternalInfo = AdvantaHelper::getUserInfoByEmail($email, $users);
          $coExecutorExternalInfo['roleb24'] = 'Соисполнитель';
          $coExecutorExternalInfoList[] = $coExecutorExternalInfo;
        }
        $presaleInfo['coExecutorsList'] = $coExecutorExternalInfoList;

        $users = [];
        $users[] = $executorInfo;

        
        if(!empty($coExecutorExternalInfoList)){
            $users = array_merge($users, $coExecutorExternalInfoList);
        }

        
        $createRecordsToDictionaryXml = AdvantaHelper::buildXmlToRequest(
            'InsertDirectoryRecords',
            $sessId,
            [
              'presaleId'=> $presaleUID,
              'users' => $users
            ]
        );

        
        AdvantaHelper::deleteAllExecutors($presaleUID, $sessId);
        
        //Debug::dbgLog($presaleInfo,'_presaleInfo_');

        //Формируем XML для редактирования информации о проекте (Дата начала/ завершения проекта) - метод UpdateProject
        //https://wiki.a2nta.ru/doku.php/product/api/list/updateproject
        //TODO:: presaleUID надо получать динамически
        $presaleInfo['presaleUID'] = $presaleUID;
        //$presaleInfo['presaleUID'] = UserHelper::getUFCodeByXmlId('PRESALE_UID');

        Debug::dbgLog($presaleInfo,'_presaleInfo_');
        $updatePresaleXml = AdvantaHelper::buildXmlToRequest('UpdateProject', $sessId, $presaleInfo);

        Debug::dbgLog($updatePresaleXml,'_presaleUpdateDates_');

        $curlUpdatePresaleXmlResponse = Curl::sendRequest(
          $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
          ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProject'],
          'UpdateProject',
          $updatePresaleXml
        );
        
        Debug::dbgLog($curlUpdatePresaleXmlResponse,'_presaleUpdateDatesResponse_');

        //Формируем XML для добавления исполнителей и соисполнителей
        
        $presaleCommandUsersXml = AdvantaHelper::buildXmlToRequest('InsertDirectoryRecords', $sessId, 
            [
                'presaleId'=> $presaleUID,
                'users' => $users
            ]
        );

        //Предварительно нужно удалять вообще всех исполнителей и соисполнителей, иначе будут дубли записей
        AdvantaHelper::deleteAllExecutors($presaleUID, $sessId);
        $addPresaleCommandResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/InsertDirectoryRecords'],
            'InsertDirectoryRecords',
            $presaleCommandUsersXml
        );
        //Debug::dbgLog($addPresaleCommandResult,'_addPresaleCommandResult_');
        
        $b24Link = $_SERVER['HTTP_ORIGIN'].'/workgroups/group/'.$taskInfo['GROUP_ID'].'/tasks/task/view/'.$taskInfo['ID'].'/';
        $projectRequisitesUpdateXml = AdvantaHelper::buildXmlToRequest('UpdateProjectFields', $sessId,
            [
                'presaleUID' => $presaleUID,
                'projectBase' => $taskInfo['DESCRIPTION'],
                'projectContent' => $taskInfo['DESCRIPTION'],
                'b24Link' => $b24Link,
                'salesDepartmentUser' => $salesDepartmentUser
            ]
        );
        //Debug::dbgLog($projectRequisitesUpdateXml,'_projectRequisitesUpdateXml_');
        $projectRequisitesUpdateResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProjectFields'],
            'UpdateProjectFields',
            $projectRequisitesUpdateXml
        );
        //Debug::dbgLog($projectRequisitesUpdateResult,'_projectRequisitesUpdateResult_');
        
    }

    //При изменении дела
    public static function onActivityUpdateHandler($oldActivityFields, $newActivityFields)
    {
    	/*
        Debug::dbgLog('onActivityUpdateHandler','_onActivityUpdateHandler_');
        Debug::dbgLog(['file'=>__FILE__,'line'=>__LINE__],'_onActivityUpdateHandler_');
        Debug::dbgLog($oldActivityFields,'_oldActivityFields_');
        Debug::dbgLog($newActivityFields,'_newActivityFields_');

        if(empty($newActivityFields['OWNER_ID'])){
        	$newData = $newActivityFields;
        	Debug::dbgLog($newData,'_newData_');


			$presaleNewInfo['taskName'] = '';
			$presaleNewInfo['taskDescription'] = '';

		}else{
        	$taskId = $newActivityFields['SETTINGS']['TASK_ID'];
        	$dealId = $newActivityFields['OWNER_ID'];

			$presaleUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
			$dealInfo = DealHelper::getDealInfoShort($dealId, [$presaleUFCode]);
			//Debug::dbgLog($dealInfo,'_dealInfo_');

			$presaleUID = $dealInfo[0][$presaleUFCode];
			$presaleNewInfo['presaleUID'] = $presaleUID;
			Debug::dbgLog($presaleUID,'_presaleUID_');
		}
    	*/


		//Обновляем пресейл


        //test123
        //Debug::dbgLog($oldActivityBindings,'_oldActivityBindings_');
        //Debug::dbgLog($newActivityBindings,'_newActivityBindings_');
    }

    public static function onCrmDealUpdateHandler()
	{
		Debug::dbgLog('onCrmDealUpdateHandler','_onCrmDealUpdateHandler_');

	}

}
