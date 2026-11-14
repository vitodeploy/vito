<?php

namespace App\Actions\SSL;

use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CreateLetsEncryptWildcardSslJob;
use App\Models\Domain;
use App\Models\EnvironmentDomain;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateLetsEncryptWildcardSsl
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Server $server, array $input, ?EnvironmentDomain $environmentDomain = null): Ssl
    {
        $this->validate($server, $input);

        if ($environmentDomain && ($environmentDomain->component->server_id !== $server->id
            || $environmentDomain->environment->application->project_id !== $server->project_id)) {
            throw ValidationException::withMessages(['domain_id' => 'The certificate target must belong to this server and project.']);
        }

        return DB::transaction(function () use ($server, $input, $environmentDomain): Ssl {
            /** @var Domain $domain */
            $domain = Domain::query()->findOrFail($input['domain_id']);

            $ssl = new Ssl([
                'server_id' => $server->id,
                'domain_id' => $domain->id,
                'type' => SslType::LETSENCRYPT->value,
                'status' => SslStatus::CREATING,
                'is_wildcard' => true,
                'domains' => [$domain->domain, '*.'.$domain->domain],
                'email' => $input['email'],
            ]);
            $ssl->environment_id = $environmentDomain?->environment_id;
            $ssl->log_id = ServerLog::log($server, 'create-wildcard-ssl', '')->id;
            $ssl->save();

            if ($environmentDomain) {
                $environmentDomain->ssl_id = $ssl->id;
                $environmentDomain->status = HostedDomainStatus::UPDATING;
                $environmentDomain->save();
            }
            dispatch(new CreateLetsEncryptWildcardSslJob($server, $ssl))->onQueue('ssh-certbot')->afterCommit();

            return $ssl;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(Server $server, array $input): void
    {
        $rules = [
            'domain_id' => [
                'required',
                Rule::exists('domains', 'id')->where('project_id', $server->project_id),
            ],
            'email' => ['required', 'email', 'max:255'],
        ];

        Validator::make($input, $rules)->validate();

        $domain = Domain::query()->find($input['domain_id']);
        if ($domain && ! $domain->dns_provider_id) {
            throw ValidationException::withMessages([
                'domain_id' => 'The selected domain does not have a DNS provider configured.',
            ]);
        }
    }
}
