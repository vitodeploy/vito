<?php

namespace App\Http\Controllers\API;

use App\Actions\SSL\ActivateServerSsl;
use App\Actions\SSL\CreateLetsEncryptWildcardSsl;
use App\Actions\SSL\CreateServerSsl;
use App\Actions\SSL\DeleteSsl;
use App\Actions\SSL\InstallCustomServerSsl;
use App\Enums\SslStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SslResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Prefix('api/projects/{project}/servers/{server}/ssls')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class ServerSSLController extends Controller
{
    #[Get('/', name: 'api.projects.servers.ssls', middleware: 'ability:read')]
    public function index(Project $project, Server $server): ResourceCollection
    {
        $this->authorize('viewAny', [Ssl::class, $server]);

        $this->validateRoute($project, $server);

        return SslResource::collection(
            $server->ssls()->whereNull('site_id')->simplePaginate(25)
        );
    }

    #[Get('{ssl}', name: 'api.projects.servers.ssls.show', middleware: 'ability:read')]
    public function show(Project $project, Server $server, Ssl $ssl): SslResource
    {
        $this->validateRoute($project, $server, $ssl);

        $this->authorize('viewAny', [Ssl::class, $server]);

        return new SslResource($ssl);
    }

    #[Post('/csr', name: 'api.projects.servers.ssls.create-csr', middleware: 'ability:write')]
    public function createCsr(Request $request, Project $project, Server $server): SslResource
    {
        $this->authorize('create', [Ssl::class, $server]);

        $this->validateRoute($project, $server);

        $ssl = app(CreateServerSsl::class)->create($server, $request->all());

        return new SslResource($ssl);
    }

    #[Post('/custom', name: 'api.projects.servers.ssls.create-custom', middleware: 'ability:write')]
    public function createCustom(Request $request, Project $project, Server $server): SslResource
    {
        $this->authorize('create', [Ssl::class, $server]);

        $this->validateRoute($project, $server);

        $ssl = app(InstallCustomServerSsl::class)->install($server, $request->all());

        return new SslResource($ssl);
    }

    #[Post('/letsencrypt-wildcard', name: 'api.projects.servers.ssls.create-letsencrypt-wildcard', middleware: 'ability:write')]
    public function createLetsEncryptWildcard(Request $request, Project $project, Server $server): SslResource
    {
        $this->authorize('create', [Ssl::class, $server]);

        $this->validateRoute($project, $server);

        $ssl = app(CreateLetsEncryptWildcardSsl::class)->create($server, $request->all());

        return new SslResource($ssl);
    }

    #[Post('{ssl}/activate', name: 'api.projects.servers.ssls.activate', middleware: 'ability:write')]
    public function activate(Request $request, Project $project, Server $server, Ssl $ssl): SslResource
    {
        $this->authorize('activate', [$ssl, $server]);

        $this->validateRoute($project, $server, $ssl);

        app(ActivateServerSsl::class)->activate($server, $ssl, $request->all());

        return new SslResource($ssl->refresh());
    }

    #[Delete('{ssl}', name: 'api.projects.servers.ssls.delete', middleware: 'ability:write')]
    public function delete(Project $project, Server $server, Ssl $ssl): Response
    {
        $this->authorize('delete', [$ssl, $server]);

        $this->validateRoute($project, $server, $ssl);

        app(DeleteSsl::class)->delete($ssl);

        return response()->noContent();
    }

    #[Get('{ssl}/download', name: 'api.projects.servers.ssls.download', middleware: 'ability:read')]
    public function download(Project $project, Server $server, Ssl $ssl): StreamedResponse
    {
        $this->authorize('viewAny', [Ssl::class, $server]);

        $this->validateRoute($project, $server, $ssl);

        if (! $ssl->has_csr || $ssl->status !== SslStatus::CREATED) {
            abort(404, 'CSR file is not available for download.');
        }

        $csrPath = $ssl->csr_data['csr_path'] ?? null;
        if (! $csrPath) {
            abort(404, 'CSR file path not found.');
        }

        $tmpName = $server->id.'-'.strtotime('now').'-csr-'.$ssl->id.'.csr';
        $tmpPath = Storage::disk('local')->path($tmpName);

        $server->ssh()->download($tmpPath, $csrPath);

        dispatch(function () use ($tmpPath): void {
            if (File::exists($tmpPath)) {
                File::delete($tmpPath);
            }
        })
            ->delay(now()->addMinutes(5))
            ->onQueue('default');

        return Storage::disk('local')->download($tmpName, 'request.csr');
    }

    private function validateRoute(Project $project, Server $server, ?Ssl $ssl = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($ssl && $ssl->server_id !== $server->id) {
            abort(404, 'SSL not found in server');
        }
    }
}
