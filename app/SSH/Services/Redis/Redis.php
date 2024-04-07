<?php

namespace App\SSH\Services\Redis;

use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;

class Redis extends AbstractService
{
    use HasScripts;

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    $redisExists = $this->service->server->service('redis');
                    if ($redisExists) {
                        $fail('You already have a Redis service on the server.');
                    }
                },
            ],
        ];
    }

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('install.sh'),
            'install-redis'
        );
    }

    public function uninstall(): void
    {
        //
    }
}
