<?php

namespace App\Http\Controllers;

use App\Actions\SSL\ActivateServerSsl;
use App\Actions\SSL\CreateServerSsl;
use App\Actions\SSL\DeleteSsl;
use App\Enums\SslStatus;
use App\Http\Resources\SslResource;
use App\Models\Domain;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Prefix('/servers/{server}/ssl')]
#[Middleware(['auth', 'has-project'])]
class ServerSslController extends Controller
{
    #[Get('/', name: 'server-ssls')]
    public function index(Server $server): Response
    {
        $this->authorize('viewAny', [Ssl::class, $server]);

        $domains = Domain::query()
            ->where('project_id', $server->project_id)
            ->whereNotNull('dns_provider_id')
            ->get(['id', 'domain', 'dns_provider_id']);

        return Inertia::render('server-ssls/index', [
            'ssls' => SslResource::collection(
                $server->ssls()->whereNull('site_id')->latest()->simplePaginate(config('web.pagination_size'))
            ),
            'domains' => $domains,
        ]);
    }

    #[Post('/', name: 'server-ssls.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [Ssl::class, $server]);

        $ssl = app(CreateServerSsl::class)->create($server, $request->all());

        $message = match ($ssl->type) {
            'csr' => 'Certificate Signing Request is being generated.',
            'letsencrypt' => 'Wildcard certificate is being created.',
            'custom' => 'Custom certificate is being installed.',
            default => 'SSL certificate is being created.',
        };

        return back()->with('info', $message);
    }

    #[Post('/{ssl}/activate', name: 'server-ssls.activate')]
    public function activate(Request $request, Server $server, Ssl $ssl): RedirectResponse
    {
        $this->authorize('activate', [$ssl, $server]);

        app(ActivateServerSsl::class)->activate($server, $ssl, $request->all());

        return back()->with('info', 'SSL certificate is being activated.');
    }

    #[Delete('/{ssl}', name: 'server-ssls.destroy')]
    public function destroy(Server $server, Ssl $ssl): RedirectResponse
    {
        $this->authorize('delete', [$ssl, $server]);

        app(DeleteSsl::class)->delete($ssl);

        return back()->with('success', 'SSL certificate deleted.');
    }

    #[Get('/{ssl}/download', name: 'server-ssls.download')]
    public function download(Server $server, Ssl $ssl): StreamedResponse
    {
        $this->authorize('downloadCsr', [$ssl, $server]);

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
}
