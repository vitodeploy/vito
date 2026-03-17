<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CreateJob;
use App\Models\Application;
use App\Models\ServerLog;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateSSL
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Site|Application $parent, array $input): Ssl
    {
        $this->validate($input);

        $parent->ssls()
            ->where('type', $input['type'])
            ->where('status', SslStatus::FAILED)
            ->delete();

        $ssl = new Ssl([
            'site_id' => $parent instanceof Site ? $parent->id : null,
            'application_id' => $parent instanceof Application ? $parent->id : null,
            'type' => $input['type'],
            'certificate' => $input['certificate'] ?? null,
            'pk' => $input['private'] ?? null,
            'expires_at' => $input['type'] === SslType::LETSENCRYPT->value ? now()->addMonths(3) : $input['expires_at'],
            'status' => SslStatus::CREATING,
            'email' => $input['email'] ?? null,
            'is_active' => ! $parent->activeSsl,
        ]);
        $ssl->domains = [$parent->domain];
        if (isset($input['aliases']) && $input['aliases']) {
            $ssl->domains = array_merge($ssl->domains, $parent->aliases ?? []);
        }
        $ssl->log_id = ServerLog::log($parent->server, 'create-ssl', '')->id;
        $ssl->save();

        dispatch(new CreateJob($parent, $ssl))->onQueue('ssh');

        return $ssl;
    }

    private function validate(array $input): void
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

        Validator::make($input, $rules)->validate();
    }
}
