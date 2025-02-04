<?php

namespace App\Actions\Server;

use App\Enums\FirewallRuleStatus;
use App\Enums\ServerProvider;
use App\Enums\ServerStatus;
use App\Enums\ServerType;
use App\Exceptions\SSHConnectionError;
use App\Facades\Notifier;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Notifications\ServerInstallationFailed;
use App\Notifications\ServerInstallationSucceed;
use App\ValidationRules\RestrictedIPAddressesRule;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateServer
{
    public function create(User $creator, Project $project, array $input): Server
    {
        $server = new Server([
            'project_id' => $project->id,
            'user_id' => $creator->id,
            'name' => $input['name'],
            'ssh_user' => config('core.server_providers_default_user')[$input['provider']][$input['os']],
            'ip' => $input['ip'] ?? '',
            'port' => $input['port'] ?? 22,
            'os' => $input['os'],
            'type' => ServerType::REGULAR,
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
            if ($server->provider != 'custom') {
                $server->provider_id = $input['server_provider'];
            }

            $server->type_data = $server->type()->data($input);

            $server->provider_data = $server->provider()->data($input);

            // save
            $server->save();

            // create firewall rules
            $this->createFirewallRules($server);

            // create instance
            $server->provider()->create();

            // add services
            $server->type()->createServices($input);

            // install server
            $this->install($server);

            return $server;
        } catch (Exception $e) {
            $server->delete();

            throw ValidationException::withMessages([
                'provider' => $e->getMessage(),
            ]);
        }
    }

    private function install(Server $server): void
    {
        dispatch(function () use ($server) {
            $maxWait = 180;
            while ($maxWait > 0) {
                sleep(10);
                $maxWait -= 10;
                if (! $server->provider()->isRunning()) {
                    continue;
                }
                try {
                    $server->ssh()->connect();
                    break;
                } catch (SSHConnectionError) {
                    // ignore
                }
            }
            $server->type()->install();
            $server->update([
                'status' => ServerStatus::READY,
            ]);
            Notifier::send($server, new ServerInstallationSucceed($server));
        })
            ->catch(function (Throwable $e) use ($server) {
                $server->update([
                    'status' => ServerStatus::INSTALLATION_FAILED,
                ]);
                Notifier::send($server, new ServerInstallationFailed($server));
                Log::error('server-installation-error', [
                    'error' => (string) $e,
                ]);
            })
            ->onConnection('ssh');
    }

    public static function rules(Project $project, array $input): array
    {
        $rules = [
            'provider' => [
                'required',
                Rule::in(config('core.server_providers')),
            ],
            'name' => [
                'required',
            ],
            'os' => [
                'required',
                Rule::in(config('core.operating_systems')),
            ],
            'server_provider' => [
                Rule::when(function () use ($input) {
                    return isset($input['provider']) && $input['provider'] != ServerProvider::CUSTOM;
                }, [
                    'required',
                    Rule::exists('server_providers', 'id')->where(function (Builder $query) use ($project) {
                        $query->where('project_id', $project->id)
                            ->orWhereNull('project_id');
                    }),
                ]),
            ],
            'ip' => [
                Rule::when(function () use ($input) {
                    return isset($input['provider']) && $input['provider'] == ServerProvider::CUSTOM;
                }, [
                    'required',
                    new RestrictedIPAddressesRule,
                ]),
            ],
            'port' => [
                Rule::when(function () use ($input) {
                    return isset($input['provider']) && $input['provider'] == ServerProvider::CUSTOM;
                }, [
                    'required',
                    'numeric',
                    'min:1',
                    'max:65535',
                ]),
            ],
        ];

        return array_merge($rules, self::typeRules($input), self::providerRules($input));
    }

    private static function typeRules(array $input): array
    {
        if (! isset($input['type']) || ! in_array($input['type'], config('core.server_types'))) {
            return [];
        }

        $server = new Server(['type' => $input['type']]);

        return $server->type()->createRules($input);
    }

    private static function providerRules(array $input): array
    {
        if (
            ! isset($input['provider']) ||
            ! isset($input['server_provider']) ||
            ! in_array($input['provider'], config('core.server_providers')) ||
            $input['provider'] == ServerProvider::CUSTOM
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
                'protocol' => 'ssh',
                'port' => 22,
                'source' => '0.0.0.0',
                'mask' => 0,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'protocol' => 'http',
                'port' => 80,
                'source' => '0.0.0.0',
                'mask' => 0,
                'status' => FirewallRuleStatus::READY,
            ],
            [
                'type' => 'allow',
                'protocol' => 'https',
                'port' => 443,
                'source' => '0.0.0.0',
                'mask' => 0,
                'status' => FirewallRuleStatus::READY,
            ],
        ]);
    }
}
