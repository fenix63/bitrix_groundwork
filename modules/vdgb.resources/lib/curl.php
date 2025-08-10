<?php

namespace Vdgb\Resources;

use Vdgb\Resources\Debug;

class Curl
{

    public static function sendRequest(string $url, array $headersParams, string $methodName, string $xml)
    {
        $headers = [];
        foreach($headersParams as $paramItem){
            array_push($headers, $paramItem);    
        }
        //array_push($headers, "Content-Type: text/xml; charset=utf-8");
        //array_push($headers, "SOAPAction: http://streamline/".$methodName);
      

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);


        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        //Debug::dbgLog($response,'_response_');


        return ['response'=> $response,'info'=> $info];
    }
}

?>