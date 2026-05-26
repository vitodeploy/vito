<?php

namespace App\Actions\Domain;

use App\Models\DNSProvider;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddDomain
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function add(User $user, Project $project, array $input): Domain
    {
        $this->validate($input);

        $dnsProvider = DNSProvider::findOrFail($input['dns_provider_id']);

        $this->authorize($user, $dnsProvider);

        $provider = $dnsProvider->provider();
        $domainData = $provider->getDomain($input['provider_domain_id']);

        if (! $domainData) {
            throw ValidationException::withMessages([
                'domain' => ['Domain not found in DNS provider.'],
            ]);
        }

        try {
            return DB::transaction(function () use ($user, $project, $dnsProvider, $domainData) {
                $domain = new Domain;
                $domain->dns_provider_id = $dnsProvider->id;
                $domain->user_id = $user->id;
                $domain->project_id = $project->id;
                $domain->domain = $domainData['name'];
                $domain->provider_domain_id = $domainData['id'];
                $domain->metadata = $domainData;
                $domain->save();

                $domain->syncDnsRecords();

                return $domain;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'domain' => [$e->getMessage()],
            ]);
        }
    }

    private function validate(array $input): void
    {
        $rules = [
            'dns_provider_id' => [
                'required',
                'exists:dns_providers,id',
            ],
            'provider_domain_id' => [
                'required',
                'string',
                Rule::unique('domains', 'provider_domain_id')->where(function ($query) use ($input) {
                    return $query->where('dns_provider_id', $input['dns_provider_id'] ?? null);
                }),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }

    private function authorize(User $user, DNSProvider $dnsProvider): void
    {
        if ($user->cannot('view', $dnsProvider)) {
            abort(403, 'Unauthorized access to DNS provider.');
        }
    }
}
