<?php

namespace App\Providers;

use App\Facades\SSH;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class DemoServiceProvider extends ServiceProvider
{
    protected string $error = 'Cannot modify on Demo!';

    protected array $canDelete = [
        //
    ];

    protected array $canUpdate = [
        'App\Models\ServerLog',
        'App\Models\Script',
        'App\Models\ScriptExecution',
    ];

    protected array $canCreate = [
        'App\Models\ServerLog',
        'App\Models\Script',
        'App\Models\ScriptExecution',
        'App\Models\FirewallRule',
        'App\Models\PersonalAccessToken',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! config('app.demo') || app()->runningInConsole()) {
            return;
        }

        // get all classes inside App\Models namespace
        $models = collect(scandir(app_path('Models')))
            ->filter(fn ($file) => ! in_array($file, ['.', '..']))
            ->map(fn ($file) => 'App\\Models\\'.str_replace('.php', '', $file));

        foreach ($models as $model) {
            if (! in_array($model, $this->canCreate)) {
                $this->preventCreating($model);
            }
            if (! in_array($model, $this->canUpdate)) {
                $this->preventUpdating($model);
            }
            if (! in_array($model, $this->canDelete)) {
                $this->preventDeletion($model);
            }
        }

        SSH::fake('Demo SSH is enabled. No SSH commands will be executed.');
        Http::fake([
            '*' => Http::response([]),
        ]);

        config()->set('queue.default', 'sync');
        config()->set('logging.default');
        config()->set('session.driver', 'file');
    }

    private function preventUpdating(string $model): void
    {
        $model::updating(function ($m) {
            $throw = true;
            if ($m instanceof User && ! $m->isDirty(['name', 'email', 'password', 'two_factor_secret', 'two_factor_recovery_codes'])) {
                $throw = false;
            }
            if ($throw) {
                abort(403, $this->error);
            }
        });
    }

    private function preventDeletion(string $model): void
    {
        $model::deleting(function ($m) {
            abort(403, $this->error);
        });
    }

    private function preventCreating(string $model): void
    {
        $model::creating(function ($m) {
            abort(403, $this->error);
        });
    }
}
