<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings')]
#[Middleware(['auth'])]
class SettingController extends Controller
{
    #[Get('/', name: 'settings')]
    public function index(): RedirectResponse
    {
        return to_route('profile');
    }
}
