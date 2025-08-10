<?
namespace Vdgb\Core\Helpers\User;
use Bitrix\Main\Loader;
use Vdgb\Core\Debug;

Loader::includeModule('main');

class UserHelper
{
    public static function getCurrentUserGroupList()
    {
        global $USER;
        $userId = $USER->GetID();
        $groupsIdList = \CUser::GetUserGroup($userId);
        $groupsIdListString = '';
        foreach($groupsIdList as $groupId){
            $groupsIdListString .= $groupId .'|';
        }

        $groupsIdListString = trim($groupsIdListString,'|');


        $filter = [
            'ID' => $groupsIdListString,
            'ACTIVE' => 'Y'
        ];
        
        $groupsInfo = [];
        $by="c_sort";
        $order="desc";

        $rsGroups = \CGroup::GetList($by, $order, $filter);
        while($groupItem = $rsGroups->fetch()){
            $groupsInfo[$groupItem['ID']] = ['STRING_ID'=>$groupItem['STRING_ID'],'NAME'=>$groupItem['NAME']];
        }
        


        return $groupsInfo;
    }

    public static function getUFCodeByXmlId(string $xmlId): string
    {
        $filter = [
            'ENTITY_ID' => 'CRM_DEAL',
            'XML_ID' => $xmlId
        ];
        $ufDBData = \CUserTypeEntity::GetList( array($by=>$order), $filter );

        $ufCode = '';
        if($ufInfo = $ufDBData->fetch()){
            $ufCode = $ufInfo['FIELD_NAME'];
        }

        return $ufCode;
    }

    public static function getEnumXmlIdById(int $userFieldValueId): string
    {
        $dbItems = \CUserFieldEnum::GetList([],['ID' => $userFieldValueId]);
        $ufxmlId = '';
        if($arItem = $dbItems->fetch()){
            $ufxmlId = $arItem['XML_ID'];
        }

        return $ufxmlId;
    }

    public static function getEnumTextValueById(int $userFieldValueId): string
    {
        $dbItems = \CUserFieldEnum::GetList([],['ID' => $userFieldValueId]);
        $ufTextValue = '';
        if($arItem = $dbItems->fetch()){
            $ufTextValue = $arItem['VALUE'];
        }

        return $ufTextValue;
    }

    public static function getUserFieldsById(int $userId, array $fieldsList): array
    {
        $dbUser = \CUser::GetByID($userId);
        $userInfo = [];
        if($user = $dbUser->fetch()){
            foreach($fieldsList as $fieldItem){
                $userInfo[$fieldItem] = $user[$fieldItem];
            }
            
        }

        return $userInfo;
    }

    public static function getUserInfo(int $userId, array $fieldsToSelect): array
    {
        $filter = ['ID'=> $userId];
        
        $dbUsers = \CUser::GetList(($by="id"), ($sort="sort"), $filter,['FIELDS'=> $fieldsToSelect]);
        $info = [];
        
        if($userItem = $dbUsers->fetch()){
            foreach($fieldsToSelect as $fieldItem){
                $info[$fieldItem] = $userItem[$fieldItem];
            }
        }

        return $info;
    }

    public static function getUserInfoByFilter(array $filter)
    {
        $userId = 0;
        $dbUsers = \CUser::GetList(($by="sort"), ($order="desc"), $filter);
        if($user = $dbUsers->fetch()){
            $userId = $user['ID'];
        }

        return $userId;
    }

    public static function getUsersInfoListByFilter(array $filter, array $fieldsToSelect): array
    {
        $usersInfo = [];

        $order = ['sort' => 'asc'];
        $tmp = 'sort';

        $res = \Bitrix\Main\UserTable::getList([
          "select" => $fieldsToSelect,
          "filter" => $filter
        ]);
        while($user = $res->fetch()){
            foreach($fieldsToSelect as $fieldItem){
                $dataItem[$fieldItem] = $user[$fieldItem];
            }
            $usersInfo[] = $dataItem; 
        }

        
        return $usersInfo;
    }



}

?>