<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Spatie\RouteAttributes\Attributes\Get;

#[Group(name: 'general')]
class HealthController extends Controller
{
    #[Get('api/health', name: 'api.health')]
    #[Unauthenticated]
    #[Endpoint(title: 'health-check')]
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'version' => config('app.version'),
        ]);
    }
}
