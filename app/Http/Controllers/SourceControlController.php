<?php

namespace App\Http\Controllers;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Actions\SourceControl\DeleteSourceControl;
use App\Actions\SourceControl\EditSourceControl;
use App\Http\Resources\SourceControlResource;
use App\Models\SourceControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/source-controls')]
#[Middleware(['auth'])]
class SourceControlController extends Controller
{
    #[Get('/', name: 'source-controls')]
    public function index(): Response
    {
        $this->authorize('viewAny', SourceControl::class);

        return Inertia::render('source-controls/index', [
            'sourceControls' => SourceControlResource::collection(SourceControl::getByProjectId(user()->current_project_id)->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Get('/json', name: 'source-controls.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', SourceControl::class);

        return SourceControlResource::collection(SourceControl::getByProjectId(user()->current_project_id)->get());
    }

    #[Post('/', name: 'source-controls.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SourceControl::class);

        app(ConnectSourceControl::class)->connect(user()->currentProject, $request->all());

        return back()->with('success', 'Source control created.');
    }

    #[Patch('/{sourceControl}', name: 'source-controls.update')]
    public function update(Request $request, SourceControl $sourceControl): RedirectResponse
    {
        $this->authorize('update', $sourceControl);

        app(EditSourceControl::class)->edit($sourceControl, user()->currentProject, $request->all());

        return back()->with('success', 'Source control updated.');
    }

    #[Delete('{sourceControl}', name: 'source-controls.destroy')]
    public function destroy(SourceControl $sourceControl): RedirectResponse
    {
        $this->authorize('delete', $sourceControl);

        app(DeleteSourceControl::class)->delete($sourceControl);

        return to_route('source-controls')->with('success', 'Source control deleted.');
    }
}
