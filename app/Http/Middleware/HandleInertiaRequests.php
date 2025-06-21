<?php

namespace App\Http\Middleware;

use App\Http\Resources\ServerResource;
use App\Http\Resources\SiteResource;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        /** @var ?User $user */
        $user = $request->user();

        // servers
        $servers = [];
        if ($user && $user->can('viewAny', [Server::class, $user->currentProject])) {
            $servers = ServerResource::collection($user->currentProject?->servers);
        }

        $data = [];
        if ($request->route('server')) {
            $data['server'] = ServerResource::make($request->route('server'));

            // sites
            $sites = [];
            /** @var Server $server */
            $server = $request->route('server');
            if ($user && $user->can('viewAny', [Site::class, $server])) {
                $sites = SiteResource::collection($server->sites);
            }

            $data['server_sites'] = $sites;

            if ($request->route('site')) {
                $data['site'] = SiteResource::make($request->route('site'));
            }
        }

        return [
            ...parent::share($request),
            ...$data,
            'name' => config('app.name'),
            'version' => config('app.version'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'projects' => $user?->allProjects()->get(),
                'currentProject' => $user?->currentProject,
            ],
            'public_key_text' => __('servers.create.public_key_text', ['public_key' => get_public_key_content()]),
            'project_servers' => $servers,
            'configs' => [
                'operating_systems' => config('core.operating_systems'),
                'colors' => config('core.colors'),
                'cronjob_intervals' => config('core.cronjob_intervals'),
                'metrics_periods' => config('core.metrics_periods'),
                'site' => [
                    'types' => config('site.types'),
                ],
                'source_control' => [
                    'providers' => config('source-control.providers'),
                ],
                'server_provider' => [
                    'providers' => config('server-provider.providers'),
                ],
                'storage_provider' => [
                    'providers' => config('storage-provider.providers'),
                ],
                'notification_channel' => [
                    'providers' => config('notification-channel.providers'),
                ],
                'service' => [
                    'services' => config('service.services'),
                ],
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'csrf_token' => csrf_token(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'danger' => fn () => $request->session()->get('danger'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'gray' => fn () => $request->session()->get('gray'),
                'data' => fn () => $request->session()->get('data'),
            ],
        ];
    }
}
