<?php

namespace App\Http\Controllers\API;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Actions\SourceControl\DeleteSourceControl;
use App\Actions\SourceControl\EditSourceControl;
use App\Http\Controllers\Controller;
use App\Http\Resources\SourceControlResource;
use App\Models\SourceControl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('api/source-controls')]
#[Middleware(['auth:sanctum'])]
class UserSourceControlController extends Controller
{
    #[Get('/', name: 'api.user.source-controls', middleware: 'ability:read')]
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', SourceControl::class);

        $sourceControls = user()->sourceControls()->simplePaginate(25);

        return SourceControlResource::collection($sourceControls);
    }

    #[Post('/', name: 'api.user.source-controls.create', middleware: 'ability:write')]
    public function create(Request $request): SourceControlResource
    {
        $this->authorize('create', SourceControl::class);

        $user = user();
        $sourceControl = app(ConnectSourceControl::class)->connect($user, $request->all());

        return new SourceControlResource($sourceControl);
    }

    #[Get('{sourceControl}', name: 'api.user.source-controls.show', middleware: 'ability:read')]
    public function show(SourceControl $sourceControl): SourceControlResource
    {
        $this->authorize('view', $sourceControl);

        // Ensure the source control belongs to the authenticated user
        if ($sourceControl->user_id !== user()->id) {
            abort(404, 'Source control not found');
        }

        return new SourceControlResource($sourceControl);
    }

    #[Put('{sourceControl}', name: 'api.user.source-controls.update', middleware: 'ability:write')]
    public function update(Request $request, SourceControl $sourceControl): SourceControlResource
    {
        $this->authorize('update', $sourceControl);

        // Ensure the source control belongs to the authenticated user
        if ($sourceControl->user_id !== user()->id) {
            abort(404, 'Source control not found');
        }

        $sourceControl = app(EditSourceControl::class)->edit($sourceControl, $request->all());

        return new SourceControlResource($sourceControl);
    }

    #[Delete('{sourceControl}', name: 'api.user.source-controls.delete', middleware: 'ability:write')]
    public function delete(SourceControl $sourceControl): Response
    {
        $this->authorize('delete', $sourceControl);

        // Ensure the source control belongs to the authenticated user
        if ($sourceControl->user_id !== user()->id) {
            abort(404, 'Source control not found');
        }

        app(DeleteSourceControl::class)->delete($sourceControl);

        return response()->noContent();
    }
}
