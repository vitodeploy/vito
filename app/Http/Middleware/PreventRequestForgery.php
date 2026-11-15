<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * @var array<int, string>
     */
    protected $except = [
        'api/webhooks/github-app',
    ];
}
