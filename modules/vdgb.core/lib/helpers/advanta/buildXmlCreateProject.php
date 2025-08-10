<?

namespace Vdgb\Core\Helpers\Advanta;

use Vdgb\Core\Helpers\Advanta\Strategy;

class BuildXmlCreateProject implements Strategy
{
    //Ответственный от отдела продаж
    const PROJECT_RESPONSIBLE_ID = '0d082b1c-29ca-493d-8027-72d50820c2d8';

    public static function buildXmlToRequest(string $sessId, array $presaleInfo)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <CreateProject xmlns="http://streamline/">
      <newProject>
        <ASPNETSessionId>'.$sessId.'</ASPNETSessionId>

        <!--Родительский проект (РН)-->
        <ParentProjectId>'.$presaleInfo['parentProjectUID'].'</ParentProjectId>

        <ProjectTypeId>95fa1505-98b1-4561-93b3-1a383e84292a</ProjectTypeId>

        <!--547cdd10-fecd-4f0a-b360-f5df42ac8162  Долматова Марина - Руководитель проекта-->
        <ProjectOwnerId>'.$presaleInfo['managerUID'].'</ProjectOwnerId>

        <!--ProjectResponsibleId  исполнитель проекта 0d082b1c-29ca-493d-8027-72d50820c2d8 Андрей Кобзев-->
        <ProjectResponsibleId>'.self::PROJECT_RESPONSIBLE_ID.'</ProjectResponsibleId>

        <ProjectName>'.$presaleInfo['name'].'</ProjectName>

        <PlannedStartDate>'.$presaleInfo['startDate'].'</PlannedStartDate>
        <PlannedEndDate>'.$presaleInfo['finishDate'].'</PlannedEndDate>

        <Fields>
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
          </FieldWrapper>


          <FieldWrapper>
            <FieldName>Ссылка на Б24</FieldName>
            <FieldId>e2904601-43ed-4576-8405-4429aa5d0ac8</FieldId>
            <FieldVal>
                &lt;p&gt;&lt;a href="'.$presaleInfo['b24Link'].'"&gt;'.$presaleInfo['b24Link'].'&lt;/a&gt;&lt;/p&gt;
            </FieldVal>
            <FieldType>Html</FieldType>
          </FieldWrapper>
          <FieldWrapper>
            <FieldName>Предварительная стоимость проекта, руб. с НДС</FieldName>
            <FieldId>6c93f643-9af0-4354-99b1-812c9ca5ae73</FieldId>
            <FieldVal>'.$presaleInfo['dealSum'].'</FieldVal>
            <FieldType>Numeric</FieldType>
          </FieldWrapper>
          <FieldWrapper>
            <FieldName>Скрытое поле</FieldName>
            <FieldId>646a2a1b-4a5d-468d-aa5b-59f8713c7994</FieldId>
            <FieldVal>'.$presaleInfo['companyINN'].'</FieldVal>
            <FieldType>Numeric</FieldType>
          </FieldWrapper>
        </Fields>


      </newProject>
    </CreateProject>
  </soap:Body>
</soap:Envelope>';
    }
} 