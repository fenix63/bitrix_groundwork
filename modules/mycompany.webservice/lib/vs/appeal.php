<?php


namespace MyCompany\WebService\VS;

use MyCompany\WebService\Helper;

/**Обращения граждан
 * Class Appeal
 * @package MyCompany\WebService\VS
 */
class Appeal implements \MyCompany\WebService\VS
{
    const IBLOCK_CODE = 'appeal';
    const WORKFLOW_TEMPLATE_NAME = 'Обработка обращений граждан';

    public $messageId = '';
    public $vsCode = 'IQEpguMyCompanyAppealsSpgu';
    private $orderData = [];
    public $nodeValue = '';
    private $mess = [
        'OrderId' => 'Номер заявления на ЕПГУ',
        'Department' => 'Код ведомства',
        'ServiceCode' => 'Код гос. услуги, в рамках оказания которой осуществляется информационный обмен',
        'TargetCode' => 'Код цели',
        'StatementDate' => 'Дата заявления на ЕПГУ',
        'ZayavitelqType' => 'Тип заявителя (ФЛ/Руководитель ЮЛ/Сотрудник ЮЛ/Руководитель ИП/Сотрудник ИП)',
        'ZayavlenieType' => 'Тип заявления (Обращение/Жалоба/Предложение)',
        'SelectType' => 'Варианты получения ответа (куда направить результат оказания услуги)',
        'ZayavitelqInfo' => 'Данные заявителя (ФЛ/Руководитель ЮЛ/Сотрудник ЮЛ/Руководитель ИП/Сотрудник ИП)',
        'IQEpguMyCompanyAppealsSpguUl' => 'Данные о ЮЛ',
        'ulFullName' => 'Полное наименование ЮЛ',
        'ulShortName' => 'Краткое наименование ЮЛ',
        'ulHeadSurname' => 'Фамилия руководителя',
        'ulHeadName' => 'Имя руководителя',
        'ulHeadPatronymic' => 'Отчество руководителя',
        'ulEmail' => 'Адрес электронной почты ЮЛ',
        'SotrudnikSurname' => 'Фамилия сотрудника',
        'SotrudnikName' => 'Имя сотрудника',
        'SotrudnikPatronymic' => 'Отчество сотрудника (при наличии)',
        'SotrudnikBirthdate' => 'Дата рождения сотрудника',
        'TematikaObr' => 'Тематика обращения',
        'SutqObr' => 'Краткое содержание',
        'TextObr' => 'Текст обращения',
        'AppliedDocuments' => 'Вложения',
        'CodeDocument' => 'Код документа',
        'Name' => 'Имя файла документа',
        'Type' => 'MIME-тип контента',

        //Юр лица
        'IQEpguMyCompanyAppealsSpguIpType' => 'Данные ЮЛ (ИП)',
        'ipFullName' => 'Полное наименование ИП',
        'ipShortName' => 'Краткое наименование ИП',
        'OGRN' => 'ОГРНИП',
        'ipEmail' => 'Адрес электронной почты ИП',

        //Данные сотрудника ИП
        'SotrudnikIpSurname' => 'Фамилия сотрудника',
        'SotrudnikIpName' => 'Имя сотрудника',
        'SotrudnikIpPatronymic' => 'Отчество сотрудника (при наличии)',
        'SotrudnikIpBirthdate' => 'Дата рождения сотрудника',
    ];

    public function add(): int
    {
        $data = $this->orderData;
        $el = new \CIblockElement();
        $data['PROPERTY_VALUES']['StatusSended'] = 0;
        $data['PROPERTY_VALUES']['ITERATOR'] = 0;
        $newOrderId = $el->add($data);

        if ($newOrderId) {
            //$this->setDocument($newOrderId);
            echo $newOrderId;
        } else {
            throw new \Exception('Ошибка создания элемента инфоблока:' . $el->LAST_ERROR);
        }

        return $newOrderId;
    }

    public function setData(array $request)
    {
        $this->messageId = trim($this->getNodeFromXmlArray($request, 'MessageID'));
        if (\MyCompany\WebService\Helper::checkUniqueMessageId(self::getIblockId(), $this->messageId)) {
            die('MessageId не уникальный');
        }
        $this->replyTo = $this->getNodeFromXmlArray($request, 'ReplyTo');
        $this->messagePrimaryContent = $this->getNodeFromXmlArray($request, 'MessagePrimaryContent');
        //$this->celqUslugi = array_key_first($this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['CelqUslugi']);

        $this->orderData["IBLOCK_ID"] = $this->getIblockId();
        $this->orderData["NAME"] = $this->messagePrimaryContent[$this->vsCode]["OrderId"];
        $this->orderData["PROPERTY_ORDER_ID"] = $this->orderData["NAME"];
        $this->orderData["XML_ID"] = $this->messageId;
        $this->orderData["CODE"] = $this->messageId;
        $this->orderData['PREVIEW_TEXT'] = $this->celqUslugi;

        /* проверяем заявителя */
        if ($this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqType']){
            $this->ZayavitelqType = $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqType'];
        } else {
            if (substr(array_key_first($this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqInfo']), -2) == 'Fl'){
                $this->ZayavitelqType = 'Физическое лицо';
            } else {
                $this->ZayavitelqType = '';
            }
        }

        $zayavitelqInfo = $this->messagePrimaryContent[array_key_first($this->messagePrimaryContent)]['ZayavitelqInfo'];

        //TODO Будет баг печтаной формы, если у заявителя отсутствует отчество
        $this->zayavitelqInfo = $zayavitelqInfo[array_key_first($zayavitelqInfo)];

        $orderDataProps = $this->getOrderDataProps($this->messagePrimaryContent);
        $orderDataProps['ZayavitelqInfo'].=$orderDataProps['CelqUslugiData'];
        unset($orderDataProps['CelqUslugiData']);

        /* Вложения */
        $attachmentsData = [];
        $appliedDocuments = $this->getNodeFromXmlArray($request, 'AppliedDocuments');
        //Сделать такой же блок получения вложений из тега (RefAttachmentHeaderList - узел в XML)
        if(is_array($appliedDocuments)){
            if(is_array($appliedDocuments["AppliedDocument"])){
                foreach($appliedDocuments["AppliedDocument"] as $docData){
                    if(is_array($docData)){
                        $attachmentsData[] = $docData;
                    }else{
                        $attachmentsData[] = $appliedDocuments["AppliedDocument"];
                        break;
                    }
                }
            }
        }

        if(empty($appliedDocuments)){
            $FSAttachmentsList = $this->getNodeFromXmlArray($request, 'FSAttachmentsList');
            if(is_array($FSAttachmentsList)){
                if(is_array($FSAttachmentsList["FSAttachment"])){
                    foreach($FSAttachmentsList["FSAttachment"] as $docData){
                        if(is_array($docData)){
                            $attachmentsData[] = $docData;
                        }else{
                            $attachmentsData[] = $FSAttachmentsList["FileName"];
                            break;
                        }
                    }
                }
            }
        }

        $orderDataProps["AppliedDocuments"] = \MyCompany\WebService\Attachments::getAttachmentsData($this->messageId, $attachmentsData);
        $this->nodeValue = '';
        //$fSAttachment = $this->getNodeFromXmlArray($request, 'FSAttachment');
        if (!empty($attachmentsData) && (!$orderDataProps["AppliedDocuments"])) {
            throw new \Exception('Вложения, ожидаемые в виде сведений, не обнаружены в системе:');
        }

        $this->orderData["PROPERTY_VALUES"] = $orderDataProps;
    }

    /**
     * Обходим messagePrimaryContent и заполняем $orderDataProps для сохранения в елементе ИБ
     * @param mixed $messagePrimaryContent
     *
     * @return array
     */
    private function getOrderDataProps($messagePrimaryContent)
    {
        $orderDataProps = [];
        $orderDataProps['ReplyTo'] = \MyCompany\WebService\Helper::removeSpacesAndHyphens($this->replyTo);
        $orderDataProps['Request'] = file_get_contents('php://input');


        $this->nodeValue = '';
        $orderDataProps['Department'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'Department');
        $this->nodeValue = '';
        $orderDataProps['ServiceCode'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ServiceCode');
        $this->nodeValue = '';
        $orderDataProps['TargetCode'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'TargetCode');
        $this->nodeValue = '';
        $orderDataProps['StatementDate'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'StatementDate');
        $this->nodeValue = '';
        $orderDataProps['ORDER_ID'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'OrderId');
        $this->nodeValue = '';

        $orderDataProps['flSurname'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'flSurname');
        $this->nodeValue = '';
        $orderDataProps['flName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'flName');
        $this->nodeValue = '';
        $orderDataProps['flPatronymic'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'flPatronymic');
        $this->nodeValue = '';
        $orderDataProps['flBirthdate'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'flBirthdate');
        $this->nodeValue = '';

        $this->nodeValue = '';
        $orderDataProps['flEmail'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'flEmail');

        $this->nodeValue = '';
        $orderDataProps['ulFullName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ulFullName');

        $this->nodeValue = '';
        $orderDataProps['ulShortName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ulShortName');
        $this->nodeValue = '';
        $orderDataProps['ulHeadSurname'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ulHeadSurname');
        $this->nodeValue = '';
        $orderDataProps['ulHeadName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ulHeadName');

        $this->nodeValue = '';
        $orderDataProps['ipFullName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ipFullName');
        $this->nodeValue = '';
        $orderDataProps['ipShortName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'ipShortName');
        $this->nodeValue = '';
        $orderDataProps['OGRN'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'OGRN');

        $this->nodeValue = '';
        $orderDataProps['SotrudnikIpSurname'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikIpSurname');
        $this->nodeValue = '';
        $orderDataProps['SotrudnikIpName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikIpName');
        $this->nodeValue = '';
        $orderDataProps['SotrudnikIpPatronymic'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikIpPatronymic');
        $this->nodeValue = '';
        $orderDataProps['SotrudnikIpBirthdate'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikIpBirthdate');

        $this->nodeValue = '';
        $orderDataProps['SotrudnikSurname'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikSurname');
        $this->nodeValue = '';
        $orderDataProps['SotrudnikName'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikName');
        $this->nodeValue = '';
        $orderDataProps['SotrudnikPatronymic'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikPatronymic');
        $this->nodeValue = '';
        $orderDataProps['SotrudnikBirthdate'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SotrudnikBirthdate');



        $orderDataProps['ZayavitelqType'] = Helper::getEnumIdByTextValue(
            Helper::getIblockIdByCode('appeal'),
            'ZayavitelqType',
            $this->getNodeFromXmlArray($messagePrimaryContent, 'ZayavitelqType')
        );

        $orderDataProps['ZAYAVLENIE_TYPE'] = Helper::getEnumIdByTextValue(
            Helper::getIblockIdByCode('appeal'),
            'ZAYAVLENIE_TYPE',
            $this->getNodeFromXmlArray($messagePrimaryContent, 'ZayavlenieType')
        );

        $orderDataProps['TEXT_OBR'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'TextObr');
        $orderDataProps['TEMATIKA_OBR'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'TematikaObr');
        $orderDataProps['SUTQ_OBR'] = $this->getNodeFromXmlArray($messagePrimaryContent, 'SutqObr');



        /* IQEpguMyCompanyAppealsSpguFl */
        $IQEpguMyCompanyAppealsSpguFl = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyAppealsSpguFl');
        if (is_array($IQEpguMyCompanyAppealsSpguFl)) {
            $orderDataProps['IQEpguMyCompanyAppealsSpguFl'] = "<b>Физическое лицо:</b><br>";
            foreach ($IQEpguMyCompanyAppealsSpguFl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyAppealsSpguFl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyAppealsSpguFl'] .= "</u>" . ": " . $val . "<br>";
            }
        }
        $this->nodeValue = '';
        /* IQEpguMyCompanyAcrSpguUl */
        /*$iQEpguMyCompanyExpSpguUl = $this->getNodeFromXmlArray($messagePrimaryContent, 'IQEpguMyCompanyExpSpguUl');
        if (is_array($iQEpguMyCompanyExpSpguUl)) {
            $orderDataProps['IQEpguMyCompanyExpSpguUl'] = "<b>Юридическое лицо:</b><br>";
            foreach ($iQEpguMyCompanyExpSpguUl as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['IQEpguMyCompanyExpSpguUl'] .= "<u>" . (($this->mess[$key]) ?? $key);
                $orderDataProps['IQEpguMyCompanyExpSpguUl'] .= "</u>" . ": " . $val . "<br>";
            }
        }
        $orderDataProps['ZayavitelqInfo'] = ($orderDataProps['IQEpguMyCompanyExpSpguFl']) ?? $orderDataProps['IQEpguMyCompanyExpSpguUl'];

        $orderDataProps['SelectType'] = \MyCompany\WebService\Helper::getSelectType($this->getNodeFromXmlArray($this->messagePrimaryContent, 'SelectType'));
        */

        /* CelqUslugi */
        /*$CelqUslugi = $this->getNodeFromXmlArray($messagePrimaryContent, 'CelqUslugi');
        if (!empty($CelqUslugi)) {
            foreach ($CelqUslugi as $key => $val) {
                if(empty($val)){continue;}
                $orderDataProps['CelqUslugi'] = $this->mess[$key] ?? $key;
                $orderDataProps['CelqUslugiData'] = '<b>Предоставленные данные для оказания услуги:</b><br \>';
                foreach ($val as $key_2 => $val_2) {
                    if(empty($val_2)){continue;}
                    if (is_array($val_2)) {
                        foreach ($val_2 as $key_3 => $val_3) {
                            if(empty($val_3)){continue;}
                            $orderDataProps['CelqUslugiData'] .= "<u>" . (($this->mess[$key_3]) ?? $key_3);
                            $orderDataProps['CelqUslugiData'] .= "</u>: " . $val_3 . "<br \>";
                        }
                    } else {
                        $orderDataProps['CelqUslugiData'] .= "<u>" . (($this->mess[$key_2]) ?? $key_2);
                        $orderDataProps['CelqUslugiData'] .= "</u>: " . $val_2 . "<br \>";
                    }
                }
                unset($val);
            }
        }
        */

        //unset($key, $key_2, $val_2);
        //$orderDataProps['CelqUslugi'] = \MyCompany\WebService\Helper::removeSpacesAndHyphens($orderDataProps['CelqUslugi']);

        return $orderDataProps;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getIblockId(): int
    {
        $result = \Bitrix\Iblock\IblockTable::getRow([
            'filter' => ['=CODE' => static::IBLOCK_CODE],
            'select' => ['ID']
        ]);
        if (!empty($result)) {
            return (int)$result['ID'];
        }

        return 0;
    }

    public function getWorkflowId(): int
    {
        $iblockId = self::getIblockId();
        $workFlowTemplateName = self::WORKFLOW_TEMPLATE_NAME;
        return $this->getWorkFlowTemplateId($iblockId, $workFlowTemplateName);
    }

    private function getWorkFlowTemplateId(int $iblockId, string $templateName)
    {
        \Bitrix\Main\Loader::includeModule("bizproc");
        $documentType = \CBPHelper::ParseDocumentId(['iblock', 'CIBlockDocument','iblock_'.$iblockId]);
        $rows = \Bitrix\Bizproc\WorkflowTemplateTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=MODULE_ID' => $documentType[0],
                '=ENTITY' => $documentType[1],
                '=DOCUMENT_TYPE' => $documentType[2],
                '=NAME' => $templateName
            ],
        ])->fetchAll();


        return array_column($rows, 'ID')[0];
    }

    public function searchElement()
    {

    }

    public function updateElement()
    {

    }

    private function getNodeFromXmlArray(array $xmlArray, string $nodeName)
    {
        //$resultNodeValue=($nodeValue)?$nodeValue:false;
        foreach ($xmlArray as $key => $value) {
            if ($key === $nodeName) {
                $this->nodeValue = $value;
                break;
            } else {
                if (gettype($value) == 'array') {
                    $this->getNodeFromXmlArray($value, $nodeName);
                }
            }
        }

        return $this->nodeValue;
    }
}
