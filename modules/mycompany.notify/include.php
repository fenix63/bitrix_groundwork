<?php

if (!\CJSCore::IsExtRegistered('customRows')) {
    \CJSCore::RegisterExt('customRows', [
        'js' => '/local/modules/mycompany.notify/assets/js/custom.js'
    ]);
}