<?php


namespace MyCompany\Notify\Agents;

use Bitrix\Main\Loader;

Loader::includeModule('mycompany.rest');
Loader::includeModule('mycompany.notify');
Loader::includeModule('highloadblock');

use MyCompany\Rest\Model\Debuger;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use  MyCompany\Notify\HLblockHelper;
use MyCompany\Notify\CheckPointsNotify;
use MyCompany\Notify\ResultNotify;
use MyCompany\Notify\FinanceNotify;


class NotifyRule
{
    /**
     * Метод запускается 1 раз в день, проходится по всем правилам уведомлений,
     * и если текущая дата равна, или больше чем дата начала отправки уведомлений, то добавляем новое уведомление в HL-блок уведомлений
     * @return string
     */
    const HL_BLOCK_CODE_NOTIFY_RULES = 'NotifyRules';

    public static function createNotify(): string
    {
        // Указываем ID нашего highloadblock блока к которому будет делать запросы.
        $hlbl = HLblockHelper::getHLblockIdByCode(self::HL_BLOCK_CODE_NOTIFY_RULES);
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();
        $rsData = $entityDataClass::getList([
            "select" => ["*"],
            "filter" => []
        ]);

        $hlElementItems = [];
        while ($arData = $rsData->Fetch()) {
            $hlElementItems[] = $arData;
        }
        self::createNotifyItems($hlElementItems);

        return 'NotifyRule::createNotify();';
    }

    public static function createNotifyItems(array $hlElementItems)
    {
        $notifyTopicTextList = HLblockHelper::getUserFieldEnumList('NotifyRules', 'UF_NOTIFY_TOPIC');
        foreach ($hlElementItems as $HLItem) {
            $topicText = $notifyTopicTextList[$HLItem['UF_NOTIFY_TOPIC']];
            switch ($topicText['VALUE']) {
                case 'Дата достижения контрольной точки':
                    //Debuger::dbgLog('Мы тут: ' . __FILE__ . ':' . __LINE__, '_NOTIFY_AGENT');
                    CheckPointsNotify::createCheckPointNotify($HLItem);
                    break;
                case 'Дата достижения результата':
                    ResultNotify::createResultNotify($HLItem);
                    break;
                case 'Последняя дата месяца':
                    FinanceNotify::createFinanceNotify($HLItem);
                    break;
            }
        }
    }
}
