<?php

namespace App\Http\Controllers;

use App\Actions\Ziggy\GetZiggyRoutes;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Where;

/**
 * Intentionally unauthenticated. Guest pages (login, forgot-password, etc.)
 * call `route()` and need `window.route` defined; gating this endpoint behind
 * `auth` would redirect those fetches to /login, returning HTML as JS and
 * breaking guest flows. The route catalogue was already publicly exposed via
 * the previous `@routes` Blade directive — this preserves parity.
 *
 * If finer-grained exposure is needed later, configure `config/ziggy.php`
 * with `only` / `except` filters rather than middleware.
 */
final class ZiggyRoutesController extends Controller
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
