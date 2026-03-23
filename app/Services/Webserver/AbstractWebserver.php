<?php

namespace App\Services\Webserver;

use App\Enums\SslMethod;
use App\Models\Site;
use App\Services\AbstractService;
use Closure;

abstract class AbstractWebserver extends AbstractService implements Webserver
{
    public function creationRules(array $input): array
    {
        return [
            'name' => [
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

    public function createsSiteSSLs(): bool
    {
        $name = static::id();

        return (bool) data_get(config("service.services.{$name}.data"), 'creates_site_ssls', true);
    }

    public function siteDefaults(): array
    {
        return [];
    }

    public function canConfigureSSL(): bool
    {
        return true;
    }

    public function allowedSslMethods(): ?array
    {
        return null;
    }

    public function defaultSslMethod(): SslMethod
    {
        return SslMethod::NONE;
    }

    abstract public function generateVhost(Site $site, ?string $template = null): string;
}
