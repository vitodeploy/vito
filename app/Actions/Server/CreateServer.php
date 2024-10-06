<?php

namespace App\Actions\Server;

use App\Enums\FirewallRuleStatus;
use App\Enums\ServerProvider;
use App\Enums\ServerStatus;
use App\Facades\Notifier;
use App\Models\Server;
use App\Models\User;
use App\Notifications\ServerInstallationFailed;
use App\Notifications\ServerInstallationSucceed;
use App\ValidationRules\RestrictedIPAddressesRule;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CreateServer
{
    /**
     * @throws Throwable
     */
    public function create(User $creator, array $input): Server
    {
        $server = new Server([
            'project_id' => $creator->currentProject->id,
            'user_id' => $creator->id,
            'name' => $input['name'],
            'ssh_user' => config('core.server_providers_default_user')[$input['provider']][$input['os']],
            'ip' => $input['ip'] ?? '',
            'port' => $input['port'] ?? 22,
            'os' => $input['os'],
            'type' => $input['type'],
            'provider' => $input['provider'],
            'authentication' => [
                'user' => config('core.ssh_user'),
                'pass' => Str::random(15),
                'root_pass' => Str::random(15),
            ],
            'progress' => 0,
            'progress_step' => 'Initializing',
        ]);

        DB::beginTransaction();
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

            DB::commit();

            return $server;
        } catch (Exception $e) {
            $server->provider()->delete();
            DB::rollBack();
            throw $e;
        }
    }

    private function install(Server $server): void
    {
        $bus = Bus::chain([
            function () use ($server) {
                if (! $server->provider()->isRunning()) {
                    sleep(2);
                }
                $server->type()->install();
                $server->update([
                    'status' => ServerStatus::READY,
                ]);
                Notifier::send($server, new ServerInstallationSucceed($server));
            },
        ])->catch(function (Throwable $e) use ($server) {
            $server->update([
                'status' => ServerStatus::INSTALLATION_FAILED,
            ]);
            Notifier::send($server, new ServerInstallationFailed($server));
            Log::error('server-installation-error', [
                'error' => (string) $e,
            ]);
        });

        if ($server->provider != ServerProvider::CUSTOM) {
            $server->progress_step = 'waiting-for-provider';
            $server->save();
            $bus->delay(now()->addMinutes(3));
        }

        $bus->onConnection('ssh')->dispatch();
    }

    public static function rules(array $input): array
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
            'type' => [
                'required',
                Rule::in(config('core.server_types')),
            ],
            'server_provider' => [
                Rule::when(function () use ($input) {
                    return $input['provider'] != ServerProvider::CUSTOM;
                }, [
                    'required',
                    'exists:server_providers,id,user_id,'.auth()->user()->id,
                ]),
            ],
            'ip' => [
                Rule::when(function () use ($input) {
                    return $input['provider'] == ServerProvider::CUSTOM;
                }, [
                    'required',
                    new RestrictedIPAddressesRule,
                ]),
            ],
            'port' => [
                Rule::when(function () use ($input) {
                    return $input['provider'] == ServerProvider::CUSTOM;
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

    private function createFirewallRules(Server $server): void
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
