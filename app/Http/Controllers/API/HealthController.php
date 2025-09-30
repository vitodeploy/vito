<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;

class HealthController extends Controller
{
    #[Get('api/health', name: 'api.health')]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'version' => config('app.version'),
        ]);
    }
}
