<?php

namespace Vdgb\Resources\Events;
use Vdgb\Core\Debug;

Loader::includeModule("tasks");
Loader::includeModule("timeman");

class TimemanEvents
{
    public static function onAfterUpdateHandler()
    {
        Debug::dbgLog('onAfterUpdateHandler','_onAfterUpdateHandler_');
    }   
}