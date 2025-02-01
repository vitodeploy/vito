<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Models\ServerLog;
use App\Models\Site;
use App\Models\Ssl;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSSL
{
    /**
     * @throws ValidationException
     */
    public function create(Site $site, array $input): void
    {
        $site->ssls()
            ->where('type', $input['type'])
            ->where('status', SslStatus::FAILED)
            ->delete();

        $ssl = new Ssl([
            'site_id' => $site->id,
            'type' => $input['type'],
            'certificate' => $input['certificate'] ?? null,
            'pk' => $input['private'] ?? null,
            'expires_at' => $input['type'] === SslType::LETSENCRYPT ? now()->addMonths(3) : $input['expires_at'],
            'status' => SslStatus::CREATING,
            'email' => $input['email'] ?? null,
            'is_active' => ! $site->activeSsl,
        ]);
        $ssl->domains = [$site->domain];
        if (isset($input['aliases']) && $input['aliases']) {
            $ssl->domains = array_merge($ssl->domains, $site->aliases);
        }
        $ssl->log_id = ServerLog::log($site->server, 'create-ssl', '', $site)->id;
        $ssl->save();

        dispatch(function () use ($site, $ssl) {
            /** @var Webserver $webserver */
            $webserver = $site->server->webserver()->handler();
            $webserver->setupSSL($ssl);
            $ssl->status = SslStatus::CREATED;
            $ssl->save();
            $webserver->updateVHost($site);
        })->catch(function () use ($ssl) {
            $ssl->status = SslStatus::FAILED;
            $ssl->save();
        })->onConnection('ssh');
    }

    public static function rules(array $input): array
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
            $rules['expires_at'] = [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.now(),
            ];
        }
        if (isset($input['type']) && $input['type'] == SslType::LETSENCRYPT) {
            $rules['email'] = [
                'required',
                'email',
            ];
        }

        return $rules;
    }
}
