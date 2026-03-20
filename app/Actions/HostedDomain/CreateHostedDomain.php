<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateHostedDomain
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Site $site, array $input): HostedDomain
    {
        $this->validate($site, $input);

        $hostedDomain = new HostedDomain;
        $hostedDomain->site_id = $site->id;
        $hostedDomain->domain = $input['domain'];
        $hostedDomain->type = $input['type'];
        $hostedDomain->status = HostedDomainStatus::CREATING;
        $hostedDomain->ssl_method = SslMethod::from($input['ssl_mode']);
        $hostedDomain->ssl_id = $input['ssl_mode'] === 'custom' ? (int) $input['ssl_id'] : null;

        $hostedDomain->save();

        dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');

        return $hostedDomain->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Site $site, array $input): void
    {
        $rules = [
            'domain' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($site): void {
                    $exists = HostedDomain::query()
                        ->where('domain', $value)
                        ->whereHas('site', fn ($q) => $q->where('server_id', $site->server_id))
                        ->exists();

                    if ($exists) {
                        $fail('This domain is already in use on this server.');
                    }
                },
            ],
            'type' => [
                'required',
                Rule::in([HostedDomainType::ALIAS->value, HostedDomainType::REDIRECT->value]),
            ],
            'ssl_mode' => [
                'required',
                Rule::in([SslMethod::NONE->value, SslMethod::LETSENCRYPT->value, SslMethod::CUSTOM->value]),
            ],
            'ssl_id' => [
                Rule::requiredIf(($input['ssl_mode'] ?? '') === 'custom'),
                function (string $attribute, mixed $value, \Closure $fail) use ($site, $input): void {
                    if (($input['ssl_mode'] ?? '') !== 'custom' || empty($value)) {
                        return;
                    }

                    $ssl = Ssl::query()
                        ->whereNull('site_id')
                        ->where('server_id', $site->server_id)
                        ->where('status', SslStatus::CREATED)
                        ->where('is_active', true)
                        ->where('id', $value)
                        ->first();

                    if (! $ssl) {
                        $fail('The selected SSL certificate is not valid.');
                    }
                },
            ],
        ];

        Validator::make($input, $rules, [
            'ssl_id.required' => 'Please select an SSL certificate when using a custom certificate.',
        ])->validate();
    }
}
