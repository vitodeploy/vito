<?php

namespace App\ServerFeatures\Motd;

use App\Exceptions\SSHError;
use App\Helpers\SSH;
use App\ServerFeatures\Action;
use Illuminate\Http\Request;

class Disable extends Action
{
    protected SSH $ssh;

    public function name(): string
    {
        return 'Disable';
    }

    public function active(): bool
    {
        return data_get($this->server->feature_data, 'motd', false);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->ssh = $this->server->ssh($this->server->ssh_user);

        $featureData = $this->server->feature_data ?? [];

        $file = match ($featureData['motd_position']) {
            MotdPosition::END => '/etc/update-motd.d/99-vito',
            default => '/etc/update-motd.d/00-vito',
        };

        $this->server->os()->deleteFile($file, 'root');

        if ($featureData['motd_position'] === MotdPosition::REPLACE) {
            $this->ssh->exec('chmod +x /etc/update-motd.d/*', 'restore-motd', $this->server->id);
        }

        data_set($featureData, 'motd', false);
        $this->server->feature_data = $featureData;
        $this->server->save();

        $request->session()->flash('success', 'MOTD disabled!');
    }
}
