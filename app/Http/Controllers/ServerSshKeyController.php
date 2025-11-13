<?php

namespace App\Http\Controllers;

use App\Actions\SshKey\DeleteKeyFromServer;
use App\Actions\SshKey\DeployKeyToServer;
use App\Exceptions\SSHError;
use App\Http\Resources\SshKeyResource;
use App\Models\Server;
use App\Models\SshKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/ssh-keys')]
#[Middleware(['auth', 'has-project'])]
class ServerSshKeyController extends Controller
{
    #[Get('/', name: 'server-ssh-keys')]
    public function index(Server $server): Response
    {
        $this->authorize('view', $server);

        return Inertia::render('server-ssh-keys/index', [
            'sshKeys' => SshKeyResource::collection($server->sshKeys()->with('user')->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    /**
     * @throws SSHError
     */
    #[Post('/', name: 'server-ssh-keys.store')]
    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        /** @var ?SshKey $sshKey */
        $sshKey = user()->sshKeys()->find($request->input('key'));

        if (! $sshKey) {
            throw ValidationException::withMessages([
                'key' => ['The selected SSH key does not exist.'],
            ]);
        }

        app(DeployKeyToServer::class)->deploy($server, $sshKey, $request->input());

        return back()->with('success', 'SSH key deployed.');
    }

    /**
     * @throws SSHError
     */
    #[Delete('/{sshKey}', name: 'server-ssh-keys.destroy')]
    public function destroy(Server $server, SshKey $sshKey): RedirectResponse
    {
        $this->authorize('update', $server);

        app(DeleteKeyFromServer::class)->delete($server, $sshKey);

        return back()->with('success', 'SSH key deleted.');
    }
}
