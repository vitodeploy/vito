<?php

namespace App\Actions\SSL;

use App\Enums\SslType;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateServerSsl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Ssl
    {
        Validator::make($input, [
            'type' => [
                'required',
                Rule::in(config('core.ssl_types')),
            ],
        ])->validate();

        return match ($input['type']) {
            SslType::CUSTOM->value => app(InstallCustomServerSsl::class)->install($server, $input),
            SslType::LETSENCRYPT->value => app(CreateLetsEncryptWildcardSsl::class)->create($server, $input),
            default => app(CreateServerCsr::class)->create($server, $input),
        };
    }
}
