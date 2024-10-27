<?php

namespace App\Http\Controllers\API;

use App\Actions\SourceControl\ConnectSourceControl;
use App\Actions\SourceControl\DeleteSourceControl;
use App\Actions\SourceControl\EditSourceControl;
use App\Http\Controllers\Controller;
use App\Http\Resources\SourceControlResource;
use App\Models\Project;
use App\Models\SourceControl;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group(name: 'source-controls')]
class SourceControlController extends Controller
{
    #[Endpoint(title: 'list')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class, collection: true, paginate: 25)]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', SourceControl::class);

        $sourceControls = SourceControl::getByProjectId($project->id)->simplePaginate(25);

        return SourceControlResource::collection($sourceControls);
    }

    #[Endpoint(title: 'create')]
    #[BodyParam(name: 'provider', description: 'The provider', required: true, enum: [\App\Enums\SourceControl::GITLAB, \App\Enums\SourceControl::GITHUB, \App\Enums\SourceControl::BITBUCKET])]
    #[BodyParam(name: 'name', description: 'The name of the storage provider.', required: true)]
    #[BodyParam(name: 'token', description: 'The token if provider requires api token')]
    #[BodyParam(name: 'url', description: 'The URL if the provider is Gitlab and it is self-hosted')]
    #[BodyParam(name: 'username', description: 'The username if the provider is Bitbucket')]
    #[BodyParam(name: 'password', description: 'The password if the provider is Bitbucket')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class)]
    public function create(Request $request, Project $project): SourceControlResource
    {
        $this->authorize('create', SourceControl::class);

        $this->validate($request, ConnectSourceControl::rules($request->all()));

        $sourceControl = app(ConnectSourceControl::class)->connect(auth()->user(), $project, $request->all());

        return new SourceControlResource($sourceControl);
    }

    #[Endpoint(title: 'show')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class)]
    public function show(Project $project, SourceControl $sourceControl)
    {
        $this->authorize('view', $sourceControl);

        return new SourceControlResource($sourceControl);
    }

    #[Endpoint(title: 'update')]
    #[ResponseFromApiResource(SourceControlResource::class, SourceControl::class)]
    public function update(Request $request, Project $project, SourceControl $sourceControl)
    {
        $this->authorize('update', $sourceControl);

        $this->validate($request, EditSourceControl::rules($sourceControl, $request->all()));

        $sourceControl = app(EditSourceControl::class)->edit($sourceControl, $project, $request->all());

        return new SourceControlResource($sourceControl);
    }

    /**
     * @throws Exception
     */
    #[Endpoint(title: 'delete')]
    #[Response(status: 204)]
    public function delete(Project $project, SourceControl $sourceControl)
    {
        $this->authorize('delete', $sourceControl);

        app(DeleteSourceControl::class)->delete($sourceControl);

        return response()->noContent();
    }
}
