<?php

namespace App\SSH\Services\EmailServers;

use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;

class Postfix extends AbstractService
{
    use HasScripts;

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('postfix/install.sh'),
            'install-postfix'
        );
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    $postfixExists = $this->service->server->emailService();
                    if ($postfixExists) {
                        $fail('You already have a Redis service on the server.');
                    }
                },
            ],
        ];
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('postfix/uninstall.sh'),
            'uninstall-postfix'
        );
        $this->service->server->os()->cleanup();
    }
}
