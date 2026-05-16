<?php

namespace App\Http\Controllers;

use App\Actions\Ziggy\GetZiggyRoutes;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Where;

class ZiggyRoutesController extends Controller
{
    #[Get('/ziggy/{version}.js', name: 'ziggy.routes')]
    #[Where('version', '[a-f0-9]{1,32}')]
    public function __invoke(GetZiggyRoutes $action): Response
    {
        return response($action->script(), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
