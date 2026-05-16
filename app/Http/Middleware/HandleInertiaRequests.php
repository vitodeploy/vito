<?php

namespace App\Http\Middleware;

use App\Actions\Bootstrap\GetBootstrap;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ServerResource;
use App\Http\Resources\SiteResource;
use App\Http\Resources\UserResource;
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

        $ssrEnabled = (bool) config('inertia.ssr.enabled');

        /** @var ?User $user */
        $user = $request->user();
        $currentProject = $user?->currentProject;
        $canSeeCurrentProject = $user && $currentProject && $user->can('view', $currentProject);
        if ($user && (! $currentProject || ! $canSeeCurrentProject)) {
            $user->ensureHasDefaultProject();
            $user->unsetRelation('currentProject');
            $currentProject = $user->currentProject;
        }

        $data = [];
        if ($request->route('server')) {
            /** @var Server $server */
            $server = $request->route('server');
            if ($user && $user->can('view', $server) && $user->current_project_id !== $server->project_id) {
                $user->current_project_id = $server->project_id;
                $user->save();
            }

            $data['server'] = ServerResource::make($server);

            if ($request->route('site')) {
                /** @var Site $site */
                $site = $request->route('site');
                $site->load('hostedDomains.ssl');
                $data['site'] = SiteResource::make($site);
            }
        }

        return [
            ...parent::share($request),
            ...$data,
            'name' => config('app.name'),
            'version' => config('app.version'),
            'env' => config('app.env'),
            'demo' => config('app.demo'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => $user ? [
                'user' => UserResource::make($user->load('projects')),
                'currentProject' => ProjectResource::make($currentProject),
            ] : null,
            'csrf_token' => csrf_token(),
            'bootstrap_version' => app(GetBootstrap::class)->version(),
            ...($ssrEnabled ? [
                'ziggy' => fn (): array => [
                    ...(new Ziggy)->toArray(),
                    'location' => $request->url(),
                ],
            ] : []),
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
