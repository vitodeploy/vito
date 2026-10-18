<?php

namespace App\Http\Controllers\Applications;

use App\Actions\Application\CreateApplication;
use App\Actions\Application\DeleteApplication;
use App\Actions\Application\UpdateApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnvironmentResource;
use App\Http\Resources\SourceControlResource;
use App\Models\Application;
use App\Models\Environment;
use App\Models\SourceControl;
use App\Tables\ApplicationTable;
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

#[Prefix('applications')]
#[Middleware(['auth', 'has-project'])]
class ApplicationController extends Controller
{
    #[Get('/', name: 'applications')]
    public function index(): Response
    {
        $project = user()->currentProject;
        $this->authorize('viewAny', [Application::class, $project]);

        return Inertia::render('applications/index', [
            'applications' => ApplicationTable::make($project->applications())->simplePaginate(),
            'sourceControls' => SourceControlResource::collection(SourceControl::getByProjectId($project->id, user())->get()),
            'canCreate' => user()->can('create', [Application::class, $project]),
        ]);
    }

    #[Post('/', name: 'applications.store')]
    public function store(Request $request): RedirectResponse
    {
        $project = user()->currentProject;
        $this->authorize('create', [Application::class, $project]);

        $application = app(CreateApplication::class)->create($project, $request->all(), user());

        return to_route('applications.show', $application)->with('success', 'Application created.');
    }

    #[Get('/{application}', name: 'applications.show')]
    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        return Inertia::render('applications/show', [
            'environments' => EnvironmentResource::collection($application->environments()->withCount('components')->withExists(['components as needs_redeploy' => fn ($query) => $query->where('needs_redeploy', true)])->orderBy('name')->get()),
            'sourceControls' => SourceControlResource::collection(SourceControl::getByProjectId($application->project_id, user())->get()),
            'can' => [
                'update' => user()->can('update', $application),
                'delete' => user()->can('delete', $application),
                'createEnvironment' => user()->can('create', [Environment::class, $application]),
            ],
        ]);
    }

    #[Put('/{application}', name: 'applications.update')]
    public function update(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        app(UpdateApplication::class)->update($application, $request->all(), user());

        return back()->with('success', 'Application updated. Repository changes sync component deploy keys now and apply to the running release on the next deploy.');
    }

    #[Delete('/{application}', name: 'applications.destroy')]
    public function destroy(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        app(DeleteApplication::class)->delete($application, $request->all());

        return to_route('applications')->with('success', 'Application deletion requested. Components are cleaned up before the application is removed.');
    }
}
