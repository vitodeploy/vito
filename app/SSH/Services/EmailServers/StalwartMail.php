<?php

namespace App\SSH\Services\EmailServers;

use App\Actions\Site\CreateSite;
use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;
use Throwable;

class StalwartMail extends AbstractService
{
    use HasScripts;

    /**
     * @throws \Exception
     */
    public function install(): void
    {
        $site = app(CreateSite::class)->create($this->service->server, [
            'type' => \App\Enums\SiteType::REVERSE_PROXY,
            'port' => '8080',
            'domain' => data_get($this->service, 'type_data.domain'),
        ]);

        if (! $site) {
            throw new \Exception('Failed to install stalwart-mail, the app domain not created.');
        }

        $this->service->server->ssh()->exec(
            $this->getScript('stalwart/install.sh', [
                'domain' => data_get($this->service, 'type_data.domain')
            ]),
            'install-stalwart'
        );
    }

    /**
     * @throws Throwable
     */
    public function restart(int $id, ?int $siteId = null): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('stalwart/restart.sh', [
                'id' => $id,
            ]),
            'restart-stalwart'
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
                    $serviceExists = $this->service->server->emailService();
                    if ($serviceExists) {
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
            $this->getScript('stalwart/uninstall.sh'),
            'uninstall-stalwart'
        );
        $this->service->server->os()->cleanup();
    }
}
