<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Tests\ArchTestCase;

it('names every application route', function (): void {
    $offenders = [];

    foreach (applicationRoutes() as $route) {
        if ($route->getName() === null) {
            $offenders[] = $route->methods()[0].' /'.$route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

it('gives every route a unique name', function (): void {
    $names = [];

    foreach (applicationRoutes() as $route) {
        if ($route->getName() !== null) {
            $names[] = $route->getName();
        }
    }

    expect(array_values(array_diff_assoc($names, array_unique($names))))->toBe([]);
});

it('authenticates every route that is not deliberately public', function (): void {
    $offenders = [];

    foreach (applicationRoutes() as $route) {
        if (isPublicRoute($route)) {
            continue;
        }

        $middleware = $route->gatherMiddleware();
        $authenticates = array_filter(
            $middleware,
            fn (mixed $item): bool => is_string($item) && Str::startsWith($item, ['auth', 'signed']),
        );

        if ($authenticates === []) {
            $offenders[] = $route->methods()[0].' /'.$route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

it('scopes every api route to a token ability', function (): void {
    $offenders = [];

    foreach (applicationRoutes() as $route) {
        if (! Str::startsWith($route->uri(), 'api/') || isPublicRoute($route)) {
            continue;
        }

        $middleware = array_filter($route->gatherMiddleware(), 'is_string');

        if (! Str::contains(implode(' ', $middleware), 'ability:')) {
            $offenders[] = $route->methods()[0].' /'.$route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

it('scopes every project api route to the project boundary', function (): void {
    $offenders = [];

    foreach (applicationRoutes() as $route) {
        if (! Str::startsWith($route->uri(), 'api/projects/{project}')) {
            continue;
        }

        $middleware = array_filter($route->gatherMiddleware(), 'is_string');

        if (! in_array('can-see-project', $middleware, true)) {
            $offenders[] = $route->methods()[0].' /'.$route->uri();
        }
    }

    expect($offenders)->toBe([]);
});

/**
 * @return array<int, Route>
 */
function applicationRoutes(): array
{
    return array_values(array_filter(
        RouteFacade::getRoutes()->getRoutes(),
        fn (Route $route): bool => ! Str::startsWith($route->uri(), ['_debugbar', 'storage/']),
    ));
}

function isPublicRoute(Route $route): bool
{
    $uri = $route->uri();

    if ($uri === '/') {
        return true;
    }

    foreach (ArchTestCase::except('routing.public-endpoints') as $prefix) {
        if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
            return true;
        }
    }

    return false;
}
