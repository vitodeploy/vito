<?php

namespace App\Http\Controllers;

use App\Actions\Application\CreateApplication;
use App\Actions\Application\DeleteApplication;
use App\Actions\Application\DeployApplication;
use App\Actions\Application\ResetTemplate;
use App\Actions\Application\UpdateApplication;
use App\Actions\Application\UpdateTemplate;
use App\Helpers\QueryBuilder;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\DeploymentResource;
use App\Http\Resources\ServerLogResource;
use App\Http\Resources\SslResource;
use App\Models\Application;
use App\Models\Server;
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

#[Prefix('/servers/{server}/applications')]
#[Middleware(['auth', 'has-project'])]
class ServerApplicationController extends Controller
{
    #[Get('/', name: 'applications')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Application::class, $server]);

        $applications = $server->applications()->latest();
        $applications = QueryBuilder::for($applications)
            ->searchableFields(['domain'])
            ->query()
            ->simplePaginate(config('web.pagination_size'));

        return Inertia::render('applications/index', [
            'applications' => ApplicationResource::collection($applications),
        ]);
    }

    #[Post('/', name: 'applications.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [Application::class, $server]);

        $application = app(CreateApplication::class)->create($server, $request->input());

        return redirect()->route('applications.show', ['server' => $server, 'application' => $application])
            ->with('info', 'Installing application, please wait...');
    }

    #[Get('/{application}', name: 'applications.show')]
    public function show(Server $server, Application $application): Response
    {
        $this->authorize('view', [$application, $server]);

        return Inertia::render('applications/show', [
            'application' => ApplicationResource::make($application),
            'logs' => ServerLogResource::collection(
                $application->logs()->latest()->simplePaginate(config('web.pagination_size'))
            ),
            'deployments' => DeploymentResource::collection(
                $application->deployments()->latest()->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Put('/{application}', name: 'applications.update')]
    public function update(Request $request, Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        app(UpdateApplication::class)->update($application, $request->input());

        return back()->with('success', 'Application updated successfully.');
    }

    #[Delete('/{application}', name: 'applications.destroy')]
    public function destroy(Server $server, Application $application): RedirectResponse
    {
        $this->authorize('delete', [$application, $server]);

        app(DeleteApplication::class)->delete($application);

        return redirect()->route('applications', ['server' => $server])
            ->with('success', 'Application is being deleted.');
    }

    #[Post('/{application}/deploy', name: 'applications.deploy')]
    public function deploy(Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        app(DeployApplication::class)->deploy($application);

        return back()->with('info', 'Deployment started, please wait...');
    }

    #[Get('/{application}/ssl', name: 'applications.ssl')]
    public function ssl(Server $server, Application $application): Response
    {
        $this->authorize('view', [$application, $server]);

        return Inertia::render('applications/ssl', [
            'application' => ApplicationResource::make($application),
            'ssls' => SslResource::collection(
                $application->ssls()->latest()->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Get('/{application}/logs', name: 'applications.logs')]
    public function logs(Server $server, Application $application): Response
    {
        $this->authorize('view', [$application, $server]);

        $logs = $application->logs()->latest();
        $logs = QueryBuilder::for($logs)
            ->searchableFields(['name'])
            ->query()
            ->simplePaginate(config('web.pagination_size'));

        return Inertia::render('applications/logs', [
            'application' => ApplicationResource::make($application),
            'logs' => ServerLogResource::collection($logs),
        ]);
    }

    #[Get('/{application}/settings', name: 'applications.settings')]
    public function settings(Server $server, Application $application): Response
    {
        $this->authorize('view', [$application, $server]);

        return Inertia::render('applications/settings', [
            'application' => ApplicationResource::make($application),
        ]);
    }

    #[Get('/{application}/template', name: 'applications.template')]
    public function template(Server $server, Application $application): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', [$application, $server]);

        $template = $application->custom_template ?? $application->type()->vhostTemplate(
            $application->webserverId()
        );

        return response()->json([
            'template' => $template,
        ]);
    }

    #[Put('/{application}/template', name: 'applications.template.update')]
    public function updateTemplate(Request $request, Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        app(UpdateTemplate::class)->update($application, $request->input());

        return back()->with('success', 'Template updated and deploying.');
    }

    #[Post('/{application}/template/reset', name: 'applications.template.reset')]
    public function resetTemplate(Server $server, Application $application): RedirectResponse
    {
        $this->authorize('update', [$application, $server]);

        app(ResetTemplate::class)->reset($application);

        return back()->with('success', 'Template reset to default and deploying.');
    }
}
