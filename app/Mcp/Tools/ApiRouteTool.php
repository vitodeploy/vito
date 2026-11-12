<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Exposes one authenticated API route as an MCP tool. Calls are replayed through the
 * HTTP kernel with the caller's token, so the route's middleware, policies and
 * validation decide the outcome exactly as they do for the REST API.
 */
class ApiRouteTool extends Tool
{
    /**
     * @param  array<string, mixed>  $operation  The route's OpenAPI operation, empty when undocumented.
     */
    public function __construct(
        private readonly Route $route,
        private readonly array $operation = [],
    ) {}

    public function name(): string
    {
        return Str::of((string) $this->route->getName())->after('api.')->replace(['.', '-'], '_')->toString();
    }

    public function title(): string
    {
        return $this->operation['summary'] ?? Str::headline($this->name());
    }

    public function description(): string
    {
        $endpoint = $this->method().' /'.$this->route->uri();

        return isset($this->operation['description']) ? $this->operation['description'].' ('.$endpoint.')' : $endpoint;
    }

    public function shouldRegister(): bool
    {
        return $this->isReadOnly() || user()->tokenCan('write');
    }

    /**
     * Write tools omit destructiveHint so clients apply the MCP default (destructive) and ask before running them.
     *
     * @return array<string, bool>
     */
    public function annotations(): array
    {
        return ['readOnlyHint' => $this->isReadOnly()];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [...parent::toArray(), 'inputSchema' => $this->inputSchema()];
    }

    public function handle(Request $request): Response
    {
        $parameters = $request->validate(collect($this->route->parameterNames())
            ->mapWithKeys(fn (string $name): array => [$name => [$this->isOptional($name) ? 'nullable' : 'required', 'alpha_dash:ascii']])
            ->all());
        $arguments = Arr::except($request->all(), $this->route->parameterNames());
        $method = $this->method();
        $original = request();

        $internal = HttpRequest::create(
            route((string) $this->route->getName(), $parameters),
            $method,
            $method === 'GET' ? $arguments : [],
            server: [
                'HTTP_AUTHORIZATION' => (string) $original->header('Authorization'),
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => $original->ip(),
            ],
            content: $method === 'GET' ? null : (string) json_encode((object) $arguments),
        );

        if (app('router')->getRoutes()->match($internal)->getName() !== $this->route->getName()) {
            return Response::error('The given parameters resolve to a different endpoint.');
        }

        try {
            $response = app(Kernel::class)->handle($internal);
        } finally {
            app()->instance('request', $original);
            Facade::clearResolvedInstance('request');
        }

        $content = $response->getContent();
        if ($content === false) {
            ob_start();
            try {
                $response->sendContent();
            } finally {
                $content = (string) ob_get_clean();
            }
        }

        if ($response->isSuccessful()) {
            return Response::text($content !== '' ? $content : 'Done.');
        }

        return Response::error('HTTP '.$response->getStatusCode().': '.$content);
    }

    /**
     * @return array<string, mixed>
     */
    private function inputSchema(): array
    {
        $documented = collect($this->operation['parameters'] ?? [])->where('in', 'path')->keyBy('name');
        $properties = [];
        $required = [];

        foreach ($this->route->parameterNames() as $name) {
            $parameter = $documented->get($name);
            $properties[$name] = $parameter
                ? [...$parameter['schema'], 'description' => $parameter['description'] ?? $name]
                : ['type' => ['integer', 'string'], 'description' => Str::headline($name).' ID'];

            if (! $this->isOptional($name)) {
                $required[] = $name;
            }
        }

        if ($this->isReadOnly() && str_contains((string) json_encode($this->operation['responses'] ?? []), 'PaginatedResponse')) {
            $properties['page'] = ['type' => 'integer', 'minimum' => 1, 'description' => 'Page number'];
        }

        $body = $this->operation['requestBody']['content']['application/json']['schema'] ?? [];
        $properties = [...$properties, ...$body['properties'] ?? []];
        $required = [...$required, ...$body['required'] ?? []];

        return array_filter([
            'type' => 'object',
            'properties' => $properties === [] ? (object) [] : $this->toJsonSchema($properties),
            'required' => $required,
        ], fn (mixed $value): bool => $value !== []);
    }

    /**
     * Drop OpenAPI-only keywords that strict JSON Schema validators reject.
     *
     * @param  array<array-key, mixed>  $schema
     * @return array<array-key, mixed>
     */
    private function toJsonSchema(array $schema): array
    {
        if (($schema['nullable'] ?? false) === true && is_string($schema['type'] ?? null)) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        unset($schema['nullable'], $schema['example']);

        return array_map(fn (mixed $value): mixed => is_array($value) ? $this->toJsonSchema($value) : $value, $schema);
    }

    private function method(): string
    {
        return $this->route->methods()[0];
    }

    private function isReadOnly(): bool
    {
        return $this->method() === 'GET';
    }

    private function isOptional(string $parameter): bool
    {
        return str_contains($this->route->uri(), '{'.$parameter.'?}');
    }
}
