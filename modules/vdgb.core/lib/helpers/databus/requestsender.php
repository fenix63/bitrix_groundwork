<?php

namespace Vdgb\Core\Helpers\Databus;

use Vdgb\Core\Helpers\Curl;
use Vdgb\Core\Debug;

class RequestSender
{
    const ADVANTA_DEFAULT_PARENT_PROJECT_UID = 'f6557f50-1ffc-4551-8334-db477b7c1b34';
    const PROJECT_RESPONSIBLE_ID = '04fad1b8-b19d-4ed0-9378-f033ddaec2c0';

    public static function buildXMLToRequest(string $methodName, array $presaleInfo): string
    {
        $outXml = '';
        
        
        switch ($methodName) {
            case 'GetPersons':
                $outXml = <<<HERE
'<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetPersons xmlns="http://streamline/">
    </GetPersons>
  </soap:Body>
</soap:Envelope>';
HERE;
                break;

            case 'GetClassifierRecords':
                $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetClassifierRecords xmlns="http://tempuri.org/">
    <classifierId>'.$presaleInfo["classificatorUID"].'</classifierId>
    </GetClassifierRecords>
  </soap:Body>
</soap:Envelope>';

                break;

            case 'GetClassifierRecords':
                $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetClassifierRecords xmlns="http://tempuri.org/">
    <classifierId>'.$presaleInfo['classificatorUID'].'</classifierId>
    </GetClassifierRecords>
  </soap:Body>
</soap:Envelope>';
                break;

            case 'CreateProject':
                $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <CreateProject xmlns="http://streamline/">
      <newProject>

        <!--Родительский проект (РН)-->';

        
        if(!empty($presaleInfo['parentProjectUID']))
            $outXml.='<ParentProjectId>'.$presaleInfo['parentProjectUID'].'</ParentProjectId>';
        else
            $outXml.= '<ParentProjectId>'.self::ADVANTA_DEFAULT_PARENT_PROJECT_UID.'</ParentProjectId>';
        

        $outXml.='<ProjectTypeId>95fa1505-98b1-4561-93b3-1a383e84292a</ProjectTypeId>';

        $owner = $presaleInfo['managerUID'];
        if(empty($presaleInfo['managerUID']))
          $owner = 'a1c399b6-0a20-4606-b412-6f382e4854e3';//Денис Чистяков

        $outXml.='<ProjectOwnerId>'.$owner.'</ProjectOwnerId>
        <ProjectResponsibleId>'.self::PROJECT_RESPONSIBLE_ID.'</ProjectResponsibleId>

        <ProjectName>'.$presaleInfo['name'].'</ProjectName>';

        if(!empty(empty($presaleInfo['startDate'])))
            $outXml.='<PlannedStartDate>'.$presaleInfo['startDate'].'</PlannedStartDate>';
        else
            $outXml.='<StartDateConstraint>NOT_SET</StartDateConstraint>';

        
        if(!empty($presaleInfo['finishDate']))
            $outXml.='<PlannedEndDate>'.$presaleInfo['finishDate'].'</PlannedEndDate>';
        else
            $outXml.='<EndDateConstraint>NOT_SET</EndDateConstraint>';

        if(!empty($presaleInfo['dealStatusAdvantaCode']))
          $outXml.='<Status>'.$presaleInfo['dealStatusAdvantaCode'].'</Status>';
        
        
        
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

            
        }
    

        return $outXml;
    }


    public static function buildJSONToRequest(string $xml)
    {
        $json = [
            'message' => $xml
        ];

        return json_encode($json);
    }

}

?>