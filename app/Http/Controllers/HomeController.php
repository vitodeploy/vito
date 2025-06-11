<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\SiteFeatures\LaravelOctane\Enable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    #[Get('/test')]
    public function test(Request $request): string
    {
        $site = Site::query()->first();

        new Enable($site)->handle($request);

        return 'Laravel Octane enabled for site: ' . $site->name;
    }
}
