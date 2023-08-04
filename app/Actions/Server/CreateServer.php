<?php

namespace App\Actions\Server;

use App\Enums\FirewallRuleStatus;
use App\Exceptions\ServerProviderError;
use App\Jobs\Installation\ContinueInstallation;
use App\Models\Server;
use App\Models\User;
use App\ValidationRules\RestrictedIPAddressesRule;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateServer
{
    /**
     * @throws Throwable
     */
    public function create(User $creator, array $input): Server
    {
        $this->validateInputs($input);

        $server = new Server([
            'user_id' => $creator->id,
            'name' => $input['name'],
            'ssh_user' => config('core.server_providers_default_user')[$input['provider']][$input['os']],
            'ip' => $input['ip'],
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

        try {
            DB::beginTransaction();

            if ($server->provider != 'custom') {
                $server->provider_id = $input['server_provider'];
            }

            // validate type
            $this->validateType($server, $input);
            $server->type_data = $server->type()->data($input);

            // validate provider
            $this->validateProvider($server, $input);
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
            if ($server->provider == 'custom') {
                $server->install();
            } else {
                $server->progress_step = __('Installation will begin in 3 minutes!');
                $server->save();
                dispatch(new ContinueInstallation($server))
                    ->delay(now()->addMinutes(2));
            }
            DB::commit();

            return $server;
        } catch (Exception $e) {
            $server->provider()->delete();
            DB::rollBack();
            if ($e instanceof ServerProviderError) {
                throw ValidationException::withMessages([
                    'provider' => __('Provider Error: ').$e->getMessage(),
                ])->errorBag('createServer');
            }
            throw $e;
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateInputs(array $input): void
    {
        $rules = [
            'provider' => 'required|in:'.implode(',', config('core.server_providers')),
            'name' => 'required',
            'os' => 'required|in:'.implode(',', config('core.operating_systems')),
            'type' => [
                'required',
                Rule::in(config('core.server_types')),
            ],
        ];

        Validator::make($input, $rules)->validate();

        if ($input['provider'] != 'custom') {
            $rules['server_provider'] = 'required|exists:server_providers,id,user_id,'.auth()->user()->id;
        }

        if ($input['provider'] == 'custom') {
            $rules['ip'] = [
                'required',
                'ip',
                new RestrictedIPAddressesRule(),
            ];
            $rules['port'] = [
                'required',
                'numeric',
                'min:1',
                'max:65535',
            ];
        }

        Validator::make($input, $rules)->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validateType(Server $server, array $input): void
    {
        Validator::make($input, $server->type()->createValidationRules($input))
            ->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validateProvider(Server $server, array $input): void
    {
        Validator::make($input, $server->provider()->createValidationRules($input))
            ->validate();
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
