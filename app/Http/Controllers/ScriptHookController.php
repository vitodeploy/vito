<?php

namespace App\Http\Controllers;

use App\Actions\ScriptEventHook\CreateScriptEventHook;
use App\Actions\ScriptEventHook\UpdateScriptEventHook;
use App\Http\Resources\ScriptEventHookResource;
use App\Models\Script;
use App\Models\ScriptEventHook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('scripts/{script}/hooks')]
#[Middleware(['auth'])]
class ScriptHookController extends Controller
{
    #[Get('/', name: 'scripts.hooks')]
    public function index(Script $script): Response
    {
        $this->authorize('viewAny', [ScriptEventHook::class, $script]);

        return Inertia::render('scripts/hooks', [
            'script' => $script,
            'hooks' => ScriptEventHookResource::collection(
                $script->hooks()->with('server')->latest()->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Post('/', name: 'scripts.hooks.store')]
    public function store(Request $request, Script $script): RedirectResponse
    {
        $this->authorize('create', [ScriptEventHook::class, $script]);

        app(CreateScriptEventHook::class)->create(user(), $script, $request->input());

        return back()->with('success', 'Hook created.');
    }

    #[Put('/{hook}', name: 'scripts.hooks.update')]
    public function update(Request $request, Script $script, ScriptEventHook $hook): RedirectResponse
    {
        abort_if($hook->script_id !== $script->id, 404);
        $this->authorize('update', $hook);

        app(UpdateScriptEventHook::class)->update($hook, user(), $request->input());

        return back()->with('success', 'Hook updated.');
    }

    #[Delete('/{hook}', name: 'scripts.hooks.destroy')]
    public function destroy(Script $script, ScriptEventHook $hook): RedirectResponse
    {
        abort_if($hook->script_id !== $script->id, 404);
        $this->authorize('delete', $hook);

        $hook->delete();

        return back()->with('success', 'Hook deleted.');
    }
}
