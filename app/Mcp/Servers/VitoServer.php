<?php

namespace App\Mcp\Servers;

use App\Actions\ApiDocs\GetOpenApiSpec;
use App\Mcp\Tools\ApiRouteTool;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Laravel\Mcp\Server;

/**
 * Mirrors the REST API: every token-authenticated `api.*` route becomes a tool,
 * described by its OpenAPI operation, so new endpoints reach MCP clients unchanged.
 */
class VitoServer extends Server
{
    protected string $name = 'Vito';

    protected string $instructions = <<<'MARKDOWN'
        Manage VitoDeploy servers, sites, databases, services, DNS and providers.
        Each tool maps to one endpoint of the Vito REST API and returns its JSON response.
        Resource IDs are numeric; list tools (e.g. `projects`, `projects_servers`) return them and accept a `page` argument.
        Write tools are only listed for API keys with write permission, and project-scoped keys can only reach their projects.
        MARKDOWN;

    public int $defaultPaginationLength = 500;

    public int $maxPaginationLength = 500;

    protected function boot(): void
    {
        $this->version = (string) config('app.version');

        $operations = [];
        foreach (app(GetOpenApiSpec::class)->handle()['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $operations[$this->operationKey(strtoupper($method), $path)] = $operation;
            }
        }

        $this->tools = collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => $this->isExposed($route))
            ->map(fn (Route $route): ApiRouteTool => new ApiRouteTool(
                $route,
                $operations[$this->operationKey($route->methods()[0], $route->uri())]
                    ?? $operations[$this->operationKey($route->methods()[0], (string) preg_replace('#/\{[^}]+\?}#', '', $route->uri()))]
                    ?? [],
            ))
            ->values()
            ->all();
    }

    private function isExposed(Route $route): bool
    {
        $name = (string) $route->getName();

        return str_starts_with($name, 'api.')
            && $name !== 'api.mcp'
            && in_array('auth:sanctum', $route->gatherMiddleware(), true);
    }

    private function operationKey(string $method, string $uri): string
    {
        return $method.' '.preg_replace('/\{[^}]+}/', '{}', trim($uri, '/'));
    }
}
