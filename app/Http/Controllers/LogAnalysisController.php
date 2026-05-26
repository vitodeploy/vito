<?php

namespace App\Http\Controllers;

use App\Jobs\Site\ResyncGoAccessJob;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/log-analysis')]
#[Middleware(['auth', 'has-project'])]
class LogAnalysisController extends Controller
{
    #[Post('/resync', name: 'log-analysis.resync')]
    public function resync(Server $server): RedirectResponse
    {
        /** @var ?Service $service */
        $service = $server->service('log_analysis');
        abort_if($service === null, 404);

        $this->authorize('update', $service);

        dispatch(new ResyncGoAccessJob($server));

        return back()->with('success', 'Re-syncing site statistics scripts...');
    }
}
