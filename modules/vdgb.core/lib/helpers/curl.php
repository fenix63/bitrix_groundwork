<?php

namespace Vdgb\Core\Helpers;

use Vdgb\Core\Debug;

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


        return ['response'=> $response,'info'=> $info];
    }

    public static function sendJSONRequest(string $url, string $json)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return ['response'=> $response,'info'=> $info];
    }

    public static function sendJSONRequestTest()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://advanta-app.rgaz.ru:442/api/queries/get',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "DataSourceKey": "get_customer",
            "Parameters": { "inn": 7743529527 },
            
            "PageSize": 1
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Cookie: .AspNet.ApplicationCookie=cgUryCS-wXnY8G6tpQtnkFC41m_PI9RugZKGGDQfW4l38K1S1P2yJRx4SO6wGexvrWU9cSH_h_F39axmwpQNecTFBKLHOJCMGmwEerh9a3CB0lQymA5Oyy5vi4O17T75wa2ki3Zhwrg-S0s6f380_RpqoNVlCw58b1rm3FRyNdVLCBbpU-XrewIVry7y7llIuZr-nNcjD27qNRt9c8uoG5oD9NI3Ov6FJ45iEWFBSXRiyxBdW9hTJKmKa537nMhAdQiu9r_2ZT7ebEE-v9sQJB_F8N6QN5hbZgr28_M--Djqq3z6qrIHA3_rQCKzLgoWvTm4AOAqoT9peSTGb9WJakzpOUn5aTtvSxKFfHgYgwY_AyaMWVkGWPc5XrAKW1mLOsbARE1BYRBydIoHzorJEWSDICwVyIzJ3b-tpeJ75zDEPZz0Wfb6XKNvmjbsqZhvQcr4GF1Rd4xev8W4P7hnzqZ4UKaG2NMnPNWqtZoGua5FfB_3PSKB2OpXuD1TbodvUKgeLUMol5damVMM9B8UN3tK9UfZ-IGoYD2oe-WJSxFo-gD--n91BJQLQVj-C0oDuxYl22l2Qj1ALhcZv5ppswhV5AUM4bJHZ3FRvfVRPcxNGrwasqV4tV3uIO-QmK_GaWULAW69Fh9gNakXQzVWXoyTOqszFUaaIOwYvZP3m6BuMmizZPfl3gU2JSWHUDLmK3pTJfE9b22mtE6esFXY02o3FgNE9MmT1GG77LKbork'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public static function sendJSONDataBusRequest(string $url, string $json, string $apiKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,[
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey
        ]);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        Debug::dbgLog($response,'_response_');
        Debug::dbgLog($info,'_info_');

        return ['response'=> $response,'info'=> $info];
    }
}

?>