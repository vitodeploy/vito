<?php

namespace App\DNSProviders;

use App\Models\DNSProvider as DNSProviderModel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class Cloudflare extends AbstractDNSProvider
{
    private const string API_BASE_URL = 'https://api.cloudflare.com/client/v4/';

    public function __construct(DNSProviderModel $dnsProvider)
    {
        parent::__construct($dnsProvider);
    }

    public static function id(): string
    {
        return 'cloudflare';
    }

    private function getClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->dnsProvider->credentials['token'],
            'Content-Type' => 'application/json',
        ])->baseUrl(self::API_BASE_URL);
    }

    public function validationRules(array $input): array
    {
        return [
            'token' => 'required|string',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'token' => $input['token'],
        ];
    }

    public function editValidationRules(array $input): array
    {
        return [
            'token' => 'nullable|string',
        ];
    }

    public function mergeEditData(array $input): array
    {
        $credentials = $this->dnsProvider->credentials;
        $needsReconnect = false;

        if (! empty($input['token'])) {
            $credentials['token'] = $input['token'];
            $needsReconnect = true;
        }

        return [$credentials, $needsReconnect];
    }

    public function connect(array $credentials): bool
    {
        try {
            // Use /zones endpoint to verify token works for both user-scoped and account-scoped tokens
            // This also verifies the token has Zone:Read permissions which we need
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$credentials['token'],
                'Content-Type' => 'application/json',
            ])
                ->baseUrl(self::API_BASE_URL)
                ->get('zones', ['per_page' => 1]);

            if ($response->successful() && $response->json('success') !== false) {
                return true;
            }

            Log::error('Cloudflare connection failed', ['response' => $response->json()]);

            return false;
        } catch (Throwable $e) {
            Log::error('Cloudflare connection exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getDomains(): array
    {
        try {
            $response = $this->getClient()->get('zones', [
                'per_page' => 100,
            ]);

            if (! $response->successful()) {
                Log::error('Failed to fetch Cloudflare domains', ['response' => $response->json()]);

                return [];
            }

            return collect($response->json('result'))->map(function (array $zone) {
                return [
                    'id' => $zone['id'],
                    'name' => $zone['name'],
                    'status' => $zone['status'],
                    'created_on' => $zone['created_on'],
                    'modified_on' => $zone['modified_on'],
                ];
            })->toArray();
        } catch (Throwable $e) {
            Log::error('Cloudflare getDomains exception', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function getDomain(string $domainId): array
    {
        try {
            $response = $this->getClient()->get("zones/{$domainId}");

            if (! $response->successful()) {
                Log::error('Failed to fetch Cloudflare domain', ['domainId' => $domainId, 'response' => $response->json()]);

                return [];
            }

            $zone = $response->json('result');

            return [
                'id' => $zone['id'],
                'name' => $zone['name'],
                'status' => $zone['status'],
                'created_on' => $zone['created_on'],
                'modified_on' => $zone['modified_on'],
            ];
        } catch (Throwable $e) {
            Log::error('Cloudflare getDomain exception', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function getRecords(string $domainId): array
    {
        $response = $this->getClient()->get("zones/{$domainId}/dns_records", [
            'per_page' => 100,
        ]);

        if (! $response->successful()) {
            Log::error('Failed to fetch Cloudflare DNS records', ['domainId' => $domainId, 'response' => $response->json()]);
            throw new \RuntimeException('Failed to fetch DNS records: '.($response->json('errors')[0]['message'] ?? 'Unknown error'));
        }

        return collect($response->json('result'))->map(function (array $record) {
            return [
                'id' => $record['id'],
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'],
                'priority' => $record['type'] === 'MX' && isset($record['priority']) ? $record['priority'] : null,
                'created_on' => $record['created_on'],
                'modified_on' => $record['modified_on'],
            ];
        })->toArray();
    }

    public function createRecord(string $domainId, array $recordData): array
    {
        try {
            $response = $this->getClient()->post("zones/{$domainId}/dns_records", $this->buildPayload($recordData));

            if (! $response->successful()) {
                Log::error('Failed to create Cloudflare DNS record', ['domainId' => $domainId, 'input' => $recordData, 'response' => $response->json()]);
                throw ValidationException::withMessages(['record' => 'Failed to create DNS record: '.($response->json('errors')[0]['message'] ?? 'Unknown error')]);
            }

            return $response->json('result');
        } catch (Throwable $e) {
            Log::error('Cloudflare createRecord exception', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages(['record' => 'Failed to create DNS record: '.$e->getMessage()]);
        }
    }

    public function updateRecord(string $domainId, string $recordId, array $recordData): array
    {
        try {
            $response = $this->getClient()->put("zones/{$domainId}/dns_records/{$recordId}", $this->buildPayload($recordData));

            if (! $response->successful()) {
                Log::error('Failed to update Cloudflare DNS record', ['domainId' => $domainId, 'recordId' => $recordId, 'input' => $recordData, 'response' => $response->json()]);
                throw ValidationException::withMessages(['record' => 'Failed to update DNS record: '.($response->json('errors')[0]['message'] ?? 'Unknown error')]);
            }

            return $response->json('result');
        } catch (Throwable $e) {
            Log::error('Cloudflare updateRecord exception', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages(['record' => 'Failed to update DNS record: '.$e->getMessage()]);
        }
    }

    private function buildPayload(array $input): array
    {
        $payload = [
            'type' => $input['type'],
            'name' => $input['name'],
            'content' => $input['content'],
            'ttl' => $input['ttl'] ?? 1,
            'proxied' => $input['proxied'] ?? false,
        ];

        if (isset($input['priority'])) {
            $payload['priority'] = $input['priority'];
        }

        return $payload;
    }

    public function deleteRecord(string $domainId, string $recordId): bool
    {
        try {
            $response = $this->getClient()->delete("zones/{$domainId}/dns_records/{$recordId}");

            if (! $response->successful()) {
                Log::error('Failed to delete Cloudflare DNS record', ['domainId' => $domainId, 'recordId' => $recordId, 'response' => $response->json()]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Cloudflare deleteRecord exception', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
