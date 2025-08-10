<?
namespace Vdgb\Resources\Helpers\User;
use Bitrix\Main\Loader;
use Vdgb\Resources\Debug;

Loader::includeModule('main');

class UserHelper
{
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

    public static function getUserUIDByEmail(string $targetEmail, array $usersList)
    {
        foreach($usersList as $userItem){
            if($userItem['EMail']!=$targetEmail)
                continue;
            else
                return $userItem['UID'];
        }

    }

    public static function getCurUserUID(array $allAdvantaUsers): string
    {
        global $USER;
        $userId = $USER->GetID();
        $curUserEmail = self::getUserInfo($userId,['EMAIL'])['EMAIL'];
        $curUserUID = self::getUserUIDByEmail($curUserEmail, $allAdvantaUsers);

        return $curUserUID;
    }

    public static function getUserIdByEmail(string $userEmail): int
    {
        $userId = 0;
        $filter = [
            'EMAIL' => $userEmail
        ];
        $result = \Bitrix\Main\UserTable::getList([
            'select' => ['ID'],
            'filter' => ['EMAIL' => $userEmail, 'ACTIVE' => 'Y']
        ])->fetch();

        $userId = $result['ID'];

        return $userId;
    }
    
}