<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Jobs\SSL\InstallCustomServerSslJob;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InstallCustomServerSsl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function install(Server $server, array $input): Ssl
    {
        $this->validate($input);

        $certData = CertificateParser::parse($input['certificate']);

        $server->ssls()
            ->whereNull('site_id')
            ->where('type', 'custom')
            ->where('status', SslStatus::FAILED)
            ->delete();

        $ssl = new Ssl([
            'server_id' => $server->id,
            'type' => 'custom',
            'status' => SslStatus::CREATING,
            'certificate' => $input['certificate'],
            'pk' => $input['private_key'],
            'ca' => $input['ca'] ?? null,
            'expires_at' => $certData['expires_at'],
            'domains' => $certData['domains'],
        ]);
        $ssl->log_id = ServerLog::log($server, 'install-custom-server-ssl', '')->id;
        $ssl->save();

        dispatch(new InstallCustomServerSslJob($server, $ssl))->onQueue('ssh');

        return $ssl;
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
            'private_key' => [
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
