<?php

namespace App\SiteTypes;

use App\Enums\LoadBalancerMethod;
use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class LoadBalancer extends AbstractSiteType
{
    public static function id(): string
    {
        return 'load-balancer';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function language(): string
    {
        return 'yaml';
    }

    public function createRules(array $input): array
    {
        return [
            'method' => [
                'required',
                Rule::in([
                    LoadBalancerMethod::IP_HASH,
                    LoadBalancerMethod::ROUND_ROBIN,
                    LoadBalancerMethod::LEAST_CONNECTIONS,
                ]),
            ],
        ];
    }

    public function data(array $input): array
    {
        return [
            'method' => $input['method'] ?? LoadBalancerMethod::ROUND_ROBIN,
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();

        $this->site->webserver()->createVHost($this->site);
    }

    public function vhost(string $webserver): string
    {
        if ($webserver === 'nginx') {
            return view('ssh.services.webserver.nginx.vhost', [
                'header' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.force-ssl', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.load-balancer-upstream', ['site' => $this->site]),
                ],
                'main' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.load-balancer', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.redirects', ['site' => $this->site]),
                ],
            ]);
        }

        if ($webserver === 'caddy') {
            return view('ssh.services.webserver.caddy.vhost', [
                'main' => implode("\n", [
                    view('ssh.services.webserver.caddy.vhost-blocks.force-ssl', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.load-balancer', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.redirects', ['site' => $this->site]),
                ]),
            ]);
        }

        return '';
    }
}
