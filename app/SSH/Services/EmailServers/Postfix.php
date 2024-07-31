<?php

namespace App\SSH\Services\EmailServers;

use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class Postfix extends AbstractService
{
    use HasScripts;

    public function install(): void
    {
        Log::debug('Test; ' . data_get($this->service, 'type_data.domain'));
        $this->service->server->ssh()->exec(
            $this->getScript('postfix/install.sh', [
                'domain' => data_get($this->service, 'type_data.domain')
            ]),
            'install-postfix'
        );
    }

    public function creationData(array $input): array
    {
        return [
            'domain' => $input['domain'],
        ];
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    $postfixExists = $this->service->server->emailService();
                    if ($postfixExists) {
                        $fail('You already have a email service on the server.');
                    }
                },
            ],
            'domain' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (empty($value)) {
                        $fail('A domain is required to setup your email service.');
                    }

                    $validateDomain = filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
                    if (! $validateDomain) {
                        $fail('The domain you specified is not valid.');
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
