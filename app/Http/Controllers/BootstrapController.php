<?php

namespace App\Http\Controllers;

use App\Actions\Bootstrap\GetBootstrap;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;

class BootstrapController extends Controller
{
    #[Get('/bootstrap', name: 'bootstrap.show', middleware: 'auth')]
    public function __invoke(GetBootstrap $getBootstrap): JsonResponse
    {
        return response()->json($getBootstrap->handle());
    }
}
