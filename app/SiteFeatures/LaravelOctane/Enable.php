<?php

namespace App\SiteFeatures\LaravelOctane;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\Models\Worker;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class Enable extends Action
{
    public function name(): string
    {
        return 'Enable';
    }

    public function active(): bool
    {
        return ! data_get($this->site->type_data, 'octane', false);
    }

    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('port')
                ->text()
                ->default(8000),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        Validator::make($request->all(), [
            'port' => 'required|integer|min:1|max:65535',
        ])->validate();

        $this->site->server->ssh()->exec(
            __('php :path/artisan octane:install --no-interaction', [
                'path' => $this->site->path,
            ]),
            'install-laravel-octane',
        );

        $command = __('php :path/artisan octane:start --port=:port --host=127.0.0.1', [
            'path' => $this->site->path,
            'port' => $request->input('port'),
        ]);

        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'laravel-octane')->first();
        if ($worker) {
            app(ManageWorker::class)->restart($worker);
        } else {
            app(CreateWorker::class)->create(
                $this->site->server,
                [
                    'name' => 'laravel-octane',
                    'command' => $command,
                    'user' => $this->site->user ?? $this->site->server->getSshUser(),
                    'auto_start' => true,
                    'auto_restart' => true,
                    'numprocs' => 1,
                ],
                $this->site,
            );
        }

        $typeData = $this->site->type_data ?? [];
        data_set($typeData, 'octane', true);
        data_set($typeData, 'octane_port', $request->input('port'));
        $this->site->type_data = $typeData;
        $this->site->save();

        $this->updateVHost();

        $request->session()->flash('success', 'Laravel Octane has been enabled for this site.');
    }

    private function updateVHost(): void
    {
        $webserver = $this->site->webserver();

        if ($webserver->id() === 'nginx') {
            $this->site->webserver()->updateVHost(
                $this->site,
                replace: [
                    'php' => view('ssh.services.webserver.nginx.vhost-blocks.laravel-octane', ['site' => $this->site]),
                    'laravel-octane-map' => '',
                ],
                append: [
                    'header' => view('ssh.services.webserver.nginx.vhost-blocks.laravel-octane-map', ['site' => $this->site]),
                ]
            );

            return;
        }

        throw new RuntimeException('Unsupported webserver: '.$webserver->id());
    }
}
