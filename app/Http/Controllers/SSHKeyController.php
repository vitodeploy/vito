<?php

namespace App\Http\Controllers;

use App\Actions\SshKey\CreateSshKey;
use App\Actions\SshKey\DeleteKeyFromServer;
use App\Actions\SshKey\DeployKeyToServer;
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

        $request->merge(['key_id' => $key->id]);

        return $this->deploy($server, $request);
    }

    public function destroy(Server $server, SshKey $sshKey): RedirectResponse
    {
        app(DeleteKeyFromServer::class)->delete($server, $sshKey);

        Toast::success('SSH Key has been deleted.');

        return back();
    }

    public function deploy(Server $server, Request $request): HtmxResponse
    {
        app(DeployKeyToServer::class)->deploy(
            $request->user(),
            $server,
            $request->input()
        );

        Toast::success('SSH Key has been deployed to the server.');

        return htmx()->back();
    }
}
