<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('invoice-admin-channel', function () {
    // ✅ هذه القناة عامة، نسمح للجميع بالاستماع
    return true;
});
