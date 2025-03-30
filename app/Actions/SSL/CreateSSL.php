<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\Ssl;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSSL
{
    /**
     * @param  array<string, mixed>  $input
     *
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
            'is_active' => false,
            'domains' => $input['domains'],
        ]);
        $ssl->log_id = ServerLog::log($site->server, 'create-ssl', '', $site)->id;
        $ssl->save();

        dispatch(function () use ($site, $ssl): void {
            /** @var Service $service */
            $service = $site->server->webserver();
            /** @var Webserver $webserver */
            $webserver = $service->handler();
            $webserver->setupSSL($ssl);
            $ssl->status = SslStatus::CREATED;
            $ssl->save();
            $webserver->updateVHost($site);
        })->catch(function () use ($ssl): void {
            $ssl->status = SslStatus::FAILED;
            $ssl->save();
        })->onConnection('ssh');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(array $input): array
    {
        $rules = [
            'type' => [
                'required',
                Rule::in(config('core.ssl_types')),
            ],
            'domains' => [
                'required',
                'array',
                'min:1',
            ],
            'domains.*' => [
                'required',
                'string',
                'max:255',
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
