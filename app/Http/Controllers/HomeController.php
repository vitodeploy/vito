<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Get;

class HomeController extends Controller
{
    #[Get('/', name: 'home')]
    public function __invoke(): RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('servers');
        }

        return redirect()->route('login');
    }
}
