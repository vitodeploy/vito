<?php

namespace App\Http\Controllers;

use App\Exceptions\SourceControlIsNotConnected;
use App\Models\GitHook;
use Illuminate\Http\Request;
use Throwable;

class GitHookController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->input('secret')) {
            abort(404);
        }

        /** @var GitHook $gitHook */
        $gitHook = GitHook::query()
            ->where('secret', $request->input('secret'))
            ->firstOrFail();

        foreach ($gitHook->actions as $action) {
            if ($action == 'deploy') {
                try {
                    $gitHook->site->deploy();
                } catch (SourceControlIsNotConnected) {
                    // TODO: send notification
                } catch (Throwable $e) {
                    Log::error('git-hook-exception', (array) $e);
                }
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
