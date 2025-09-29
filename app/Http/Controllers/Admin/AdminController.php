<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('admin')]
#[Middleware(['auth', 'must-be-admin'])]
class AdminController extends Controller
{
    #[Get('/', name: 'admin')]
    public function index(): RedirectResponse
    {
        return to_route('users');
    }
}
