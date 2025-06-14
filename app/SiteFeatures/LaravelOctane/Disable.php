<?php

namespace App\SiteFeatures\LaravelOctane;

use App\Actions\Worker\DeleteWorker;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Models\Worker;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Disable extends Action
{
    public function name(): string
    {
        return 'Disable';
    }

    public function active(): bool
    {
        return data_get($this->site->type_data, 'octane', false);
    }

    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('port')
                ->text()
                ->default(8000),
        ]);
    }

    public function handle(Request $request): void
    {
        $typeData = $this->site->type_data ?? [];

        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'laravel-octane')->first();
        if ($worker) {
            app(DeleteWorker::class)->delete($worker);
        }

        data_set($typeData, 'octane', false);
        data_set($typeData, 'octane_port', $request->input('port'));
        $this->site->type_data = $typeData;
        $this->site->save();

        $webserver = $this->site->webserver()->id();

        if ($webserver === 'nginx') {
            $this->site->webserver()->updateVHost(
                $this->site,
                replace: [
                    'laravel-octane-map' => '',
                    'laravel-octane' => view('ssh.services.webserver.nginx.vhost-blocks.php', ['site' => $this->site]),
                ],
            );
        }

        $request->session()->flash('success', 'Laravel Octane has been disabled for this site.');
    }
}
