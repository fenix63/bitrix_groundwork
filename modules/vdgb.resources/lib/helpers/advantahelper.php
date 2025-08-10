<?php

namespace Vdgb\Resources\Helpers;
use Vdgb\Resources\Curl;
use Vdgb\Resources\Helpers\XMLHelper;
use Vdgb\Resources\Debug;


class AdvantaHelper
{
    private static $authSessionId = null;
    private static $advantaLogin = null;
    private static $advantaPassword = null;
    private static $advantaServerURL = null;

    public static function sendRequest(string $methodName, array $data)
    {
        if(!self::$authSessionId){
            $sessId = self::getSessionId();
        }        

        if(self::$authSessionId){
            $xml = self::buildXmlToRequest($methodName, self::$authSessionId, $data);
            //Debug::dbgLog($xml,'_xml_');

            $curlResponse = Curl::sendRequest(
                self::$advantaServerURL.$data['urlTail'],
                $data['curlHeaders'],
                $methodName,
                $xml
            );
            $response = self::parseXmlResponse($curlResponse['response'], $data['nodeTarget']);
        }/*else{
            throw new \Exception('Не определена ID сессии для Адванты');
        }*/
        
        return $response;
    }

    public static function parseXmlResponse(string $xml, string $nodeName = '')
    {
      $requestSoap = XMLHelper::getRequestSoap($xml);
      $json = json_encode($requestSoap);
      $requestData = json_decode($json, true);
      $xmlObj = new XMLHelper();
      $data = $xmlObj->getNodeFromXmlArray($requestData, $nodeName);

      return $data;
    }

    public static function getAdvantaCredentials(): array
    {
      $loginOption = \Bitrix\Main\Config\Option::get("vdgb.resources", "advanta_user_login","",false);
      $passwordOption = \Bitrix\Main\Config\Option::get("vdgb.resources","advanta_user_password","",false);
      $advantaServerURL = \Bitrix\Main\Config\Option::get("vdgb.resources","advanta_server_url","",false);

      self::$advantaLogin = $loginOption;
      self::$advantaPassword = $passwordOption;
      self::$advantaServerURL = $advantaServerURL;

      return ['advantaServerURL'=> $advantaServerURL,'login' => $loginOption, 'password' => $passwordOption];
    }

    public static function getSessionId(): string
    {
      $credentials = self::getAdvantaCredentials();
      //Debug::dbgLog($credentials, '_credentials_');
      

      $authXml = self::buildXmlToRequest('Authenticate', '', ['login'=>$credentials['login'],'password'=> $credentials['password']]);
      //Debug::dbgLog($authXml, '_authXml_');

      $curlResponse = Curl::sendRequest(
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
      //Debug::dbgLog($sessId,'_sessId_');
      self::$authSessionId = $sessId;

      return $sessId;
    }


    public static function buildXmlToRequest(string $methodName, string $sessId, array $info)
    {
        $outXml = '';

        switch($methodName){
            case 'Authenticate':
              $outXml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:str="http://streamline/">
   <soapenv:Header/>
   <soapenv:Body>
      <str:Authenticate>
         <!--Optional:-->
         <str:login>'.$info['login'].'</str:login>
         <!--Optional:-->
         <str:password>'.$info['password'].'</str:password>
      </str:Authenticate>
   </soapenv:Body>
</soapenv:Envelope>';
            break;

            case 'GetRecords':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetRecords xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <directoryId>'.$info['directoryId'].'</directoryId>
      <projectId>'.$info['projectUID'].'</projectId>
    </GetRecords>
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

            case 'InsertDirectoryRecord':
              $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <InsertDirectoryRecord xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <directoryTemplateId>977ff0ae-56f8-40ad-8919-8175788e38c8</directoryTemplateId>
      <projectId>'.$info['projectUID'].'</projectId>
      <lstParams>
        <FieldWrapper>
          <FieldName>Ресурс</FieldName>
          <FieldId>caaaaaac-8920-4ab0-a553-b4516a8417a1</FieldId>
          <FieldVal>'.$info['userUID'].'</FieldVal>
          <FieldType />
        </FieldWrapper>
        <FieldWrapper>
          <FieldName>Кол-во часов</FieldName>
          <FieldId>06ff935a-c7fc-4ab3-8ad7-9012927cc651</FieldId>
          <FieldVal>'.$info['time'].'</FieldVal>
          <FieldType />
        </FieldWrapper>
        <FieldWrapper>
            <FieldName>Что сделано/Риски</FieldName>
            <FieldId>dfa2cb7e-fb2c-4b8b-93a5-443190ecf6fd</FieldId>
            <FieldVal>'.$info['comment'].'</FieldVal>
            <FieldType />
        </FieldWrapper>
      </lstParams>
      <record>
        <Date>'.$info['date'].'</Date>
      </record>
    </InsertDirectoryRecord>
  </soap:Body>
</soap:Envelope>';
              break;

              case 'ChangeDirectoryRecord':
                $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ChangeDirectoryRecord xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <directoryRecordId>'.$info['directoryRecordId'].'</directoryRecordId>
      <lstParams>
        <FieldWrapper>
            <FieldName>Дата</FieldName>
            <FieldId>caaaaaac-8920-4ab0-a553-b4516a8417ac</FieldId>
            <FieldVal>2025-06-10 15:36:39Z</FieldVal>
            <FieldType />
        </FieldWrapper>
        <FieldWrapper>
          <FieldName>Кол-во часов</FieldName>
          <FieldId>06ff935a-c7fc-4ab3-8ad7-9012927cc651</FieldId>
          <FieldVal>'.$info['time'].'</FieldVal>
        </FieldWrapper>
        <FieldWrapper>
          <FieldName>Что сделано/Риски</FieldName>
          <FieldId>dfa2cb7e-fb2c-4b8b-93a5-443190ecf6fd</FieldId>
          <FieldVal>'.$info['comment'].'</FieldVal>
          <FieldType>string</FieldType>
        </FieldWrapper>
      </lstParams>
      <record>
        <Date>'.$info['date'].'</Date>
      </record>
    </ChangeDirectoryRecord>
  </soap:Body>
</soap:Envelope>';
              break;

              case 'DeleteDirectoryRecord':
                $outXml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <DeleteDirectoryRecord xmlns="http://tempuri.org/">
      <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>
      <directoryRecordId>'.$info['directoryRecordId'].'</directoryRecordId>
    </DeleteDirectoryRecord>
  </soap:Body>
</soap:Envelope>';
              break;
        }

        return $outXml;
    }

    
}