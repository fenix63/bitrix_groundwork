<?

namespace Vdgb\Core\Agents;
use Vdgb\Core\Helpers\Advanta\AdvantaHelper;
use Vdgb\Core\Helpers\Deal\DealHelper;
use Vdgb\Core\Helpers\User\UserHelper;
use Vdgb\Core\Helpers\Company\CompanyHelper;
use Vdgb\Core\Helpers\Curl;
use Vdgb\Core\Helpers\XMLHelper;
use Vdgb\Core\Events\CrmEvents;
use Vdgb\Core\Debug;


class SendAdvanta
{
    public static $sessId = '';

    //Ответственный от отдела продаж
    public static $responsibleUserIUD = '';

    public static function getSessionId(array $advantaCredentials)
    {
        //Debug::dbgLog($advantaCredentials,'_advantaCredentials_');

        $authXml = AdvantaHelper::buildXmlToRequest('Authenticate', '',
            [
                'login' => $advantaCredentials['login'],
                'password' => $advantaCredentials['password']
            ]
        );

        //Debug::dbgLog($authXml,'_authXml_');

        $curlResponse = Curl::sendRequest(
            //AdvantaHelper::ADVANTA_LOGIN_URL,
            $advantaCredentials['advantaServerURL'].'/components/services/login.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/Authenticate'],
            //AdvantaHelper::advantaHeaders['AuthenticateHeader']
            'Authenticate',
            $authXml
        );
        //Debug::dbgLog($curlResponse,'_curlResponseAuth_');

        $requestSoap = XMLHelper::getRequestSoap($curlResponse['response']);
        $json = json_encode($requestSoap);
        $requestData = json_decode($json, true);
        $xmlObj = new XMLHelper();
        $sessId = $xmlObj->getNodeFromXmlArray($requestData, 'ASPNETSessionId');

        self::$sessId = $sessId;
    }

    public static function send(int $dealId)
    {
        $advantaCredentials = AdvantaHelper::getAdvantaCredentials();
        $sessId = self::getSessionId($advantaCredentials);
        //Debug::dbgLog($sessId,'_sessId_');
        //Debug::dbgLog(self::$sessId,'_self_sessId_');

        /*if(empty($sessId) && empty(self::$sessId)){
            return ['result'=> false,'desc'=>'sessId пусто'];
        }
        */

        $dealInfo = DealHelper::getDealInfo($dealId);
        Debug::dbgLog($dealInfo,'_dealInfo_');
        
        $presaleInfo['TITLE'] = $dealInfo[$dealId]['TITLE'];
        $presaleInfo['price'] = $dealInfo[$dealId]['OPPORTUNITY'];

        $dealStatusCode = $dealInfo[$dealId]['STAGE_ID'];
        $deadStatusTitle = DealHelper::getDealNameByCode($dealStatusCode);

        $presaleInfo['dealStatus'] = $deadStatusTitle;
        
        switch($presaleInfo['dealStatus']){
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

        $presaleInfo['dealId'] = $dealId;



        $classificatorAllRecordsXml = AdvantaHelper::buildXmlToRequest(
            'GetClassifierRecords',
            self::$sessId,
            ['classificatorUID'=>'8f239169-9148-4128-84f9-83851c196c53']
          );

        $classificatorAllRecordsResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
            ['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
            'GetClassifierRecords',
            $classificatorAllRecordsXml
        );

        Debug::dbgLog($classificatorAllRecordsResult,'_classificatorAllRecordsResult_');

        $classificatorRequestSoap = XMLHelper::getRequestSoap($classificatorAllRecordsResult['response']);
        $classificatorJson = json_encode($classificatorRequestSoap);
        $classificatorRequestData = json_decode($classificatorJson, true);

        $xmlObj = new XMLHelper();
        $classificatorXmlObj = new XMLHelper();
        $classificatorRecords = $xmlObj->getNodeFromXmlArray($classificatorRequestData, 'RecordWrapper');
        $classificatorUID = AdvantaHelper::getClassificatorUidByTitle($deadStatusTitle, $classificatorRecords);
        $presaleInfo['stageUID'] = $classificatorUID;


        //Раздел пресейла
        $parentProjectUFCode = UserHelper::getUFCodeByXmlId('ADVANTA_SECTION');//UF_CRM_1741179568246
        
        //Debug::dbgLog($dealInfo,'_dealInfo_2_');
        //Debug::dbgLog($dealId,'_dealId_');
        //Debug::dbgLog($parentProjectUFCode,'_parentProjectUFCode_');

        if(!empty($dealInfo[$dealId][$parentProjectUFCode])){
            $parentProjectUFXml_Id = UserHelper::getEnumXmlIdById($dealInfo[$dealId][$parentProjectUFCode]);
            $presaleInfo['parentProjectUID'] = $parentProjectUFXml_Id;
        }
        
        

        //Принадлежность к портфелю проектов
        $portfolioAllListXml = AdvantaHelper::buildXmlToRequest(
            'GetClassifierRecords',
            self::$sessId,
            ['classificatorUID'=>'d5d01090-73e3-4d26-878f-00e8a45b9774']
        );
        $portfolioAllListRecords = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
            ['Content-Type: text/xml;charset=utf-8','SOAPAction: http://tempuri.org/GetClassifierRecords'],
            'GetClassifierRecords',
            $portfolioAllListXml
        );
        //Debug::dbgLog($portfolioAllListRecords,'_portfolioAllListRecords_');

        $portfolioAllListRecordsItems = CrmEvents::getXMLNode($portfolioAllListRecords['response'], 'RecordWrapper');
        //Debug::dbgLog($portfolioAllListRecordsItems,'_portfolioAllListRecordsItems_');
        $enumParentSectionId = $dealInfo[$dealId][$parentProjectUFCode];
        //Debug::dbgLog($enumParentSectionId,'_enumParentSectionId_');

        if(!empty($enumParentSectionId)){
            $enumParentSectionTitle = UserHelper::getEnumTextValueById($enumParentSectionId);
            $enumParentSectionPortFolioUID = AdvantaHelper::getClassificatorUidByTitle(
            $enumParentSectionTitle, $portfolioAllListRecordsItems);
        }
       

        $presaleInfo['projectPortfolioUID'] = $enumParentSectionPortFolioUID;



        //$companyUFCode = UserHelper::getUFCodeByXmlId('COMPANY');
        $companyId = $dealInfo[$dealId]['COMPANY_ID'];
        $companyInfo = CompanyHelper::getCompanyRequisites($companyId);
        $companyInn = $companyInfo[0]['RQ_INN'];
        $presaleInfo['companyINN'] = $companyInn;


        //-------Получение информации по пользователям
        $allUsersInfoXML = AdvantaHelper::buildXmlToRequest('GetPersons', self::$sessId, []);
        $curlUsersInfoResponse = Curl::sendRequest(
          $advantaCredentials['advantaServerURL'].'/components/services/persons.asmx',
          ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons'],
          'GetPersons',
          $allUsersInfoXML
        );
        //Debug::dbgLog($curlUsersInfoResponse,'_curlUsersInfoResponse_');

        $requestUsersSoap = XMLHelper::getRequestSoap($curlUsersInfoResponse['response']);
        $allUsersJson = json_encode($requestUsersSoap);
        $allUsersData = json_decode($allUsersJson, true);
        $users = $xmlObj->getNodeFromXmlArray($allUsersData, 'SlPerson');

        $managerUserFieldCode = UserHelper::getUFCodeByXmlId('PROJECT_MANAGER');
        $managerId = $dealInfo[$dealId][$managerUserFieldCode];
        Debug::dbgLog($managerId,'_managerId_');

        if(!empty($managerId)){
            $managerInfo = UserHelper::getUserInfo($managerId,['ID','EMAIL']);
            $managerEmail = $managerInfo['EMAIL'];
            $managerExternalId = AdvantaHelper::getUserUIDByEmail($managerEmail, $users);
            $presaleInfo['managerUID'] = $managerExternalId;
        }
        
        
        
        
        //-------------Конец Получение информации по пользователям


        //Получение UID пресейла
        $presaleUIDUFCode = UserHelper::getUFCodeByXmlId('PRESALE_UID');
        $presaleInfo['presaleUID'] = $dealInfo[$dealId][$presaleUIDUFCode];
        if(empty($presaleInfo['presaleUID'])){
            Debug::dbgLog([ 'dealId'=> $dealId, 'desc'=>'Не задандано поле UID пресейла в адванте'],'_ExportError_');
            return ['result'=>false,'desc'=>'Не задандано поле UID пресейла в адванте'];
        }

        //Перемещаем пресейл в нужный раздел (РН, РГФ и т.д.)
        //Debug::dbgLog($presaleInfo,'_presaleInfo_1_');
        if(!empty($presaleInfo['parentProjectUID']))
            $changeParentRequestResult = AdvantaHelper::changeParentRequest(self::$sessId, $presaleInfo['presaleUID'], $presaleInfo['parentProjectUID']);

        
        //Debug::dbgLog($changeParentRequestResult,'_changeParentRequestResult_');
        //Debug::dbgLog($presaleUIDUFCode,'_presaleUIDUFCode_');
        //Debug::dbgLog($presaleInfo['presaleUID'],'_presaleUID_1_');


        //Данные из задачи
        $taskList = DealHelper::getActivity($dealId);
        $dealInfo_test = DealHelper::getDealInfo($dealId);
        Debug::dbgLog($dealId,'_dealId_');
        Debug::dbgLog($taskList,'_taskList_');
        Debug::dbgLog($dealInfo_test,'_dealInfo_test_');


        if(!empty($taskList)){
            $taskInfo = DealHelper::getTaskInfoById($taskList[0]['ASSOCIATED_ENTITY_ID']);
            Debug::dbgLog($taskInfo,'_taskInfo_');

            $presaleInfo['taskId'] = $taskInfo['ID'];
            $presaleInfo['taskName'] = $taskInfo['TITLE'];
            $presaleInfo['projectBase'] = $taskInfo['DESCRIPTION'];
            $presaleInfo['projectContent'] = $taskInfo['DESCRIPTION'];
            $presaleInfo['b24Link'] = $_SERVER['HTTP_ORIGIN'].'/workgroups/group/'.$taskInfo['GROUP_ID'].'/tasks/task/view/'.$taskInfo['ID'].'/';

            $executorId = $taskInfo['RESPONSIBLE_ID'];
            Debug::dbgLog($executorId,'_executorId_');
            if(!empty($executorId)){
                $executorInfo = UserHelper::getUserInfo($executorId,['ID','EMAIL']);
                $executorInfo['roleb24'] = 'Исполнитель';
                $executorEmail = $executorInfo['EMAIL'];

                $salesDepartmentUser = UserHelper::getUserInfo($executorId,['NAME','LAST_NAME','SECOND_NAME']);
                $presaleInfo['salesDepartmentUser'] = $salesDepartmentUser;

                $executorInfo = AdvantaHelper::getUserInfoByEmail($executorEmail, $users);
                $presaleInfo['executorInfo'] = $executorInfo;
                $executorInfo['roleb24'] = 'Исполнитель';
            }
            

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
        }else{
            $users = [];
        }
        

        //Конец получения всех пользователей адванты



        if(!empty($taskInfo['DEADLINE'])){
            if(!empty($taskInfo['START_DATE_PLAN'])){
                $presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['START_DATE_PLAN']);
                $startDateObj = new \Bitrix\Main\Type\DateTime($taskInfo['START_DATE_PLAN']);
                $startDate = new \DateTime(explode(' ',$taskInfo['START_DATE_PLAN'])[0]);
                
                $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
                //$presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d 01:00:00");
                $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);
            }else{

                if(!empty($taskInfo['CREATED_DATE'])){
                    $presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['CREATED_DATE']);
                    $startDateObj = new \Bitrix\Main\Type\DateTime($taskInfo['CREATED_DATE']);
                    $startDate = new \DateTime(explode(' ',$taskInfo['CREATED_DATE'])[0]);
                    
                    $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
                    //$presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d 01:00:00");
                    $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);
                }else{
                    $presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime();
                    $startDateObj = new \Bitrix\Main\Type\DateTime();
                    $startDate = new \DateTime();

                    $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
                    //$presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d 01:00:00");
                    $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);
                }
            }


            $presaleInfo['finishDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['DEADLINE']);
            $finishDataObj = new \Bitrix\Main\Type\DateTime($taskInfo['DEADLINE']);
            $finishDate = new \DateTime(explode(' ',$taskInfo['DEADLINE'])[0]);
            
            $presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d H:i:s");
            //$presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d 01:00:00");
            $presaleInfo['finishDate'] = str_replace(" ","T", $presaleInfo['finishDate']);

            //$duration = $finishDate->diff($startDate)->days + 1;
            $duration = $finishDate->diff($startDate)->days;
            $presaleInfo['duration'] = $duration;
            //Debug::dbgLog($startDate,'_startDate_');
            //Debug::dbgLog($finishDate,'_finishDate_');
            //Debug::dbgLog($duration,'_duration_');

        }else{
            $presaleInfo['startDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['START_DATE_PLAN']);
            $startDateObj = new \Bitrix\Main\Type\DateTime($taskInfo['START_DATE_PLAN']);
            $startDate = new \DateTime($taskInfo['START_DATE_PLAN']);

            $presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d H:i:s");
            //$presaleInfo['startDate'] = $presaleInfo['startDate']->format("Y-m-d 01:00:00");
            $presaleInfo['startDate'] = str_replace(" ", "T", $presaleInfo['startDate']);


            $presaleInfo['finishDate'] = new \Bitrix\Main\Type\DateTime($taskInfo['END_DATE_PLAN']);
            $finishDateObj = new \Bitrix\Main\Type\DateTime($taskInfo['END_DATE_PLAN']);
            $finishDate = new \DateTime($taskInfo['END_DATE_PLAN']);

            $presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d H:i:s");
            //$presaleInfo['finishDate'] = $presaleInfo['finishDate']->format("Y-m-d 01:00:00");
            $presaleInfo['finishDate'] = str_replace(" ","T", $presaleInfo['finishDate']);

            //$duration = $finishDate->diff($startDate)->days + 1;//+1 если надо включать конечную дату
            $duration = $finishDate->diff($startDate)->days;
            $presaleInfo['duration'] = $duration;
            //Debug::dbgLog($duration,'_duration_');
        }
        //Debug::dbgLog($presaleInfo,'_presaleInfo_');
        
        //Конец Данные из задачи



        //Обновляем пресейл (Название, дата начала, дата завершения) - не обновляется дата начала
        $updatePresaleXml = AdvantaHelper::buildXmlToRequest('UpdateProject', self::$sessId, $presaleInfo);
        //Debug::dbgLog($updatePresaleXml,'_updatePresaleXml_');
        $curlUpdatePresaleXmlResponse = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProject'],
            'UpdateProject',
            $updatePresaleXml
        );
        //Debug::dbgLog($curlUpdatePresaleXmlResponse,'_curlUpdatePresaleXmlResponse_');

        Debug::dbgLog($presaleInfo,'_presaleInfo_');
        //Обновляем жизненный цикл (работает)
        $updateProjectFieldsXml = AdvantaHelper::buildXmlToRequest(
            'UpdateProjectFields',
            self::$sessId,
            $presaleInfo
        );
        Debug::dbgLog($updateProjectFieldsXml,'_updateProjectFieldsXml_');

        $projectRequisitesUpdateResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/UpdateProjectFields'],
            'UpdateProjectFields',
            $updateProjectFieldsXml
        );

        Debug::dbgLog($projectRequisitesUpdateResult,'_projectRequisitesUpdateResult_');

        self::$responsibleUserIUD = AdvantaHelper::getAdvantaResponsibleUserUID();
        $presaleInfo['responsibleUserUID'] = self::$responsibleUserIUD;
        


        //Смена руководителя проекта (работает, но есть проблемы с лицензиями)
        $delegateProjectXml = AdvantaHelper::buildXmlToRequest('DelegateProject', self::$sessId, $presaleInfo);
        //Debug::dbgLog($delegateProjectXml,'_delegateProjectXml_');

        $delegateProjectResponse = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/DelegateProject'],
            'DelegateProject',
            $delegateProjectXml
        );

        
        //Обновление исполнителей и соисполнителей (не работает)
        AdvantaHelper::deleteAllExecutors($presaleInfo['presaleUID'], self::$sessId);
        

        
        $presaleCommandUsersXml = AdvantaHelper::buildXmlToRequest('InsertDirectoryRecords', self::$sessId, 
            [
                'presaleId'=> $presaleInfo['presaleUID'],
                'users' => $users
            ]
        );

        //Debug::dbgLog($presaleInfo['presaleUID'],'_presaleUID_');
        //Debug::dbgLog($presaleCommandUsersXml,'_presaleCommandUsersXml_Import_');

        $addPresaleCommandResult = Curl::sendRequest(
            $advantaCredentials['advantaServerURL'].'/components/Services/APIService.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/InsertDirectoryRecords'],
            'InsertDirectoryRecords',
            $presaleCommandUsersXml
        );



        //Debug::dbgLog($presaleInfo,'_presaleInfo_');

        return ['result'=>true];
    }
}

//SendAdvanta::send($_POST['deal_id']);

?>