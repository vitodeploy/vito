<?php

namespace App\ServerFeatures\Motd;

use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\ServerFeatures\Action;
use Illuminate\Http\Request;

class Enable extends Action
{
    protected SSH $ssh;

    public function name(): string
    {
        return 'Enable';
    }

    public function active(): bool
    {
        return ! data_get($this->server->feature_data, 'motd', false);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->ssh = $this->server->ssh($this->server->ssh_user);

        $this->ssh->exec(view('ssh.motd.enable', [
            'motd' => 'Welcome to your server!',
        ]), 'enable-motd', $this->server->id);

        $featureData = $this->server->feature_data ?? [];
        data_set($featureData, 'motd', true);
        $this->server->feature_data = $featureData;
        $this->server->save();

        $request->session()->flash('success', 'MOTD enabled!');
    }
}
