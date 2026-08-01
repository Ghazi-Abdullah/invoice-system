<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('invoice-admin-channel', function ($user) {
    return $user !== null;
});
