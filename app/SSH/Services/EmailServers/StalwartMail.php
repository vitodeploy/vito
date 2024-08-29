<?php

namespace App\SSH\Services\EmailServers;

use App\Actions\Site\CreateSite;
use App\Actions\Site\DeleteSite;
use App\Models\Site;
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
        $site = $this->siteDomain(
            data_get($this->service, 'type_data.site_domain')
        );

        if ($site) {
            throw new \Exception('Failed to install stalwart-mail when creating the app panel domain, already in use.');
        }

        app(CreateSite::class)->create($this->service->server, [
            'type' => \App\Enums\SiteType::REVERSE_PROXY,
            'port' => '8080',
            'auto-installed' => 'stalwart-mail',
            'domain' => data_get($this->service, 'type_data.site_domain'),
        ]);

        $this->service->server->ssh()->exec(
            $this->getScript('stalwart/install.sh', [
                'domain' => data_get($this->service, 'type_data.domain'),
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
            'site_domain' => $input['site_domain'],
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
            'site_domain' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (empty($value)) {
                        $fail('A domain is required to access Stalwart panel.');
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
        $site = $this->siteDomain(
            data_get($this->service, 'type_data.site_domain'),
        );

        if (data_get($site, 'type_data.auto-installed') === 'stalwart-mail') {
            app(DeleteSite::class)->delete(
                $site
            );
        }

        $this->service->server->ssh()->exec(
            $this->getScript('stalwart/uninstall.sh'),
            'uninstall-stalwart'
        );
        $this->service->server->os()->cleanup();
    }

    private function siteDomain($domain)
    {
        return Site::query()
            ->where('server_id', $this->service->server->id)
            ->where('domain', $domain)
            ->first();
    }
}
