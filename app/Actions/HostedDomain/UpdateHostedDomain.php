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
use Illuminate\Validation\ValidationException;

class UpdateHostedDomain
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(HostedDomain $hostedDomain, Site $site, array $input): HostedDomain
    {
        if ($hostedDomain->status->isProcessing()) {
            throw ValidationException::withMessages([
                'domain' => ['Cannot update a domain while it is '.$hostedDomain->status->value.'.'],
            ]);
        }

        $validated = $this->validate($hostedDomain, $site, $input);

        $isPrimary = $hostedDomain->type === HostedDomainType::PRIMARY;
        $domainChanged = ! $isPrimary && $hostedDomain->domain !== $validated['domain'];
        $sslMethodChangedToLE = SslMethod::from($validated['ssl_method']) === SslMethod::LETSENCRYPT
            && $hostedDomain->ssl_method !== SslMethod::LETSENCRYPT;

        if (! $isPrimary) {
            $hostedDomain->domain = $validated['domain'];
            $hostedDomain->type = $validated['type'];
        }

        $hostedDomain->ssl_method = SslMethod::from($validated['ssl_method']);
        $hostedDomain->ssl_id = $validated['ssl_method'] === SslMethod::CUSTOM->value ? (int) $validated['ssl_id'] : null;
        $hostedDomain->error = null;

        $needsRecheck = ($domainChanged || $sslMethodChangedToLE)
            && in_array($hostedDomain->status, [HostedDomainStatus::ACTIVE, HostedDomainStatus::PENDING]);

        if ($needsRecheck) {
            $hostedDomain->status = HostedDomainStatus::UPDATING;
        }

        $hostedDomain->save();

        if ($needsRecheck) {
            dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');
        } else {
            $hostedDomain->site->webserver()->updateVHost($hostedDomain->site);
        }

        return $hostedDomain->refresh();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validate(HostedDomain $hostedDomain, Site $site, array $input): array
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

        $rules['ssl_method'] = [
            'required',
            Rule::in([SslMethod::NONE->value, SslMethod::LETSENCRYPT->value, SslMethod::CUSTOM->value]),
        ];
        $rules['ssl_id'] = [
            Rule::requiredIf(($input['ssl_method'] ?? '') === SslMethod::CUSTOM->value),
            function (string $attribute, mixed $value, \Closure $fail) use ($site, $input): void {
                if (($input['ssl_method'] ?? '') !== SslMethod::CUSTOM->value || empty($value)) {
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

        return Validator::make($input, $rules, [
            'ssl_id.required' => 'Please select an SSL certificate when using a custom certificate.',
        ])->validate();
    }
}
