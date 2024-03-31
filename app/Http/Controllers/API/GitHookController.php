<?php

namespace App\Http\Controllers\API;

use App\Actions\Site\Deploy;
use App\Exceptions\SourceControlIsNotConnected;
use App\Facades\Notifier;
use App\Http\Controllers\Controller;
use App\Models\GitHook;
use App\Models\ServerLog;
use App\Notifications\SourceControlDisconnected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                    app(Deploy::class)->run($gitHook->site);
                } catch (SourceControlIsNotConnected) {
                    Notifier::send($gitHook->sourceControl, new SourceControlDisconnected($gitHook->sourceControl));
                } catch (Throwable $e) {
                    ServerLog::log(
                        $gitHook->site->server,
                        'deploy-failed',
                        $e->getMessage(),
                        $gitHook->site
                    );
                    Log::error('git-hook-exception', (array) $e);
                }
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
