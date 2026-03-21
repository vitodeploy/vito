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

    public function requiredServices(): array
    {
        return [
            'webserver',
        ];
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
                    LoadBalancerMethod::IP_HASH->value,
                    LoadBalancerMethod::ROUND_ROBIN->value,
                    LoadBalancerMethod::LEAST_CONNECTIONS->value,
                ]),
            ],
        ];
    }

    public function data(array $input): array
    {
        return [
            'method' => $input['method'] ?? LoadBalancerMethod::ROUND_ROBIN->value,
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

    public function vhostData(): array
    {
        return [
            'is_load_balancer' => true,
        ];
    }
}
