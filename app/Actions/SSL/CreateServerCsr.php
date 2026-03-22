<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Jobs\SSL\CreateServerCsrJob;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateServerCsr
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Ssl
    {
        $this->validate($input);

        $csrData = [
            'common_name' => $input['common_name'],
            'organization' => $input['organization'],
            'organizational_unit' => $input['organizational_unit'] ?? null,
            'city' => $input['city'],
            'state' => $input['state'],
            'country' => $input['country'],
            'email' => $input['email'] ?? null,
            'key_size' => (int) ($input['key_size'] ?? 2048),
        ];

        $ssl = new Ssl([
            'server_id' => $server->id,
            'type' => $input['type'],
            'status' => SslStatus::CREATING,
            'has_csr' => true,
            'domains' => self::parseDomains($input['common_name']),
            'csr_data' => $csrData,
            'csr_passphrase' => $input['passphrase'] ?? null,
        ]);
        $ssl->log_id = ServerLog::log($server, 'create-server-csr', '')->id;
        $ssl->save();

        dispatch(new CreateServerCsrJob($server, $ssl))->onQueue('ssh');

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
            'common_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\.\-\*\,\s]+$/'],
            'organization' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\.\,\-]+$/'],
            'organizational_unit' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\.\,\-]+$/'],
            'city' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\.\,\-]+$/'],
            'state' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\.\,\-]+$/'],
            'country' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'key_size' => ['nullable', Rule::in([2048, 4096])],
            'passphrase' => ['nullable', 'string', 'max:255'],
        ])->validate();
    }

    /**
     * Parse a comma-separated domain string into an array of unique domains.
     *
     * @return array<int, string>
     */
    public static function parseDomains(string $input): array
    {
        $parts = array_map('trim', explode(',', $input));
        $parts = array_filter($parts, fn (string $part): bool => $part !== '');
        $parts = array_map('strtolower', $parts);

        return array_values(array_unique($parts));
    }
}
