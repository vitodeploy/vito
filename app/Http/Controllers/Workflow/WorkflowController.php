<?php

namespace App\Http\Controllers\Workflow;

use App\Actions\Workflow\CreateWorkflow;
use App\Actions\Workflow\UpdateWorkflow;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowResource;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('workflows')]
#[Middleware(['auth', 'has-project'])]
class WorkflowController extends Controller
{
    #[Get('/', name: 'workflows')]
    public function index(): Response
    {
        $user = user();

        $this->authorize('viewAny', [Workflow::class, $user->currentProject]);

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

        $this->authorize('create', [Workflow::class, $user->currentProject]);

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

        try {
            app(UpdateWorkflow::class)->update($workflow, $request->all());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->first()[0] ?? 'An error occurred');
        }

        return back()->with('success', 'Changes saved!');
    }

    #[Delete('/{workflow}', name: 'workflows.destroy')]
    public function destroy(Workflow $workflow): RedirectResponse
    {
        $this->authorize('delete', $workflow);

        $workflow->delete();

        return redirect()->route('workflows')->with('success', 'Workflow deleted!');
    }
}
