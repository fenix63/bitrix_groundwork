<?php


namespace MyCompany\WebService\VS\Gisgmp;


class ErrorHandler
{
    //TODO Метод ещё не реализован
    public static function checkForErrors(\SimpleXMLElement $input): string
    {
        $nodeToCheck = $input->soapBody->GetResponseResponse->ResponseMessage->Response->SenderProvidedResponseData->AsyncProcessingStatus->SmevFault;
        if ($nodeToCheck != null) {
            $json = json_encode($nodeToCheck);
            $responseData = json_decode($json, true);
            return 'Ошибка СМЭВ';
        }
    }
}
