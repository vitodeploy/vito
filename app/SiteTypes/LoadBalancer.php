<?php

namespace App\SiteTypes;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\LoadBalancerMethod;
use App\Enums\Webserver;
use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class LoadBalancer extends AbstractSiteType
{
    public static function make(): self
    {
        return new self(new Site(['type' => \App\Enums\SiteType::LOAD_BALANCER]));
    }

    public function language(): string
    {
        return 'yaml';
    }

    public function fields(): DynamicForm
    {
        return new DynamicForm([
            DynamicField::make('method')
                ->select()
                ->label('Load Balancing Method')
                ->options([
                    LoadBalancerMethod::IP_HASH,
                    LoadBalancerMethod::ROUND_ROBIN,
                    LoadBalancerMethod::LEAST_CONNECTIONS,
                ]),
        ]);
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
        if ($webserver === Webserver::NGINX) {
            return view('ssh.services.webserver.nginx.vhost', [
                'topBlocks' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.force-ssl', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.load-balancer-upstream', ['site' => $this->site]),
                ],
                'blocks' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.load-balancer', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.redirects', ['site' => $this->site]),
                ],
            ]);
        }

        return '';
    }
}
