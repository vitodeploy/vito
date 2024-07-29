<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Models\Site;
use App\Models\Ssl;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSSL
{
    /**
     * @throws ValidationException
     */
    public function create(Site $site, array $input): void
    {
        $this->validate($input);

        $ssl = new Ssl([
            'site_id' => $site->id,
            'type' => $input['type'],
            'certificate' => $input['certificate'] ?? null,
            'pk' => $input['private'] ?? null,
            'expires_at' => $input['type'] === SslType::LETSENCRYPT ? now()->addMonths(3) : $input['expires_at'],
            'status' => SslStatus::CREATING,
        ]);
        $ssl->domains = [$site->domain];
        if (isset($input['aliases']) && $input['aliases']) {
            $ssl->domains = array_merge($ssl->domains, $site->aliases);
        }
        $ssl->save();

        dispatch(function () use ($site, $ssl) {
            /** @var Webserver $webserver */
            $webserver = $site->server->webserver()->handler();
            $webserver->setupSSL($ssl);
            $ssl->status = SslStatus::CREATED;
            $ssl->save();
            $site->type()->edit();
        })->catch(function () use ($ssl) {
            $ssl->status = SslStatus::FAILED;
            $ssl->save();
        })->onConnection('ssh');
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(config('core.ssl_types')),
            ],
        ];
        if (isset($input['type']) && $input['type'] == SslType::CUSTOM) {
            $rules['certificate'] = 'required';
            $rules['private'] = 'required';
            $rules['expires_at'] = 'required|date_format:Y-m-d|after_or_equal:'.now();
        }

        Validator::make($input, $rules)->validate();
    }
}
