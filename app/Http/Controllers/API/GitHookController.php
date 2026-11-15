<?php

namespace App\Http\Controllers\API;

use App\Actions\Application\GetApplicationSourceControl;
use App\Actions\GitHook\TriggerRepositoryAutoDeploy;
use App\Actions\Site\Deploy;
use App\Exceptions\FailedToDestroyGitHook;
use App\Http\Controllers\Controller;
use App\Models\GitHook;
use App\Models\ServerLog;
use App\SourceControlProviders\AbstractSourceControlProvider;
use App\SourceControlProviders\SupportsApplications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\RouteAttributes\Attributes\Any;
use Throwable;

class GitHookController extends Controller
{
    /**
     * @throws FailedToDestroyGitHook
     */
    #[Any('api/git-hooks', name: 'api.git-hooks')]
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->input('secret')) {
            abort(404);
        }

        /** @var GitHook $gitHook */
        $gitHook = GitHook::query()
            ->where('secret', $request->input('secret'))
            ->firstOrFail();

        if ($gitHook->repository !== null) {
            $provider = $gitHook->sourceControl ? app(GetApplicationSourceControl::class)->provider($gitHook->sourceControl) : null;
            foreach ($provider?->getPushBranches($request->array()) ?? [] as $branch) {
                app(TriggerRepositoryAutoDeploy::class)->forBranch($gitHook, $branch);
            }

            return response()->json(['success' => true]);
        }

        if (! $gitHook->site) {
            $gitHook->destroyHook();

            return response()->json([
                'success' => true,
            ]);
        }

        $provider = $gitHook->site->sourceControl?->provider();
        $pushedBranches = match (true) {
            $provider instanceof AbstractSourceControlProvider, $provider instanceof SupportsApplications => $provider->getPushBranches($request->array()),
            $provider !== null => [$provider->getWebhookBranch($request->array())],
            default => [],
        };

        foreach ($gitHook->actions as $action) {
            if ($action == 'deploy' && in_array($gitHook->site->branch, $pushedBranches, true)) {
                try {
                    app(Deploy::class)->run($gitHook->site);
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
