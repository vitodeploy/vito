<?php

namespace App\Http\Controllers;

use App\Actions\HostedDomain\ActivateHostedDomain;
use App\Actions\HostedDomain\CreateHostedDomain;
use App\Actions\HostedDomain\DeactivateHostedDomain;
use App\Actions\HostedDomain\DeleteHostedDomain;
use App\Actions\HostedDomain\ReactivateHostedDomain;
use App\Actions\HostedDomain\UpdateHostedDomain;
use App\Actions\SSL\AssignSslToDomains;
use App\Actions\SSL\GetMatchingSslCertificates;
use App\Actions\SSL\RenewSiteSsl;
use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use App\Tables\HostedDomainTable;
use Illuminate\Http\JsonResponse;
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

#[Prefix('/servers/{server}/sites/{site}/domains')]
#[Middleware(['auth', 'has-project'])]
class HostedDomainController extends Controller
{
    #[Get('/', name: 'hosted-domains')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [HostedDomain::class, $site, $server]);

        return Inertia::render('hosted-domains/index', [
            'hostedDomains' => HostedDomainTable::make($site->hostedDomains())->simplePaginate(),
            'hasSiteSsl' => $site->ssls()
                ->where('type', SslType::LETSENCRYPT)
                ->where('status', SslStatus::CREATED)
                ->exists(),
        ]);
    }

    #[Post('/', name: 'hosted-domains.store')]
    public function store(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('create', [HostedDomain::class, $site, $server]);

        app(CreateHostedDomain::class)->create($site, $request->input());

        return back()
            ->with('success', 'Domain added successfully.');
    }

    #[Put('/{hostedDomain}', name: 'hosted-domains.update')]
    public function update(Request $request, Server $server, Site $site, HostedDomain $hostedDomain): RedirectResponse
    {
        $this->authorize('update', [$hostedDomain, $site, $server]);

        app(UpdateHostedDomain::class)->update($hostedDomain, $site, $request->input());

        return back()
            ->with('success', 'Domain updated successfully.');
    }

    #[Delete('/{hostedDomain}', name: 'hosted-domains.destroy')]
    public function destroy(Server $server, Site $site, HostedDomain $hostedDomain): RedirectResponse
    {
        $this->authorize('delete', [$hostedDomain, $site, $server]);

        app(DeleteHostedDomain::class)->delete($hostedDomain);

        return back()
            ->with('success', 'Domain deleted successfully.');
    }

    #[Post('/{hostedDomain}/force-activate', name: 'hosted-domains.force-activate')]
    public function forceActivate(Server $server, Site $site, HostedDomain $hostedDomain): RedirectResponse
    {
        $this->authorize('update', [$hostedDomain, $site, $server]);

        app(ActivateHostedDomain::class)->activate($hostedDomain);

        return back()
            ->with('success', 'Domain has been force activated.');
    }

    #[Post('/{hostedDomain}/deactivate', name: 'hosted-domains.deactivate')]
    public function deactivate(Server $server, Site $site, HostedDomain $hostedDomain): RedirectResponse
    {
        $this->authorize('update', [$hostedDomain, $site, $server]);

        app(DeactivateHostedDomain::class)->deactivate($hostedDomain);

        return back()
            ->with('success', 'Domain has been deactivated.');
    }

    #[Post('/{hostedDomain}/reactivate', name: 'hosted-domains.reactivate')]
    public function reactivate(Server $server, Site $site, HostedDomain $hostedDomain): RedirectResponse
    {
        $this->authorize('update', [$hostedDomain, $site, $server]);

        app(ReactivateHostedDomain::class)->reactivate($hostedDomain);

        return back()
            ->with('info', 'Reactivating domain.');
    }

    #[Post('/{hostedDomain}/check-dns', name: 'hosted-domains.check-dns')]
    public function checkDns(Server $server, Site $site, HostedDomain $hostedDomain): RedirectResponse
    {
        $this->authorize('update', [$hostedDomain, $site, $server]);

        $hostedDomain->status = HostedDomainStatus::UPDATING;
        $hostedDomain->save();

        dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');

        return back()
            ->with('info', 'Validating domain DNS resolution.');
    }

    #[Post('/renew-ssl', name: 'hosted-domains.renew-ssl')]
    public function renewSsl(Server $server, Site $site): RedirectResponse
    {
        $this->authorize('create', [HostedDomain::class, $site, $server]);

        try {
            app(RenewSiteSsl::class)->renew($site);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->with('error', $e->getMessage());
        }

        return back()
            ->with('info', 'Renewing site SSL certificate.');
    }

    #[Get('/matching-ssls', name: 'hosted-domains.matching-ssls')]
    public function matchingSsls(Request $request, Server $server, Site $site): JsonResponse
    {
        $this->authorize('viewAny', [HostedDomain::class, $site, $server]);

        $domain = $request->query('domain', '');

        if (empty($domain) || ! preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain)) {
            return response()->json(['certificates' => [], 'best_match_id' => null]);
        }

        $certificates = app(GetMatchingSslCertificates::class)->forDomain($site, $domain);

        $serverSsls = Ssl::activeServerLevel($site->server_id)
            ->get();

        $bestMatch = app(AssignSslToDomains::class)->findBestMatch($domain, $serverSsls);

        return response()->json([
            'certificates' => $certificates,
            'best_match_id' => $bestMatch?->id,
        ]);
    }
}
