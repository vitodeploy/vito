<?php

namespace App\SSH\Services\Webserver;

use App\SSH\Services\AbstractService;
use Closure;

abstract class AbstractWebserver extends AbstractService implements Webserver
{
    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $webserverExists = $this->service->server->webserver();
                    if ($webserverExists) {
                        $fail('You already have a webserver service on the server.');
                    }
                },
            ],
        ];
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hasSite = $this->service->server->sites()
                        ->exists();
                    if ($hasSite) {
                        $fail('Cannot uninstall webserver while you have websites using it.');
                    }
                },
            ],
        ];
    }
}
