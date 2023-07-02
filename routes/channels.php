<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('app', function (User $user) {
    return $user && $user->id;
});
