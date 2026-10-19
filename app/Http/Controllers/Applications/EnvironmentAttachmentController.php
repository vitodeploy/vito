<?php

namespace App\Http\Controllers\Applications;

use App\Actions\EnvironmentAttachment\AttachDatabase;
use App\Actions\EnvironmentAttachment\AttachRedis;
use App\Actions\EnvironmentAttachment\DetachEnvironmentResource;
use App\Actions\EnvironmentAttachment\GetEnvironmentResourceOptions;
use App\Actions\EnvironmentAttachment\SyncEnvironmentResourceAccess;
use App\Http\Controllers\Controller;
use App\Tables\Applications\EnvironmentAttachmentTable;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

#[Prefix('applications/{application}/environments/{environment}/resources')]
#[Middleware(['auth', 'has-project'])]
#[ScopeBindings]
class EnvironmentAttachmentController extends Controller
{
    #[Get('/', name: 'environments.resources')]
    public function index(Application $application, Environment $environment): Response
    {
        $this->authorize('view', [$environment, $application]);

        return Inertia::render('applications/environments/resources', [
            'attachments' => EnvironmentAttachmentTable::make($environment->attachments()->getQuery())->paginate(),
            'resourceOptions' => app(GetEnvironmentResourceOptions::class)->get($environment),
            'canUpdate' => user()->can('update', [$environment, $application]),
        ]);
    }

    #[Post('/database', name: 'environments.resources.database')]
    public function database(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);
        app(AttachDatabase::class)->attach($environment, $request->all());

        return back()->with('success', 'Database attached. Access is synchronizing; redeploy when ready.');
    }

    #[Post('/redis', name: 'environments.resources.redis')]
    public function redis(Request $request, Application $application, Environment $environment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);
        app(AttachRedis::class)->attach($environment, $request->all());

        return back()->with('success', 'Resource attached. Access is synchronizing; redeploy when ready.');
    }

    #[Post('/{attachment}/retry', name: 'environments.resources.retry')]
    public function retry(Application $application, Environment $environment, EnvironmentAttachment $attachment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);
        app(SyncEnvironmentResourceAccess::class)->retry($attachment);

        return back()->with('success', 'Resource synchronization queued.');
    }

    #[Delete('/{attachment}', name: 'environments.resources.destroy')]
    public function destroy(Application $application, Environment $environment, EnvironmentAttachment $attachment): RedirectResponse
    {
        $this->authorize('update', [$environment, $application]);
        app(DetachEnvironmentResource::class)->detach($attachment);

        return back()->with('success', 'Resource cleanup queued. The database and its data will be kept.');
    }
}
