<?php

namespace App\ApplicationTypes;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\DTOs\SettingsField;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class ReverseProxy extends AbstractApplicationType
{
    public static function id(): string
    {
        return 'reverse-proxy';
    }

    public static function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('host')
                ->text()
                ->label('Target Host')
                ->placeholder('localhost')
                ->description('Backend host to proxy to'),
            DynamicField::make('port')
                ->text()
                ->label('Target Port')
                ->placeholder('3000')
                ->description('Backend port'),
            DynamicField::make('scheme')
                ->select()
                ->label('Scheme')
                ->options(['http', 'https'])
                ->default('http'),
            DynamicField::make('websocket')
                ->checkbox()
                ->label('WebSocket Support')
                ->description('Enable WebSocket upgrade headers'),
        ]);
    }

    /**
     * @return array<SettingsField>
     */
    public static function settingsFields(): array
    {
        return [
            SettingsField::make('domain')->label('Domain')->fromApplication()->asLink(),
            SettingsField::make('aliases')->label('Aliases')->fromApplication(),
            SettingsField::make('type')->label('Type')->fromApplication()->asBadge(),
            SettingsField::make('host')->label('Target Host')->fromTypeData(),
            SettingsField::make('port')->label('Target Port')->fromTypeData(),
            SettingsField::make('scheme')->label('Scheme')->fromTypeData(),
            SettingsField::make('websocket')->label('WebSocket')->fromTypeData()->asBoolean(),
            SettingsField::make('force_ssl')->label('Force SSL')->fromApplication()->asBoolean(),
            SettingsField::make('status')->label('Status')->fromApplication()->asBadge(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array
    {
        return [
            'host' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'port' => ['required', 'numeric', 'min:1', 'max:65535'],
            'scheme' => ['required', 'in:http,https'],
            'websocket' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function data(array $input): array
    {
        return [
            'host' => $input['host'] ?? 'localhost',
            'port' => (int) ($input['port'] ?? 3000),
            'scheme' => $input['scheme'] ?? 'http',
            'websocket' => (bool) ($input['websocket'] ?? false),
        ];
    }

    public function install(): void
    {
        // No-op: the deploy action (called by CreateJob after install) handles vhost creation.
    }

    public function uninstall(): void
    {
        $safeDomain = escapeshellarg($this->application->domain);
        $server = $this->application->server;
        $webserverId = $this->application->webserverId();

        $basePath = match ($webserverId) {
            'nginx' => '/etc/nginx',
            'caddy' => '/etc/caddy',
            default => throw new \RuntimeException("Unsupported webserver: {$webserverId}"),
        };

        $server->ssh()->exec(
            "sudo rm -f {$basePath}/sites-available/{$safeDomain} {$basePath}/sites-enabled/{$safeDomain}",
            'delete-vhost'
        );

        /** @var \App\Models\Service $webserverService */
        $webserverService = $server->webserver();
        $webserverService->restart();
    }

    public function vhost(string $webserver): string|View
    {
        if ($webserver === 'nginx') {
            return view('ssh.services.webserver.nginx.vhost-proxy', [
                'application' => $this->application,
            ]);
        }

        if ($webserver === 'caddy') {
            return view('ssh.services.webserver.caddy.vhost-proxy', [
                'application' => $this->application,
            ]);
        }

        return '';
    }

    public function vhostTemplate(string $webserver): string
    {
        $viewName = match ($webserver) {
            'nginx' => 'ssh.services.webserver.nginx.vhost-proxy',
            'caddy' => 'ssh.services.webserver.caddy.vhost-proxy',
            default => '',
        };

        if (! $viewName) {
            return '';
        }

        $path = resource_path('views/'.str_replace('.', '/', $viewName).'.blade.php');

        return File::exists($path) ? File::get($path) : '';
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editRules(array $input): array
    {
        return $this->createRules($input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(array $input): void
    {
        $this->application->type_data = $this->data($input);
        $this->application->save();
    }

}
