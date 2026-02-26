<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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

    #[Get('up', name: 'up')]
    public function up(): Response
    {
        return response('ok', 200);
    }
}
