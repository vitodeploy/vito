<?php

namespace App\Http\Controllers;

use App\Actions\Redirect\CreateRedirect;
use App\Actions\Redirect\DeleteRedirect;
use App\Actions\Redirect\UpdateRedirect;
use App\Http\Resources\RedirectResource;
use App\Models\Redirect;
use App\Models\Server;
use App\Models\Site;
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

#[Prefix('/servers/{server}/sites/{site}/redirects')]
#[Middleware(['auth', 'has-project'])]
class RedirectController extends Controller
{
    #[Get('/', name: 'redirects')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [Redirect::class, $site, $server]);

        return Inertia::render('redirects/index', [
            'redirects' => RedirectResource::collection($site->redirects()->latest()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'redirects.store')]
    public function store(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('create', [Redirect::class, $site, $server]);

        app(CreateRedirect::class)->create($site, $request->input());

        return back()
            ->with('info', 'Creating the redirect');
    }

    #[Put('/{redirect}', name: 'redirects.update')]
    public function update(Request $request, Server $server, Site $site, Redirect $redirect): RedirectResponse
    {
        $this->authorize('update', [$redirect, $site, $server]);

        app(UpdateRedirect::class)->update($site, $redirect, $request->input());

        return back()
            ->with('info', 'Updating the redirect');
    }

    #[Delete('/{redirect}', name: 'redirects.destroy')]
    public function destroy(Server $server, Site $site, Redirect $redirect): RedirectResponse
    {
        $this->authorize('delete', [$redirect, $site, $server]);

        app(DeleteRedirect::class)->delete($site, $redirect);

        return back()
            ->with('info', 'Deleting the redirect');
    }
}
