<?php

namespace App\Http\Controllers;

use App\Actions\Script\CreateScript;
use App\Actions\Script\EditScript;
use App\Actions\Script\ExecuteScript;
use App\Http\Resources\ScriptExecutionResource;
use App\Http\Resources\ScriptResource;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('scripts')]
#[Middleware(['auth'])]
class ScriptController extends Controller
{
    #[Get('/', name: 'scripts')]
    public function index(): Response
    {
        $this->authorize('viewAny', Script::class);

        return Inertia::render('scripts/index', [
            'scripts' => ScriptResource::collection(user()->scripts()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'scripts.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', Script::class);

        return ScriptResource::collection(user()->scripts()->get());
    }

    #[Get('/{script}', name: 'scripts.show')]
    public function show(Script $script): Response
    {
        $this->authorize('view', $script);

        return Inertia::render('scripts/show', [
            'script' => new ScriptResource($script),
            'executions' => ScriptExecutionResource::collection(
                $script->executions()->latest()->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Post('/', name: 'scripts.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Script::class);

        app(CreateScript::class)->create(user(), $request->input());

        return back()->with('success', 'Script created.');
    }

    #[Put('/{script}', name: 'scripts.update')]
    public function update(Script $script, Request $request): RedirectResponse
    {
        $this->authorize('update', $script);

        app(EditScript::class)->edit($script, user(), $request->input());

        return back()->with('success', 'Script updated.');
    }

    #[Delete('/{script}', name: 'scripts.destroy')]
    public function destroy(Script $script): RedirectResponse
    {
        $this->authorize('delete', $script);

        $script->delete();

        return back()->with('success', 'Script deleted.');
    }

    #[Post('/{script}/execute', name: 'scripts.execute')]
    public function execute(Request $request, Script $script): RedirectResponse
    {
        $this->authorize('view', $script);

        app(ExecuteScript::class)->execute($script, user(), $request->input());

        return redirect()->route('scripts.show', $script)->with('info', 'Script is being executed.');
    }
}
