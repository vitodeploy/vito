<?php

namespace App\Http\Controllers;

use App\Actions\SshKey\CreateSshKey;
use App\Http\Resources\SshKeyResource;
use App\Models\SshKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/ssh-keys')]
#[Middleware(['auth'])]
class SSHKeyController extends Controller
{
    #[Get('/', name: 'ssh-keys')]
    public function index(): Response
    {
        $this->authorize('viewAny', SshKey::class);

        return Inertia::render('ssh-keys/index', [
            'sshKeys' => SshKeyResource::collection(user()->sshKeys()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'ssh-keys.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SshKey::class);

        app(CreateSshKey::class)->create(user(), $request->input());

        return back()->with('success', 'SSH key created.');
    }

    #[Delete('/{sshKey}', name: 'ssh-keys.destroy')]
    public function destroy(SshKey $sshKey): RedirectResponse
    {
        $this->authorize('delete', $sshKey);

        $sshKey->delete();

        return back()->with('success', 'SSH key deleted.');
    }
}
