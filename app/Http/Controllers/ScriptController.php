<?php

namespace App\Http\Controllers;

use App\Actions\Script\CreateScript;
use App\Actions\Script\EditScript;
use App\Actions\Script\ExecuteScript;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScriptController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Script::class);

        /** @var User $user */
        $user = auth()->user();

        $data = [
            'scripts' => $user->scripts,
        ];

        if ($request->has('edit')) {
            $data['editScript'] = $user->scripts()->findOrFail($request->input('edit'));
        }

        if ($request->has('execute')) {
            $data['executeScript'] = $user->scripts()->findOrFail($request->input('execute'));
        }

        return view('scripts.index', $data);
    }

    public function show(Script $script): View
    {
        $this->authorize('view', $script);

        return view('scripts.show', [
            'script' => $script,
            'executions' => $script->executions()->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): HtmxResponse
    {
        $this->authorize('create', Script::class);

        /** @var User $user */
        $user = auth()->user();

        app(CreateScript::class)->create($user, $request->input());

        Toast::success('Script created.');

        return htmx()->redirect(route('scripts.index'));
    }

    public function edit(Request $request, Script $script): HtmxResponse
    {
        $this->authorize('update', $script);

        app(EditScript::class)->edit($script, $request->input());

        Toast::success('Script updated.');

        return htmx()->redirect(route('scripts.index'));
    }

    public function execute(Script $script, Request $request): HtmxResponse
    {
        $this->validate($request, [
            'server' => 'required|exists:servers,id',
        ]);

        $server = Server::findOrFail($request->input('server'));

        $this->authorize('execute', [$script, $server]);

        app(ExecuteScript::class)->execute($script, $server, $request->input());

        Toast::success('Executing the script...');

        return htmx()->redirect(route('scripts.show', $script));
    }

    public function delete(Script $script): RedirectResponse
    {
        $this->authorize('delete', $script);

        $script->delete();

        Toast::success('Script deleted.');

        return redirect()->route('scripts.index');
    }

    public function log(Script $script, ScriptExecution $execution): RedirectResponse
    {
        $this->authorize('view', $script);

        return back()->with('content', $execution->serverLog?->getContent());
    }
}
