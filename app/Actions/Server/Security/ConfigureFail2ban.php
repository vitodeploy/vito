<?php

namespace App\Actions\Server\Security;

use App\Jobs\Server\Security\ConfigureFail2banJob;
use App\Models\Server;
use App\Models\Service;
use App\Services\Fail2ban\Fail2ban;
use Illuminate\Support\Facades\Validator;

class ConfigureFail2ban
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function configure(Server $server, array $input): void
    {
        $service = $server->fail2ban();

        if (! $service instanceof Service) {
            return;
        }

        Validator::make($input, Fail2ban::jailValidationRules())->validate();

        $service->type_data = [
            'maxretry' => (int) ($input['maxretry'] ?? 5),
            'findtime' => $input['findtime'] ?? '10m',
            'bantime' => $input['bantime'] ?? '10m',
            'ignoreip' => $input['ignoreip'] ?? '',
        ];
        $service->save();

        dispatch(new ConfigureFail2banJob($service))->onQueue('ssh');
    }
}
