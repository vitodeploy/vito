<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateHostedDomain
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(HostedDomain $hostedDomain, Site $site, array $input): HostedDomain
    {
        $this->validate($hostedDomain, $site, $input);

        $isPrimary = $hostedDomain->type === HostedDomainType::PRIMARY;

        if (! $isPrimary) {
            $hostedDomain->domain = $input['domain'];
            $hostedDomain->type = $input['type'];
        }

        $hostedDomain->ssl_method = SslMethod::from($input['ssl_mode']);
        $hostedDomain->ssl_id = $input['ssl_mode'] === SslMethod::CUSTOM->value ? (int) $input['ssl_id'] : null;

        $hostedDomain->save();

        return $hostedDomain->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(HostedDomain $hostedDomain, Site $site, array $input): void
    {
        $isPrimary = $hostedDomain->type === HostedDomainType::PRIMARY;

        $rules = [];

        if (! $isPrimary) {
            $rules['domain'] = [
                'required',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($site, $hostedDomain): void {
                    $exists = HostedDomain::query()
                        ->where('domain', $value)
                        ->where('id', '!=', $hostedDomain->id)
                        ->whereHas('site', fn ($q) => $q->where('server_id', $site->server_id))
                        ->exists();

                    if ($exists) {
                        $fail('This domain is already in use on this server.');
                    }
                },
            ];
            $rules['type'] = [
                'required',
                Rule::in([HostedDomainType::ALIAS->value, HostedDomainType::REDIRECT->value]),
            ];
        }

        $rules['ssl_mode'] = [
            'required',
            Rule::in([SslMethod::NONE->value, SslMethod::LETSENCRYPT->value, SslMethod::CUSTOM->value]),
        ];
        $rules['ssl_id'] = [
            Rule::requiredIf(($input['ssl_mode'] ?? '') === SslMethod::CUSTOM->value),
            function (string $attribute, mixed $value, \Closure $fail) use ($site, $input): void {
                if (($input['ssl_mode'] ?? '') !== SslMethod::CUSTOM->value || empty($value)) {
                    return;
                }

                $ssl = Ssl::query()
                    ->activeServerLevel($site->server_id)
                    ->where('id', $value)
                    ->first();

                if (! $ssl) {
                    $fail('The selected SSL certificate is not valid.');
                }
            },
        ];

        Validator::make($input, $rules, [
            'ssl_id.required' => 'Please select an SSL certificate when using a custom certificate.',
        ])->validate();
    }
}
