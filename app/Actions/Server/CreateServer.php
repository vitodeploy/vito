<?php

namespace App\Actions\Server;

use App\Enums\FirewallRuleStatus;
use App\Enums\ServerStatus;
use App\Facades\Notifier;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Notifications\ServerInstallationFailed;
use App\ServerProviders\Custom;
use App\ValidationRules\RestrictedIPAddressesRule;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateServer
{
    protected Server $server;

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $creator, Project $project, array $input): Server
    {
        Validator::make($input, self::rules($project, $input))->validate();

        $this->server = new Server([
            'project_id' => $project->id,
            'user_id' => $creator->id,
            'name' => $input['name'],
            'ssh_user' => data_get(config('server-provider.providers'), $input['provider'].'.default_user') ?? 'root',
            'ip' => $input['ip'] ?? '',
            'port' => $input['port'] ?? 22,
            'os' => $input['os'],
            'provider' => $input['provider'],
            'authentication' => [
                'user' => config('core.ssh_user'),
                'pass' => Str::random(15),
                'root_pass' => Str::random(15),
            ],
            'progress' => 0,
            'progress_step' => 'Initializing',
        ]);

        try {
            if ($this->server->provider != 'custom') {
                $this->server->provider_id = $input['server_provider'];
            }

            $this->server->provider_data = $this->server->provider()->data($input);

            // save
            $this->server->save();

            // create firewall rules
            $this->createFirewallRules($this->server);

            // create instance
            $this->server->provider()->create();

            // create services
            $this->createServices();

            // install server
            dispatch(function (): void {
                app(InstallServer::class)->run($this->server);
            })
                ->catch(function (Throwable $e): void {
                    $this->server->update([
                        'status' => ServerStatus::INSTALLATION_FAILED,
                    ]);
                    Notifier::send($this->server, new ServerInstallationFailed($this->server));
                    Log::error('server-installation-error', [
                        'error' => (string) $e,
                    ]);
                })
                ->onQueue('ssh');

            return $this->server;
        } catch (Exception $e) {
            $this->server->delete();

            throw ValidationException::withMessages([
                'provider' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(Project $project, array $input): array
    {
        $rules = [
            'provider' => [
                'required',
                Rule::in(array_keys(config('server-provider.providers'))),
            ],
            'name' => [
                'required',
            ],
            'os' => [
                'required',
                Rule::in(config('core.operating_systems')),
            ],
            'server_provider' => [
                Rule::when(fn (): bool => isset($input['provider']) && $input['provider'] != Custom::id(), [
                    'required',
                    Rule::exists('server_providers', 'id')->where(function (Builder $query) use ($project): void {
                        $query->where('project_id', $project->id)
                            ->orWhereNull('project_id');
                    }),
                ]),
            ],
            'ip' => [
                Rule::when(fn (): bool => isset($input['provider']) && $input['provider'] == Custom::id(), [
                    'required',
                    new RestrictedIPAddressesRule,
                ]),
            ],
            'port' => [
                Rule::when(fn (): bool => isset($input['provider']) && $input['provider'] == Custom::id(), [
                    'required',
                    'numeric',
                    'min:1',
                    'max:65535',
                ]),
            ],
        ];

        return array_merge($rules, self::providerRules($input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string>>
     */
    private static function providerRules(array $input): array
    {
        if (
            ! isset($input['provider']) ||
            ! isset($input['server_provider']) ||
            ! config('server-provider.providers.'.$input['provider']) ||
            $input['provider'] == Custom::id()
        ) {
            return [];
        }

        $server = new Server([
            'provider' => $input['provider'],
            'provider_id' => $input['server_provider'],
        ]);

        return $server->provider()->createRules($input);
    }

    public function createFirewallRules(Server $server): void
    {
        $server->firewallRules()->createMany([
            [
                'type' => 'allow',
                'name' => 'SSH',
                'protocol' => 'tcp',
                'port' => 22,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'name' => 'HTTP',
                'protocol' => 'tcp',
                'port' => 80,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'name' => 'HTTPS',
                'protocol' => 'tcp',
                'port' => 443,
                'source' => null,
                'mask' => null,
                'status' => FirewallRuleStatus::READY,
            ],
        ]);
    }

    private function createServices(): void
    {
        $this->server->services()->forceDelete();
        $this->addUfw();
        $this->addMonitoring();
    }

    private function addUfw(): void
    {
        $this->server->services()->create([
            'type' => 'firewall',
            'name' => 'ufw',
            'version' => 'latest',
        ]);
    }

    private function addMonitoring(): void
    {
        $this->server->services()->create([
            'type' => 'monitoring',
            'name' => 'remote-monitor',
            'version' => 'latest',
        ]);
    }
}
