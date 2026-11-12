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
     * Throttling is left to the API route each tool call is replayed through, so a call counts once.
     */
    #[Post('api/mcp', name: 'api.mcp', middleware: [
        ValidateOrigin::class,
        AddWwwAuthenticateHeader::class,
        ReorderJsonAccept::class,
        ValidateMcpHeaders::class,
        'auth:sanctum',
        'ability:read',
    ], withoutMiddleware: ThrottleRequests::class.':api')]
    public function __invoke(Request $request): Response|StreamedResponse
    {
        $transport = new HttpTransport($request);

        app()->make(VitoServer::class, ['transport' => $transport])->start();

        return $transport->run();
    }
}
