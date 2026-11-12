<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mcp\Servers\VitoServer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Mcp\Server\Middleware\AddWwwAuthenticateHeader;
use Laravel\Mcp\Server\Middleware\ReorderJsonAccept;
use Laravel\Mcp\Server\Middleware\ValidateMcpHeaders;
use Laravel\Mcp\Server\Middleware\ValidateOrigin;
use Laravel\Mcp\Server\Transport\HttpTransport;
use Spatie\RouteAttributes\Attributes\Post;
use Symfony\Component\HttpFoundation\StreamedResponse;

class McpController extends Controller
{
    /**
     * MCP messages use their own limiter; a replayed tool call also counts once against the `api` limiter.
     */
    #[Post('api/mcp', name: 'api.mcp', middleware: [
        ValidateOrigin::class,
        AddWwwAuthenticateHeader::class,
        ReorderJsonAccept::class,
        ValidateMcpHeaders::class,
        'auth:sanctum',
        'ability:read',
        ThrottleRequests::class.':mcp',
    ], withoutMiddleware: ThrottleRequests::class.':api')]
    public function __invoke(Request $request): Response|StreamedResponse
    {
        $transport = new HttpTransport($request);

        app()->make(VitoServer::class, ['transport' => $transport])->start();

        return $transport->run();
    }
}
