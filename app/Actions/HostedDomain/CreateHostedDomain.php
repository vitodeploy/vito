<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
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
        $validated = $this->validate($site, $input);

        $hostedDomain = new HostedDomain;
        $hostedDomain->site_id = $site->id;
        $hostedDomain->domain = $validated['domain'];
        $hostedDomain->type = $validated['type'];
        $hostedDomain->status = HostedDomainStatus::CREATING;
        $hostedDomain->ssl_method = SslMethod::from($validated['ssl_method']);
        $hostedDomain->ssl_id = $validated['ssl_method'] === SslMethod::CUSTOM->value ? (int) $validated['ssl_id'] : null;

        $hostedDomain->save();

        dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');

        return $hostedDomain->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validate(Site $site, array $input): array
    {
        $rules = [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
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
            'ssl_method' => [
                'required',
                Rule::in(
                    $site->webserver()->allowedSslMethods()
                        ?? [SslMethod::NONE->value, SslMethod::LETSENCRYPT->value, SslMethod::CUSTOM->value]
                ),
            ],
            'ssl_id' => [
                Rule::requiredIf(($input['ssl_method'] ?? '') === SslMethod::CUSTOM->value),
                function (string $attribute, mixed $value, \Closure $fail) use ($site, $input): void {
                    if (($input['ssl_method'] ?? '') !== SslMethod::CUSTOM->value || empty($value)) {
                        return;
                    }

                    $ssl = Ssl::activeServerLevel($site->server_id)
                        ->where('id', $value)
                        ->first();

                    if (! $ssl) {
                        $fail('The selected SSL certificate is not valid.');
                    }
                },
            ],
        ];

        return Validator::make($input, $rules, [
            'ssl_id.required' => 'Please select an SSL certificate when using a custom certificate.',
        ])->validate();
    }
}
