<?php

namespace Vdgb\Core\Helpers\Advanta;
use Vdgb\Core\Helpers\XMLHelper;
use Vdgb\Core\Helpers\Curl;
use Vdgb\Core\Debug;
use Vdgb\Core\Helpers\User\UserHelper;
use Vdgb\Core\Helpers\Task\TaskHelper;
use Vdgb\Core\Helpers\IblockHelper;

class AdvantaHelper
{

    //Команда управления пресейлом
    const PRESALE_COMMAND_CONTROL = '6f00ef62-8012-4846-a6f9-a3cef328f505';

    //Ответственный от отдела продаж (0d082b1c-29ca-493d-8027-72d50820c2d8 Андрей Кобзев)
    //04fad1b8-b19d-4ed0-9378-f033ddaec2c0 - Денис Золотарский
    const PROJECT_RESPONSIBLE_ID = '04fad1b8-b19d-4ed0-9378-f033ddaec2c0';

    //UID классификатора статусов жизненного цикла пресейла
    const PRESALE_CLASSIFIER_ID = '8f239169-9148-4128-84f9-83851c196c53';



    const ADVANTA_LOGIN_URL = 'https://advanta-app.rgaz.ru/components/services/login.asmx';
    const ADVANTA_GET_PERSONS_URL = 'https://advanta-app.rgaz.ru/components/services/persons.asmx';

    const ADVANTA_DEFAULT_PARENT_PROJECT_UID = 'f6557f50-1ffc-4551-8334-db477b7c1b34';

    private $authSessionId = '';

    //Ответственный от отдела продаж
    private $advantaResponsibleUserIUD = '';//

    public static function getAdvantaCredentials(): array
    {
      $loginOption = \Bitrix\Main\Config\Option::get("vdgb.core", "advanta_user_login","",false);
      $passwordOption = \Bitrix\Main\Config\Option::get(
           // ID модуля, обязательный параметр
           "vdgb.core",
           // имя параметра, обязательный параметр
           "advanta_user_password",
           // возвращается значение по умолчанию, если значение не задано
           "",
           // ID сайта, если значение параметра различно для разных сайтов
           false
      );
      $advantaServerURL = \Bitrix\Main\Config\Option::get(
           "vdgb.core",
           "advanta_server_url",
           "",
           false
      );

      return ['advantaServerURL'=> $advantaServerURL,'login' => $loginOption, 'password' => $passwordOption];
    }

    public static function getAdvantaResponsibleUserUID()
    {
      $responsibleUserUIDoption = \Bitrix\Main\Config\Option::get(
           // ID модуля, обязательный параметр
           "vdgb.core",
           // имя параметра, обязательный параметр
           "advanta_project_executor",
           // возвращается значение по умолчанию, если значение не задано
           "",
           // ID сайта, если значение параметра различно для разных сайтов
           false
      );

      return $responsibleUserUIDoption;
    }

    public function saveSessionId($sessId)
    {
        $this->authSessionId = $sessId;
    }

    /*public function getSessionId()
    {
        return $this->authSessionId;
    }
    */

    public function parseXML(string $xml)
    {
        $xmlObject = new SimpleXMLElement($xml);
    }

    public static function getUserUIDByEmail(string $targetEmail, array $usersList)
    {
        foreach($usersList as $userItem){
            if($userItem['EMail']!=$targetEmail)
                continue;
            else
                return $userItem['UID'];
        }


    }

    public static function getUserInfoByEmail(string $targetEmail, array $usersList): array
    {
      $result = [];
      foreach($usersList as $userItem){
        if($userItem['EMail']!=$targetEmail)
          continue;
        else
          $result['UID'] = $userItem['UID'];
      }

      $filter = ['EMAIL'=> $targetEmail];
      $rsUsers = \CUser::GetList(($by="id"), ($order="desc"), $filter);
      if($user = $rsUsers->fetch()){
        $result['POSITION'] = $user['WORK_POSITION'];
      }

      return $result;
    }

    public static function getUserAdvantaUIDByUserId(int $userId, string $sessId): string
    {
        $credentials = self::getAdvantaCredentials();

        //-------Получение информации по пользователям
        $allUsersInfoXML = AdvantaHelper::buildXmlToRequest('GetPersons', $sessId, []);
        $curlUsersInfoResponse = Curl::sendRequest(
            //AdvantaHelper::ADVANTA_GET_PERSONS_URL,
            $credentials['advantaServerURL'].'/components/services/persons.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons'],
            'GetPersons',
            $allUsersInfoXML
        );
        $requestUsersSoap = XMLHelper::getRequestSoap($curlUsersInfoResponse['response']);
        $allUsersJson = json_encode($requestUsersSoap);
        $allUsersData = json_decode($allUsersJson, true);
        $xmlObj = new XMLHelper();
        $users = $xmlObj->getNodeFromXmlArray($allUsersData, 'SlPerson');

        $managerId = $userId;
        $managerInfo = UserHelper::getUserInfo($managerId,['ID','EMAIL']);
        $managerEmail = $managerInfo['EMAIL'];
        $managerExternalId = AdvantaHelper::getUserUIDByEmail($managerEmail, $users);
        //$presaleInfo['managerUID'] = $managerExternalId;
        //-------------Конец Получение информации по пользователям

        return $managerExternalId;
    }

    public static function getAllAdvantaUsers(string $sessId): array
    {
        $credentials = self::getAdvantaCredentials();
        $allUsersInfoXML = AdvantaHelper::buildXmlToRequest('GetPersons', $sessId, []);
        $curlUsersInfoResponse = Curl::sendRequest(
            //AdvantaHelper::ADVANTA_GET_PERSONS_URL,
            $credentials['advantaServerURL'].'/components/services/persons.asmx',
            ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetPersons'],
            'GetPersons',
            $allUsersInfoXML
        );

        $requestUsersSoap = XMLHelper::getRequestSoap($curlUsersInfoResponse['response']);
        $allUsersJson = json_encode($requestUsersSoap);
        $allUsersData = json_decode($allUsersJson, true);

        $xmlObj = new XMLHelper();
        $users = $xmlObj->getNodeFromXmlArray($allUsersData, 'SlPerson');

        return $users;
    }

    //раньше было getStageUidByTitle
    public static function getClassificatorUidByTitle(string $stageTitle, array $stageList): string
    {
      $stageUID = '';
      $defaultStageUID = '';
      foreach($stageList as $stageItem){
        if($stageItem['RecordName']==$stageTitle){
          $stageUID = $stageItem['RecordId'];
          break;
        }
        if($stageItem['RecordName']=='Прочее'){
          $defaultStageUID = $stageItem['RecordId'];
        }
      }

      if(empty($stageUID))
        $stageUID = $defaultStageUID;


      return $stageUID;
    }



    
    public static function buildXmlToRequest(string $methodName, string $sessId, array $presaleInfo)
    {
        $outXml = '';

        switch($methodName){
            case 'Authenticate':
              $outXml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:str="http://streamline/">
   <soapenv:Header/>
   <soapenv:Body>
      <str:Authenticate>
         <!--Optional:-->
         <str:login>'.$presaleInfo['login'].'</str:login>
         <!--Optional:-->
         <str:password>'.$presaleInfo['password'].'</str:password>
      </str:Authenticate>
   </soapenv:Body>
</soapenv:Envelope>';
            break;

            case 'CreateProject':
            $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <CreateProject xmlns="http://streamline/">
      <newProject>
        <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

        <!--Родительский проект (РН) ADVANTA_DEFAULT_PARENT_PROJECT_UID-->';

        if(!empty($presaleInfo['parentProjectUID']))
          $outXml.='<ParentProjectId>'.$presaleInfo['parentProjectUID'].'</ParentProjectId>';
        else
          $outXml.='<ParentProjectId>'.self::ADVANTA_DEFAULT_PARENT_PROJECT_UID.'</ParentProjectId>';

        $outXml.='
        <ProjectTypeId>95fa1505-98b1-4561-93b3-1a383e84292a</ProjectTypeId>

        <!--547cdd10-fecd-4f0a-b360-f5df42ac8162  Долматова Марина - Руководитель проекта-->';

        $owner = $presaleInfo['managerUID'];
        if(empty($presaleInfo['managerUID']))
          $owner = 'a1c399b6-0a20-4606-b412-6f382e4854e3';//Денис Чистяков

        $outXml.='<ProjectOwnerId>'.$owner.'</ProjectOwnerId>

        <!--ProjectResponsibleId  исполнитель проекта 0d082b1c-29ca-493d-8027-72d50820c2d8 Андрей Кобзев-->
        <ProjectResponsibleId>'.self::PROJECT_RESPONSIBLE_ID.'</ProjectResponsibleId>



        <ProjectName>'.$presaleInfo['name'].'</ProjectName>';

        if(!empty($presaleInfo['startDate']))
          $outXml.='<PlannedStartDate>'.$presaleInfo['startDate'].'</PlannedStartDate>';
        else
          $outXml.= '<StartDateConstraint>NOT_SET</StartDateConstraint>';       
        

        if(!empty($presaleInfo['finishDate']))
          $outXml.='<PlannedEndDate>'.$presaleInfo['finishDate'].'</PlannedEndDate>';
        else
          $outXml .='<EndDateConstraint>NOT_SET</EndDateConstraint>';

        if(!empty($presaleInfo['dealStatusAdvantaCode']))
          $outXml.='<Status>'.$presaleInfo['dealStatusAdvantaCode'].'</Status>';

        //$outXml .= '<PlannedEndDate>'.$presaleInfo['finishDate'].'</PlannedEndDate>
        //Debug::dbgLog('test01','_test01_');
        $outXml .= '<Fields>';
        if(!empty($presaleInfo['taskDescription']))
          $outXml.='
            <FieldWrapper>
              <FieldName>Основание</FieldName>
              <FieldId>9a2344f0-a169-4b35-93f0-1a9ef96a525b</FieldId>
              <FieldVal>'.$presaleInfo['taskDescription'].'</FieldVal>
              <FieldType>String</FieldType>
            </FieldWrapper>
            <FieldWrapper>
              <FieldName>Содержание проекта</FieldName>
              <FieldId>c43dca64-757e-439e-94ac-91f2454bb23c</FieldId>
              <FieldVal>'.$presaleInfo['taskDescription'].'</FieldVal>
              <FieldType>String</FieldType>
            </FieldWrapper>';

          //Debug::dbgLog('test02','_test02_');
          if(!empty($presaleInfo['b24Link']))
            $outXml.='
            <FieldWrapper>
              <FieldName>Ссылка на Б24</FieldName>
              <FieldId>e2904601-43ed-4576-8405-4429aa5d0ac8</FieldId>
              <FieldVal>
                  &lt;p&gt;&lt;a href="'.$presaleInfo['b24Link'].'"&gt;'.$presaleInfo['b24Link'].'&lt;/a&gt;&lt;/p&gt;
              </FieldVal>
              <FieldType>Html</FieldType>
            </FieldWrapper>';

          if(!empty($presaleInfo['dealSum']))
            $outXml.='
          <FieldWrapper>
            <FieldName>Предварительная стоимость проекта, руб. с НДС</FieldName>
            <FieldId>6c93f643-9af0-4354-99b1-812c9ca5ae73</FieldId>
            <FieldVal>'.$presaleInfo['dealSum'].'</FieldVal>
            <FieldType>Numeric</FieldType>
          </FieldWrapper>';

          //Debug::dbgLog('test03','_test03_');

          if(!empty($presaleInfo['companyINN']))
            $outXml.='
            <FieldWrapper>
              <FieldName>Скрытое поле</FieldName>
              <FieldId>646a2a1b-4a5d-468d-aa5b-59f8713c7994</FieldId>
              <FieldVal>'.$presaleInfo['companyINN'].'</FieldVal>
              <FieldType>Numeric</FieldType>
            </FieldWrapper>';

          if(!empty($presaleInfo['portfolioItemUID']))
            $outXml.='
            <FieldWrapper>
              <FieldName>Принадлежность к Портфелю проектов</FieldName>
              <FieldId>d69c75eb-394a-44ba-94de-e801ccf80db3</FieldId>
              <FieldVal>'.$presaleInfo['portfolioItemUID'].'</FieldVal>
              <FieldType>Directory</FieldType>
            </FieldWrapper>';

          if(!empty($presaleInfo['dealId']))
            $outXml.='
            <!--Presale_id-->
            <FieldWrapper>
              <FieldName>Presale_id</FieldName>
              <FieldId>776adbca-2141-4d3e-ada2-dcee89b59e90</FieldId>
              <FieldVal>'.$presaleInfo['dealId'].'</FieldVal>
            </FieldWrapper>';
          
          $outXml.='</Fields>


      </newProject>
    </CreateProject>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'InsertDirectoryRecords':
                $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <InsertDirectoryRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

      <!--6f00ef62-8012-4846-a6f9-a3cef328f505  Это ID справочника команды управления пресейлами-->
      <directoryTemplateId>'.self::PRESALE_COMMAND_CONTROL.'</directoryTemplateId>
      <projectRecords>';
            foreach($presaleInfo['users'] as $userItem){
              $fieldWrap='<ProjectRecordWrapper>';
              $fieldWrap.='<ProjectId>'.$presaleInfo['presaleId'].'</ProjectId>';
              $fieldWrap.='<Fields>';
              $fieldWrap.= '<FieldWrapper>';
              $fieldWrap.='<FieldName>ФИО участника</FieldName>';
              $fieldWrap.='<FieldId>61bffee0-97d7-47fc-b84a-13f1e69da518</FieldId>';
              $fieldWrap.='<FieldVal>'.$userItem['UID'].'</FieldVal>';
              $fieldWrap.='</FieldWrapper>';

              $fieldWrap.= '<FieldWrapper>';
              $fieldWrap.='<FieldName>Должность</FieldName>';
              $fieldWrap.='<FieldId>7fe90a25-b017-4eb4-9b2d-bea1e78fefce</FieldId>';
              $fieldWrap.='<FieldVal>'.$userItem['POSITION'].'</FieldVal>';
              $fieldWrap.='</FieldWrapper>';

              $fieldWrap.= '<FieldWrapper>';
              $fieldWrap.='<FieldName>Роль в Б24</FieldName>';
              $fieldWrap.='<FieldId>49756443-bcf5-404e-bbbc-477cd8f4eb12</FieldId>';
              $fieldWrap.='<FieldVal>'.$userItem['roleb24'].'</FieldVal>';
              $fieldWrap.='</FieldWrapper>';

              $fieldWrap.='</Fields>';
              $fieldWrap.='</ProjectRecordWrapper>';

              $outXml.= $fieldWrap;
            }
$outXml.='
      </projectRecords>
    </InsertDirectoryRecords>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'GetClassifierRecords':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetClassifierRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

    <classifierId>'.$presaleInfo['classificatorUID'].'</classifierId>


    </GetClassifierRecords>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'UpdateProjectFields':
            $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <UpdateProjectFields xmlns="http://streamline/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

      <!--ID пресейла-->
      <projectId>'.$presaleInfo['presaleUID'].'</projectId>
      <listParams>';

      if(!empty($presaleInfo['stageUID']))
        $outXml.='
        <FieldWrapper>
          <FieldName>Жизненный цикл предпродажного этапа</FieldName>
          <!--UID поля жизненного цикла пресейла-->
          <FieldId>4e33f5f2-9b39-4d87-8509-4bc5d8a9496c</FieldId>
          <!--Метод GetClassifierRecords-->
          <FieldVal>'.$presaleInfo['stageUID'].'</FieldVal>
        </FieldWrapper>';

      if(!empty($presaleInfo['projectPortfolioUID']))
        $outXml.='
        <FieldWrapper>
          <FieldName>Принадлежность к Портфелю проектов</FieldName>
          <FieldId>d69c75eb-394a-44ba-94de-e801ccf80db3</FieldId>
          <FieldVal>'.$presaleInfo['projectPortfolioUID'].'</FieldVal>
        </FieldWrapper>';

      if(!empty($presaleInfo['projectBase'])){
        $projectBase = TaskHelper::cutHTMLTags($presaleInfo['projectBase']);
        //$projectBase = $presaleInfo['projectBase'];
        $outXml.='
        <FieldWrapper>
          <FieldName>Основание</FieldName>
          <FieldId>9a2344f0-a169-4b35-93f0-1a9ef96a525b</FieldId>
          <FieldVal>'.$projectBase.'</FieldVal>
        </FieldWrapper>';
      }


      if(!empty($presaleInfo['projectContent'])){
        $projectContent = TaskHelper::cutHTMLTags($presaleInfo['projectContent']);
        //$projectContent = $presaleInfo['projectContent'];
        $outXml.='
        <FieldWrapper>
          <FieldName>Содержание проекта</FieldName>
          <FieldId>c43dca64-757e-439e-94ac-91f2454bb23c</FieldId>
          <FieldVal>'.$projectContent.'</FieldVal>
        </FieldWrapper>';
      }


      if(!empty($presaleInfo['b24Link']))
        $outXml.='
          <FieldWrapper>
          <FieldName>Ссылка на Б24</FieldName>
          <FieldId>e2904601-43ed-4576-8405-4429aa5d0ac8</FieldId>
          <FieldVal>&lt;p&gt;&lt;a href="'.$presaleInfo['b24Link'].'"&gt;'.$presaleInfo['b24Link'].'&lt;/a&gt;&lt;/p&gt;</FieldVal>
        </FieldWrapper>';

      if(!empty($presaleInfo['price']))
          $outXml.='
        <FieldWrapper>
          <FieldName>Предварительная стоимость проекта, руб. с НДС</FieldName>
          <FieldId>6c93f643-9af0-4354-99b1-812c9ca5ae73</FieldId>
          <FieldVal>'.$presaleInfo['price'].'</FieldVal>
        </FieldWrapper>';

      if(!empty($presaleInfo['companyINN']))
          $outXml.='
        <FieldWrapper>
          <FieldName>ИНН</FieldName>
          <FieldId>646a2a1b-4a5d-468d-aa5b-59f8713c7994</FieldId>
          <FieldVal>'.$presaleInfo['companyINN'].'</FieldVal>
        </FieldWrapper>';

      if(!empty($presaleInfo['salesDepartmentUser']))
        $outXml.='
        <FieldWrapper>
          <FieldName>Ответственный от отдела продаж</FieldName>
          <FieldId>6b0d974a-cd5b-4dcd-9382-0970214de945</FieldId>
          <FieldVal>'.$presaleInfo['salesDepartmentUser']['LAST_NAME'].' '.
          $presaleInfo['salesDepartmentUser']['NAME'].' '.$presaleInfo['salesDepartmentUser']['SECOND_NAME'].'</FieldVal>
        </FieldWrapper>';

      if(!empty($presaleInfo['dealId']))
        $outXml.='<FieldWrapper>
              <FieldName>Presale_id</FieldName>
              <FieldId>776adbca-2141-4d3e-ada2-dcee89b59e90</FieldId>
              <FieldVal>'.$presaleInfo['dealId'].'</FieldVal>
            </FieldWrapper>';

        $outXml.='
      </listParams>
    </UpdateProjectFields>
  </soap:Body>
</soap:Envelope>';

            break;

            case 'GetPersons':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetPersons xmlns="http://streamline/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
    </GetPersons>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'UpdateProject':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <UpdateProject xmlns="http://streamline/">
      <contract>
        <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
        <Project>';
          if(!empty($presaleInfo['TITLE']))
          	$outXml.='<Name>'.$presaleInfo['TITLE'].'</Name>';

          $outXml.='<UID>'.$presaleInfo['presaleUID'].'</UID>';

          if(!empty($presaleInfo['startDate'])){
          	$outXml.='<PlannedStartDate>'.$presaleInfo['startDate'].'</PlannedStartDate>';
            //$outXml.='<PlannedStartDate>'.'2024-09-12T16:47:44'.'</PlannedStartDate>';
          }

          if(!empty($presaleInfo['finishDate']))
          	$outXml.='<PlannedEndDate>'.$presaleInfo['finishDate'].'</PlannedEndDate>';

          if(!empty($presaleInfo['duration'])){
            $outXml.='
            <PlannedDuration>'.$presaleInfo['duration'].'</PlannedDuration>
            <DurationUnit>DAYS</DurationUnit>';
          }

          if(!empty($presaleInfo['dealStatusAdvantaCode']))
            $outXml.='<Status>'.$presaleInfo['dealStatusAdvantaCode'].'</Status>';

          $outXml.='
        </Project>
      </contract>
    </UpdateProject>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'GetRecords':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <directoryId>6f00ef62-8012-4846-a6f9-a3cef328f505</directoryId>
      <projectId>'.$presaleInfo['presaleUID'].'</projectId>
    </GetRecords>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'DeleteDirectoryRecords':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <DeleteDirectoryRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <directoryRecordIds>';

      foreach ($presaleInfo['recordsUIDList'] as $recordUID) {
        $outXml.='<string>'.$recordUID.'</string>';
      }
      $outXml.='</directoryRecordIds>
    </DeleteDirectoryRecords>
  </soap:Body>
</soap:Envelope>';
            break;

            case 'DelegateProject':
                $outXml.='<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <DelegateProject xmlns="http://streamline/">
      <projectData>
        <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
        <ProjectId>'.$presaleInfo['presaleUID'].'</ProjectId>';

        if(!empty($presaleInfo['responsibleUserUID']))
          $outXml.='<DelegateUserId>'.$presaleInfo['responsibleUserUID'].'</DelegateUserId>';

        $outXml.='
        <DelegateOwnerId>'.$presaleInfo['managerUID'].'</DelegateOwnerId>
        <AutoAccept>true</AutoAccept>
      </projectData>
    </DelegateProject>
  </soap:Body>
</soap:Envelope>';
                break;

			case 'ChangeParent':
				$outXml.='<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ChangeParent xmlns="http://streamline/">
      <contract>
        <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
        <ProjectId>'.$presaleInfo['presaleUID'].'</ProjectId>
        <ParentProjectId>'.$presaleInfo['newParentProjectId'].'</ParentProjectId>
      </contract>
    </ChangeParent>
  </soap:Body>
</soap:Envelope>';
				break;

        case 'GetProjects':
          $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetProjects xmlns="http://streamline/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <filterWrappers>
        <FilterWrapper>
          <Field>ParentProjectId</Field>
          <Value>'.$presaleInfo['parentProjectUID'].'</Value>
          <Operation>=</Operation>
          <GroupOr>false</GroupOr>
        </FilterWrapper>
      </filterWrappers>
      <sortWrappers>
        <SortWrapper>
            <Field>WBS</Field>
            <Descending>false</Descending>
        </SortWrapper>
      </sortWrappers>
      <Hierarchical>true</Hierarchical>
    </GetProjects>
  </soap:Body>
</soap:Envelope>';
          break;

          case 'GetProject':
          $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetProject xmlns="http://streamline/">
      <contract>
        <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
        <ProjectId>'.$presaleInfo['projectUID'].'</ProjectId>
      </contract>
    </GetProject>
  </soap:Body>
</soap:Envelope>';
          break;

        }

        return $outXml;
    }

    public function getSessionId(): string
    {
      $credentials = self::getAdvantaCredentials();
      //Debug::dbgLog($credentials, '_credentials_');
      $authXml = self::buildXmlToRequest('Authenticate', '', ['login'=>$credentials['login'],'password'=> $credentials['password']]);
      $curlResponse = Curl::sendRequest(
          //self::ADVANTA_LOGIN_URL,
          $credentials['advantaServerURL'].'/components/services/login.asmx',
          ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/Authenticate'],
          'Authenticate',
          $authXml
      );

      //Debug::dbgLog($authXml, '_authXML_');
      //Debug::dbgLog($curlResponse, '_curlResponse_');
      $requestSoap = XMLHelper::getRequestSoap($curlResponse['response']);
      $json = json_encode($requestSoap);
      $requestData = json_decode($json, true);
      //Debug::dbgLog($requestData,'_requestData_');
      $xmlObj = new XMLHelper();
      //Debug::dbgLog($xmlObj,'_xmlObj_');
      $sessId = $xmlObj->getNodeFromXmlArray($requestData, 'ASPNETSessionId');

      return $sessId;
    }

    //Удаление всех исполнителей и соисполнителей пресейла
    public static function deleteAllExecutors(string $presaleUID, string $sessId)
    {
      $credentials = self::getAdvantaCredentials();
      $allExecutorsRecords = self::getAllExecutorsRecords($presaleUID, $sessId);
      //Debug::dbgLog($allExecutorsRecords,'_allExecutorsRecords_');

      $xml = self::buildXmlToRequest('DeleteDirectoryRecords', $sessId,['recordsUIDList'=> $allExecutorsRecords]);
      //Debug::dbgLog($xml,'_xmlDeleteUsers_');

      $curl = Curl::sendRequest(
        $credentials['advantaServerURL'].'/components/Services/APIService.asmx',
        ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/DeleteDirectoryRecords'],
        'DeleteDirectoryRecords',
        $xml
      );

      //Debug::dbgLog($curl,'_curlDeleteUsers_');
    }

    //получить всех пользователей команды управления пресейлом
    public static function getAllExecutorsRecords(string $presaleUID, string $sessId): array
    {
      $credentials = self::getAdvantaCredentials();
      $xml = self::buildXmlToRequest('GetRecords', $sessId, 
        [
          'presaleUID'=> $presaleUID
        ]
      );
      $curlResponse = Curl::sendRequest(
        $credentials['advantaServerURL'].'/components/Services/APIService.asmx',
        ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://tempuri.org/GetRecords'],
        'GetRecords',
        $xml
      );
      $requestSoap = XMLHelper::getRequestSoap($curlResponse['response']);
      $json = json_encode($requestSoap);
      $data = json_decode($json, true);
      $xmlObj = new XMLHelper();
      $records = $xmlObj->getNodeFromXmlArray($data, 'RecordWrapper');
      //$records = $xmlObj->getNodeFromXmlArray($data, 'Records');
      //Debug::dbgLog($records,'_RECORDS_');
      //Debug::dbgLog($data,'_JSON_DATA_');

      $recordsUIDList = [];
      //Debug::dbgLog($records,'_RECORDS_');
      //Debug::dbgLog(count($records),'_RECORDS_COUNT_');

      if(empty($records['RecordId']))
        foreach($records as $recordItem){
        //foreach($records['Fields']['FieldWrapper'] as $recordItem){
          $recordsUIDList[] = $recordItem['RecordId'];
        }
      else
        $recordsUIDList[] = $records['RecordId'];


      //Debug::dbgLog($recordsUIDList,'_recordsUIDList_');
      return $recordsUIDList;
    }


    public static function changeParentRequest(string $sessId, string $presaleUID, string $newParentProjectUID): array
    {
      $credentials = self::getAdvantaCredentials();

      $changeParentSectionXml = AdvantaHelper::buildXmlToRequest('ChangeParent', $sessId,
        [
          'presaleUID' => $presaleUID,
          'newParentProjectId' => $newParentProjectUID
        ]
      );


      $changeParentSectionResponse = Curl::sendRequest(
        $credentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
        ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/ChangeParent'],
        'ChangeParent',
        $changeParentSectionXml
      );

      return $changeParentSectionResponse;

    }

    public static function getProjectsStructure(string $sessId, string $parentProjectUID): array
    {
      $credentials = self::getAdvantaCredentials();
      $projectsStructureXML = AdvantaHelper::buildXmlToRequest('GetProjects', $sessId,
        [
          'parentProjectUID' => $parentProjectUID
        ]
      );

      $projectsStructureResponse = Curl::sendRequest(
        $credentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
        ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetProjects'],
        'GetProjects',
        $projectsStructureXML
      );

      Debug::dbgLog($projectsStructureResponse,'_projectsStructureResponse_');

      return $projectsStructureResponse;
    }

    
  

  public static function tree($data): array
  {
    // Подготовка плоского массива
    $items = [];
    foreach ($data['ProjectWrapper'] as $project) {
        $items[] = [
            'UID' => (string)$project['UID'],
            'ParentProjectId' => (string)$project['ParentProjectId'],
            'Name' => (string)$project['Name'],
            'children' => [] // Добавляем пустой массив для детей
        ];
    }

    // Создаем хеш-таблицу для быстрого доступа
    $map = [];
    foreach ($items as &$item) {
        $map[$item['UID']] = &$item;
    }

    // Строим дерево
    $tree = [];
    $prefix = '';
    foreach ($items as &$item) {
        if (!empty($item['ParentProjectId']) && isset($map[$item['ParentProjectId']])) {
            //$curItem = &$item;
            //$curItem['Name'] = $prefix.$curItem['Name'];
            $map[$item['ParentProjectId']]['children'][] = &$item;
            //$map[$item['ParentProjectId']]['children'][] = &$curItem;
        } else {
            // Это корневой элемент
            $tree[] = &$item;
        }
    }

    foreach($tree as &$treeItem){
      $treeItem['enabled'] = false;
      if(empty($treeItem['children'])){
        $treeItem['enabled'] = true;
      }else{

        foreach($treeItem['children'] as &$childLevel_2_item){
          $childLevel_2_item['enabled'] = false;
          if(empty($childLevel_2_item['children'])){
            $childLevel_2_item['enabled'] = true;
          }else{
            foreach($childLevel_2_item['children'] as &$childrenLevel_3_item){
              $childrenLevel_3_item['enabled'] = false;
              if(empty($childrenLevel_3_item['children']))
                $childrenLevel_3_item['enabled'] = true;
              else{
                foreach($childrenLevel_3_item['children'] as &$childrenLevel_4_item){
                  $childrenLevel_4_item['enabled'] = false;
                  if(empty($childrenLevel_4_item['children']))
                    $childrenLevel_4_item['enabled'] = true;
                }
              }
            }
          }
        }
      }
    }

    

    return $tree;

  }

  public static function saveTreeToIblock(string $kanbanProjectUID)
  {
    $hasSection = IblockHelper::hasSectionByCode('advanta_sections', $kanbanProjectUID);

    Debug::dbgLog($kanbanProjectUID,'_kanbanProjectUID_');
    Debug::dbgLog($hasSection,'_hasSection_');
  }

  //Получить плановые даты начала и завершения проекта
  public static function getAdvantaProjectInfo(string $sessId, string $advantaProjectUID)
  {
    $credentials = self::getAdvantaCredentials();
    $xml = self::buildXmlToRequest('GetProject', $sessId, ['projectUID' => $advantaProjectUID]);

    $projectResponse = Curl::sendRequest(
      $credentials['advantaServerURL'].'/components/Services/APIProjects.asmx',
      ['Content-Type: text/xml; charset=utf-8','SOAPAction: http://streamline/GetProject'],
      'GetProject',
      $xml
    );

    $request = XMLHelper::getRequestSoap($projectResponse['response']);
    $requestJson = json_encode($request);
    $requestData = json_decode($requestJson, true);

    $xmlObj = new XMLHelper();
    $systemStartDate = $xmlObj->getNodeFromXmlArray($requestData, 'SystemStartDate');
    $systemStartDate = str_replace('T',' ',$systemStartDate);
    $systemStartDateParts = explode(' ', $systemStartDate);


    $dataPart = explode('-', $systemStartDateParts[0]);
    $day = $dataPart[2];
    $month = $dataPart[1];
    $year = $dataPart[0];
    $startDate = implode('.',[$day,$month,$year]);
    $startDate = $startDate.' '.$systemStartDateParts[1];


    $systemFinishDate = $xmlObj->getNodeFromXmlArray($requestData, 'SystemEndDate');
    $systemFinishDate = str_replace('T',' ',$systemFinishDate);
    $systemFinishDateParts = explode(' ',$systemFinishDate);
    
    $dataPart = explode('-', $systemFinishDateParts[0]);
    $day = $dataPart[2];
    $month = $dataPart[1];
    $year = $dataPart[0];
    $finishDate = implode('.',[$day,$month,$year]);
    $finishDate = $finishDate.' '.$systemFinishDateParts[1];

    

    $resources = $xmlObj->getNodeFromXmlArray($requestData, 'ResourceAssignmentWrapper');
    $usersUIDList = array_column($resources,'PersonId');
    //Debug::dbgLog($usersUIDList,'_usersUIDList_');

    $allAdvantaUsers = self::getAllAdvantaUsers($sessId);
    //Debug::dbgLog($allAdvantaUsers,'_allAdvantaUsers_');
    $filteredUsers = self::getUsersByFilter($usersUIDList, $allAdvantaUsers);
    //Debug::dbgLog($filteredUsers,'_filteredUsers_');

    return ['systemStartDate' => $startDate, 'systemFinishDate' => $finishDate, 'resources' => $filteredUsers];
  }

  public static function getAdvantaProjectInfoByBus(string $advantaProjectUID)
  {
    $xml = self::buildXmlToRequest('GetProject', "", ['projectUID' => $advantaProjectUID]);

    return [];
  }

  public static function getUsersByFilter(array &$usersUIDList, array $allUsersList): array
  {

    $allUsersUIDList = array_column($allUsersList, 'UID');
    $emailList = [];
    foreach($usersUIDList as $key => &$userUID){
      $index = array_search($userUID, $allUsersUIDList);

      if(!empty($index)){
        unset($usersUIDList[$key]);
        $usersUIDList[$key]['UID'] = $allUsersList[$index]['UID'];
        $usersUIDList[$key]['email'] = $allUsersList[$index]['EMail'];
        $usersUIDList[$key]['fullName'] = $allUsersList[$index]['FullName'];
        $emailList[] = $allUsersList[$index]['EMail'];
      }
    }


    //Debug::dbgLog($usersUIDList,'_usersUIDList_');
    
    //Debug::dbgLog($emailList,'_emailList_');
    $usersIdList = UserHelper::getUsersInfoListByFilter(['=EMAIL' => $emailList], ['ID','EMAIL']);

    foreach($usersUIDList as $key => &$userItem){
      $index = array_search($userItem['email'], array_column($usersIdList,'EMAIL'));
      $usersUIDList[$key]['ID'] = $usersIdList[$index]['ID'];
    }


    return $usersUIDList;
  }

}
?>
