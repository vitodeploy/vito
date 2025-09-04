<?php

namespace App\ServerFeatures\Motd;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
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

    public function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('position')
                ->select()
                ->label('Position')
                ->options([
                    MotdPosition::START,
                    MotdPosition::END,
                    MotdPosition::REPLACE,
                ])
                ->default(MotdPosition::END)
                ->description('Choose where to display the message of the day.'),
            DynamicField::make('content')
                ->textarea()
                ->label('Content')
                ->className('font-mono')
                ->default(view('ssh.motd.default')->render()),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $this->ssh = $this->server->ssh($this->server->ssh_user);

        $position = $request->input('position');

        $file = match ($position) {
            MotdPosition::END => '/etc/update-motd.d/99-vito',
            default => '/etc/update-motd.d/00-vito',
        };

        $this->ssh->write($file, $request->input('content'), 'root');

        if ($position === MotdPosition::REPLACE) {
            $this->ssh->exec('chmod -x /etc/update-motd.d/*', 'disable-current-motd', $this->server->id);
        }

        $this->ssh->exec('chmod +x '.$file, 'enable-motd', $this->server->id);

        $featureData = $this->server->feature_data ?? [];
        data_set($featureData, 'motd', true);
        data_set($featureData, 'motd_position', $position);
        $this->server->feature_data = $featureData;
        $this->server->save();

        $request->session()->flash('success', 'MOTD enabled!');
    }
}
