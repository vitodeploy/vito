<?php

namespace App\Http\Controllers;

use App\Actions\Script\CreateScript;
use App\Actions\Script\EditScript;
use App\Actions\Script\ExecuteScript;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Script;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScriptController extends Controller
{
    public function index(Request $request): View
    {
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

    public function store(Request $request): HtmxResponse
    {
        /** @var User $user */
        $user = auth()->user();

        app(CreateScript::class)->create($user, $request->input());

        Toast::success('Script created.');

        return htmx()->redirect(route('scripts.index'));
    }

    public function edit(Request $request, Script $script): HtmxResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($script->user_id !== $user->id) {
            abort(403);
        }

        app(EditScript::class)->edit($script, $request->input());

        Toast::success('Script updated.');

        return htmx()->redirect(route('scripts.index'));
    }

    public function execute(Request $request, Script $script): HtmxResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($script->user_id !== $user->id) {
            abort(403);
        }

        app(ExecuteScript::class)->execute($script, $request->input());

        Toast::success('Executing the script...');

        return htmx()->redirect(route('scripts.index'));
    }

    public function delete(Script $script): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($script->user_id !== $user->id) {
            abort(403);
        }

        $script->delete();

        Toast::success('Script deleted.');

        return redirect()->route('scripts.index');
    }
}
