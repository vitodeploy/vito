<?php

namespace App\Http\Controllers\Workflow;

use App\Actions\Workflow\CreateWorkflow;
use App\Actions\Workflow\UpdateWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowResource;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('workflows')]
#[Middleware('auth')]
class WorkflowController extends Controller
{
    #[Get('/', name: 'workflows')]
    public function index(): Response
    {
        $user = user();

        $workflows = $user->currentProject
            ->workflows()
            ->orderBy('created_at', 'desc')
            ->paginate(config('web.pagination_size'));

        return Inertia::render('workflows/index', [
            'workflows' => WorkflowResource::collection($workflows),
        ]);
    }

    #[Post('/', name: 'workflows.store')]
    public function store(Request $request): RedirectResponse
    {
        $user = user();
        $workflow = app(CreateWorkflow::class)->create($user, $user->currentProject, $request->all());

        return redirect()->route('workflows.show', $workflow->id);
    }

    #[Get('/{workflow}', name: 'workflows.show')]
    public function show(Workflow $workflow): Response
    {
        $this->authorize('view', $workflow);

        return Inertia::render('workflows/show', [
            'workflow' => new WorkflowResource($workflow),
            'actions' => $workflow->actions(),
        ]);
    }

    #[Put('/{workflow}', name: 'workflows.update')]
    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);

        app(UpdateWorkflow::class)->update($workflow, $request->all());

        return back()->with('success', 'Changes saved!');
    }
}
