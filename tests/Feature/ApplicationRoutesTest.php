<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

uses(RefreshDatabase::class);

function applicationRouteHasAuthorization(string $source, string $method): bool
{
    $tree = (new ParserFactory)->createForHostVersion()->parse($source);
    $finder = new NodeFinder;
    $action = $finder->findFirst($tree, fn (Node $node): bool => $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === $method);

    return $action !== null && $finder->findFirst($action->stmts ?? [], fn (Node $node): bool => $node instanceof Node\Expr\MethodCall
        && $node->var instanceof Node\Expr\Variable && $node->var->name === 'this'
        && $node->name instanceof Node\Identifier && $node->name->toString() === 'authorize') !== null;
}

test('every applications route is named scoped and protected by middleware and a policy call', function () {
    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_starts_with($route->uri(), 'applications'));
    expect($routes)->not->toBeEmpty();
    foreach ($routes as $route) {
        expect($route->gatherMiddleware(), $route->uri())->toContain('auth', 'has-project');
        expect($route->getName(), $route->uri())->not->toBeNull();
        if (str_contains($route->uri(), '{environment}')) {
            expect($route->enforcesScopedBindings(), $route->uri())->toBeTrue();
        }
        $method = new ReflectionMethod($route->getControllerClass(), $route->getActionMethod());
        expect(applicationRouteHasAuthorization(file_get_contents($method->getFileName()), $method->getName()), $route->uri())->toBeTrue();
        expect($route->uri())->not->toContain('topology');
    }
});

test('route authorization check rejects missing commented or unrelated authorization calls', function () {
    $source = '<?php class Example { public function show() { /* $this->authorize("view"); */ } public function other() { $this->authorize("view"); } }';
    expect(applicationRouteHasAuthorization($source, 'show'))->toBeFalse()
        ->and(applicationRouteHasAuthorization($source, 'other'))->toBeTrue();
});
