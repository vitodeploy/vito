<?php

namespace App\Actions\ApplicationType;

use App\Models\Application;
use App\Models\Deployment;
use RuntimeException;

class DeployReverseProxy
{
    public function deploy(Application $application, Deployment $deployment): void
    {
        $deployment->log?->write('Generating vhost configuration...');

        $webserverId = $application->webserverId();
        $vhost = $this->generateVhost($application, $webserverId);
        $domain = $application->domain;

        $deployment->log?->write('Writing vhost to server...');

        $this->writeVhost($application, $webserverId, $domain, $vhost);

        $deployment->log?->write('Webserver restarted.');
    }

    private function generateVhost(Application $application, string $webserverId): string
    {
        if ($application->custom_template) {
            return $application->custom_template;
        }

        return $application->type()->vhost($webserverId);
    }

    private function writeVhost(Application $application, string $webserverId, string $domain, string $vhost): void
    {
        $basePath = match ($webserverId) {
            'nginx' => '/etc/nginx',
            'caddy' => '/etc/caddy',
            default => throw new RuntimeException("Unsupported webserver: {$webserverId}"),
        };

        $safeDomain = escapeshellarg($domain);

        $application->server->ssh()->write(
            "{$basePath}/sites-available/{$domain}",
            format_nginx_config($vhost),
            'root'
        );
        $application->server->ssh()->exec(
            "sudo ln -sf {$basePath}/sites-available/{$safeDomain} {$basePath}/sites-enabled/{$safeDomain}",
            'enable-vhost'
        );

        /** @var \App\Models\Service $webserverService */
        $webserverService = $application->server->webserver();
        $webserverService->restart();
    }
}
