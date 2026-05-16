<?php

namespace App\Http\Controllers;

use App\Actions\Bootstrap\GetBootstrap;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;

#[Middleware(['auth'])]
final class BootstrapController extends Controller
{
    #[Get('/bootstrap', name: 'bootstrap.show')]
    public function __invoke(GetBootstrap $getBootstrap): JsonResponse
    {
        return response()->json($getBootstrap->handle());
    }
}
