<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'version' => config('app.version'),
        ]);
    }
}
