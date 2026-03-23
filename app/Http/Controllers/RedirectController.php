<?php

namespace App\Http\Controllers;

use App\Actions\Redirect\CreateRedirect;
use App\Actions\Redirect\DeleteRedirect;
use App\Models\Redirect;
use App\Models\Server;
use App\Models\Site;
use App\Tables\RedirectTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/servers/{server}/sites/{site}/redirects')]
#[Middleware(['auth', 'has-project'])]
class RedirectController extends Controller
{
    #[Get('/', name: 'redirects')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [Redirect::class, $site, $server]);

        return Inertia::render('redirects/index', [
            'redirects' => RedirectTable::make($site->redirects()->with('site'))->toInertia(),
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

    #[Delete('/{redirect}', name: 'redirects.destroy')]
    public function destroy(Server $server, Site $site, Redirect $redirect): RedirectResponse
    {
        $this->authorize('delete', [$redirect, $site, $server]);

        app(DeleteRedirect::class)->delete($site, $redirect);

        return back()
            ->with('info', 'Deleting the redirect');
    }
}
