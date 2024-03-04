<?php

namespace App\Http\Controllers;

use App\Actions\SshKey\CreateSshKey;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\Server;
use App\Models\SshKey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SSHKeyController extends Controller
{
    public function index(Server $server): View
    {
        return view('server-ssh-keys.index', [
            'server' => $server,
            'keys' => $server->sshKeys,
        ]);
    }

    public function store(Server $server, Request $request): HtmxResponse
    {
        /** @var \App\Models\SshKey $key */
        $key = app(CreateSshKey::class)->create(
            $request->user(),
            $request->input()
        );

        $key->deployTo($server);

        Toast::success('SSH Key added and being deployed to the server.');

        return htmx()->back();
    }

    public function destroy(Server $server, SshKey $sshKey): RedirectResponse
    {
        $sshKey->deleteFrom($server);

        Toast::success('SSH Key is being deleted.');

        return back();
    }

    public function deploy(Server $server, Request $request): HtmxResponse
    {
        $this->validate($request, [
            'key_id' => 'required|exists:ssh_keys,id',
        ]);

        /** @var SshKey $sshKey */
        $sshKey = SshKey::query()->findOrFail($request->input('key_id'));

        $sshKey->deployTo($server);

        Toast::success('SSH Key is being deployed to the server.');

        return htmx()->back();
    }
}
