<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\ActivateServerSslJob;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ActivateServerSsl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function activate(Server $server, Ssl $ssl, array $input): void
    {
        if ($ssl->type !== SslType::CSR->value || $ssl->status !== SslStatus::CREATED) {
            throw ValidationException::withMessages([
                'ssl' => 'SSL certificate cannot be activated.',
            ]);
        }

        $this->validate($input);

        $certData = CertificateParser::parse($input['certificate']);

        $ssl->certificate = $input['certificate'];
        $ssl->ca = $input['ca'] ?? null;
        $ssl->expires_at = $certData['expires_at'];
        $ssl->domains = $certData['domains'];
        $ssl->status = SslStatus::CREATING;
        $ssl->log_id = ServerLog::log($server, 'activate-server-ssl', '')->id;
        $ssl->save();

        dispatch(new ActivateServerSslJob($server, $ssl))->onQueue('ssh');
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'certificate' => [
                'required',
                'string',
            ],
            'ca' => [
                'nullable',
                'string',
            ],
        ])->validate();
    }
}
