<?php

namespace Tests\Unit\DNSProviders;

use App\DNSProviders\Cloudflare;
use App\Models\DNSProvider as DNSProviderModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CloudflareTest extends TestCase
{
    private DNSProviderModel $dnsProvider;

    private Cloudflare $cloudflare;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dnsProvider = DNSProviderModel::factory()->create([
            'provider' => 'cloudflare',
            'credentials' => [
                'token' => 'test-token-123',
            ],
        ]);

        $this->cloudflare = new Cloudflare($this->dnsProvider);
    }

    public function test_id_returns_cloudflare(): void
    {
        $this->assertSame('cloudflare', Cloudflare::id());
    }

    public function test_validation_rules(): void
    {
        $rules = $this->cloudflare->validationRules([]);

        $this->assertSame([
            'token' => 'required|string',
        ], $rules);
    }

    public function test_credential_data(): void
    {
        $input = [
            'token' => 'test-token-456',
        ];

        $credentialData = $this->cloudflare->credentialData($input);

        $this->assertSame([
            'token' => 'test-token-456',
        ], $credentialData);
    }

    public function test_connect_success(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $credentials = ['token' => 'test-token-123'];

        $result = $this->cloudflare->connect($credentials);

        $this->assertTrue($result);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'api.cloudflare.com/client/v4/zones')
                && str_contains($request->url(), 'per_page=1')
                && $request->header('Authorization')[0] === 'Bearer test-token-123'
                && $request->header('Content-Type')[0] === 'application/json';
        });
    }

    public function test_connect_failure_invalid_response(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => false,
                'errors' => [
                    ['message' => 'Invalid token'],
                ],
            ], 200),
        ]);

        $credentials = ['token' => 'invalid-token'];

        $result = $this->cloudflare->connect($credentials);

        $this->assertFalse($result);
    }

    public function test_connect_failure_http_error(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([], 401),
        ]);

        $credentials = ['token' => 'invalid-token'];

        $result = $this->cloudflare->connect($credentials);

        $this->assertFalse($result);
    }

    public function test_connect_exception(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $credentials = ['token' => 'test-token'];

        $result = $this->cloudflare->connect($credentials);

        $this->assertFalse($result);
    }

    public function test_get_domains_success(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'zone-1',
                        'name' => 'example.com',
                        'status' => 'active',
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-02T00:00:00Z',
                    ],
                    [
                        'id' => 'zone-2',
                        'name' => 'test.com',
                        'status' => 'pending',
                        'created_on' => '2023-01-03T00:00:00Z',
                        'modified_on' => '2023-01-04T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $domains = $this->cloudflare->getDomains();

        $expected = [
            [
                'id' => 'zone-1',
                'name' => 'example.com',
                'status' => 'active',
                'created_on' => '2023-01-01T00:00:00Z',
                'modified_on' => '2023-01-02T00:00:00Z',
            ],
            [
                'id' => 'zone-2',
                'name' => 'test.com',
                'status' => 'pending',
                'created_on' => '2023-01-03T00:00:00Z',
                'modified_on' => '2023-01-04T00:00:00Z',
            ],
        ];

        $this->assertSame($expected, $domains);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'api.cloudflare.com/client/v4/zones')
                && $request->data()['per_page'] === 100;
        });
    }

    public function test_get_domains_failure(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([], 401),
        ]);

        $domains = $this->cloudflare->getDomains();

        $this->assertSame([], $domains);
    }

    public function test_get_domains_exception(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $domains = $this->cloudflare->getDomains();

        $this->assertSame([], $domains);
    }

    public function test_get_domain_success(): void
    {
        $domainId = 'zone-123';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}" => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'zone-123',
                    'name' => 'example.com',
                    'status' => 'active',
                    'created_on' => '2023-01-01T00:00:00Z',
                    'modified_on' => '2023-01-02T00:00:00Z',
                ],
            ], 200),
        ]);

        $domain = $this->cloudflare->getDomain($domainId);

        $expected = [
            'id' => 'zone-123',
            'name' => 'example.com',
            'status' => 'active',
            'created_on' => '2023-01-01T00:00:00Z',
            'modified_on' => '2023-01-02T00:00:00Z',
        ];

        $this->assertSame($expected, $domain);
    }

    public function test_get_domain_failure(): void
    {
        $domainId = 'invalid-zone';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}" => Http::response([], 404),
        ]);

        $domain = $this->cloudflare->getDomain($domainId);

        $this->assertSame([], $domain);
    }

    public function test_get_domain_exception(): void
    {
        $domainId = 'zone-123';

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $domain = $this->cloudflare->getDomain($domainId);

        $this->assertSame([], $domain);
    }

    public function test_get_records_success(): void
    {
        $domainId = 'zone-123';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records*" => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'record-1',
                        'type' => 'A',
                        'name' => 'example.com',
                        'content' => '192.168.1.1',
                        'ttl' => 300,
                        'proxied' => false,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-02T00:00:00Z',
                    ],
                    [
                        'id' => 'record-2',
                        'type' => 'CNAME',
                        'name' => 'www.example.com',
                        'content' => 'example.com',
                        'ttl' => 1,
                        'proxied' => true,
                        'created_on' => '2023-01-03T00:00:00Z',
                        'modified_on' => '2023-01-04T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $records = $this->cloudflare->getRecords($domainId);

        $expected = [
            [
                'id' => 'record-1',
                'type' => 'A',
                'name' => 'example.com',
                'content' => '192.168.1.1',
                'ttl' => 300,
                'proxied' => false,
                'priority' => null,
                'created_on' => '2023-01-01T00:00:00Z',
                'modified_on' => '2023-01-02T00:00:00Z',
            ],
            [
                'id' => 'record-2',
                'type' => 'CNAME',
                'name' => 'www.example.com',
                'content' => 'example.com',
                'ttl' => 1,
                'proxied' => true,
                'priority' => null,
                'created_on' => '2023-01-03T00:00:00Z',
                'modified_on' => '2023-01-04T00:00:00Z',
            ],
        ];

        $this->assertSame($expected, $records);
    }

    public function test_get_records_mx_priority_is_parsed(): void
    {
        $domainId = 'zone-123';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records*" => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'record-mx',
                        'type' => 'MX',
                        'name' => 'example.com',
                        'content' => 'mail.example.com',
                        'ttl' => 3600,
                        'proxied' => false,
                        'priority' => 10,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-02T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $records = $this->cloudflare->getRecords($domainId);

        $this->assertCount(1, $records);
        $this->assertSame('MX', $records[0]['type']);
        $this->assertSame(10, $records[0]['priority']);
    }

    public function test_get_records_mx_priority_zero_is_preserved(): void
    {
        $domainId = 'zone-123';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records*" => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'record-mx',
                        'type' => 'MX',
                        'name' => 'example.com',
                        'content' => 'mail.example.com',
                        'ttl' => 3600,
                        'proxied' => false,
                        'priority' => 0,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-02T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $records = $this->cloudflare->getRecords($domainId);

        $this->assertCount(1, $records);
        $this->assertSame('MX', $records[0]['type']);
        $this->assertSame(0, $records[0]['priority']);
    }

    public function test_get_records_non_mx_priority_is_always_null(): void
    {
        $domainId = 'zone-123';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records*" => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'record-a',
                        'type' => 'A',
                        'name' => 'example.com',
                        'content' => '192.168.1.1',
                        'ttl' => 300,
                        'proxied' => false,
                        'priority' => 5,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-02T00:00:00Z',
                    ],
                    [
                        'id' => 'record-cname',
                        'type' => 'CNAME',
                        'name' => 'www.example.com',
                        'content' => 'example.com',
                        'ttl' => 1,
                        'proxied' => true,
                        'priority' => 10,
                        'created_on' => '2023-01-03T00:00:00Z',
                        'modified_on' => '2023-01-04T00:00:00Z',
                    ],
                    [
                        'id' => 'record-txt',
                        'type' => 'TXT',
                        'name' => 'example.com',
                        'content' => 'v=spf1 include:example.com ~all',
                        'ttl' => 600,
                        'proxied' => false,
                        'priority' => 0,
                        'created_on' => '2023-01-05T00:00:00Z',
                        'modified_on' => '2023-01-06T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $records = $this->cloudflare->getRecords($domainId);

        $this->assertCount(3, $records);

        foreach ($records as $record) {
            $this->assertNull($record['priority'], "Expected null priority for {$record['type']} record, got {$record['priority']}");
        }
    }

    public function test_get_records_mx_without_priority_returns_null(): void
    {
        $domainId = 'zone-123';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records*" => Http::response([
                'success' => true,
                'result' => [
                    [
                        'id' => 'record-mx',
                        'type' => 'MX',
                        'name' => 'example.com',
                        'content' => 'mail.example.com',
                        'ttl' => 3600,
                        'proxied' => false,
                        'created_on' => '2023-01-01T00:00:00Z',
                        'modified_on' => '2023-01-02T00:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $records = $this->cloudflare->getRecords($domainId);

        $this->assertCount(1, $records);
        $this->assertSame('MX', $records[0]['type']);
        $this->assertNull($records[0]['priority']);
    }

    public function test_get_records_failure(): void
    {
        $domainId = 'invalid-zone';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records*" => Http::response([
                'success' => false,
                'errors' => [
                    ['message' => 'Zone not found'],
                ],
            ], 404),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch DNS records: Zone not found');

        $this->cloudflare->getRecords($domainId);
    }

    public function test_get_records_exception(): void
    {
        $domainId = 'zone-123';

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network error');

        $this->cloudflare->getRecords($domainId);
    }

    public function test_create_record_success(): void
    {
        $domainId = 'zone-123';
        $input = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.100',
            'ttl' => 300,
            'proxied' => false,
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records" => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'new-record-123',
                    'type' => 'A',
                    'name' => 'test.example.com',
                    'content' => '192.168.1.100',
                    'ttl' => 300,
                    'proxied' => false,
                ],
            ], 200),
        ]);

        $result = $this->cloudflare->createRecord($domainId, $input);

        $expected = [
            'id' => 'new-record-123',
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.100',
            'ttl' => 300,
            'proxied' => false,
        ];

        $this->assertSame($expected, $result);
    }

    public function test_create_record_with_defaults(): void
    {
        $domainId = 'zone-123';
        $input = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.100',
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records" => Http::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $this->cloudflare->createRecord($domainId, $input);

        Http::assertSent(function (Request $request) use ($domainId) {
            return $request->url() === "https://api.cloudflare.com/client/v4/zones/{$domainId}/dns_records"
                && $request->data()['ttl'] === 1
                && $request->data()['proxied'] === false;
        });
    }

    public function test_create_record_failure(): void
    {
        $domainId = 'zone-123';
        $input = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.100',
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records" => Http::response([
                'success' => false,
                'errors' => [
                    ['message' => 'Invalid record data'],
                ],
            ], 400),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to create DNS record: Invalid record data');

        $this->cloudflare->createRecord($domainId, $input);
    }

    public function test_create_record_failure_unknown_error(): void
    {
        $domainId = 'zone-123';
        $input = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.100',
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records" => Http::response([
                'success' => false,
                'errors' => [],
            ], 400),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to create DNS record: Unknown error');

        $this->cloudflare->createRecord($domainId, $input);
    }

    public function test_create_record_exception(): void
    {
        $domainId = 'zone-123';
        $input = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '192.168.1.100',
        ];

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to create DNS record: Network error');

        $this->cloudflare->createRecord($domainId, $input);
    }

    public function test_update_record_success(): void
    {
        $domainId = 'zone-123';
        $recordId = 'record-456';
        $input = [
            'type' => 'A',
            'name' => 'updated.example.com',
            'content' => '192.168.1.200',
            'ttl' => 600,
            'proxied' => true,
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records/{$recordId}" => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'record-456',
                    'type' => 'A',
                    'name' => 'updated.example.com',
                    'content' => '192.168.1.200',
                    'ttl' => 600,
                    'proxied' => true,
                ],
            ], 200),
        ]);

        $result = $this->cloudflare->updateRecord($domainId, $recordId, $input);

        $expected = [
            'id' => 'record-456',
            'type' => 'A',
            'name' => 'updated.example.com',
            'content' => '192.168.1.200',
            'ttl' => 600,
            'proxied' => true,
        ];

        $this->assertSame($expected, $result);
    }

    public function test_update_record_with_defaults(): void
    {
        $domainId = 'zone-123';
        $recordId = 'record-456';
        $input = [
            'type' => 'A',
            'name' => 'updated.example.com',
            'content' => '192.168.1.200',
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records/{$recordId}" => Http::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $this->cloudflare->updateRecord($domainId, $recordId, $input);

        Http::assertSent(function (Request $request) use ($domainId, $recordId) {
            return $request->url() === "https://api.cloudflare.com/client/v4/zones/{$domainId}/dns_records/{$recordId}"
                && $request->data()['ttl'] === 1
                && $request->data()['proxied'] === false;
        });
    }

    public function test_update_record_failure(): void
    {
        $domainId = 'zone-123';
        $recordId = 'record-456';
        $input = [
            'type' => 'A',
            'name' => 'updated.example.com',
            'content' => '192.168.1.200',
        ];

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records/{$recordId}" => Http::response([
                'success' => false,
                'errors' => [
                    ['message' => 'Record not found'],
                ],
            ], 404),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to update DNS record: Record not found');

        $this->cloudflare->updateRecord($domainId, $recordId, $input);
    }

    public function test_update_record_exception(): void
    {
        $domainId = 'zone-123';
        $recordId = 'record-456';
        $input = [
            'type' => 'A',
            'name' => 'updated.example.com',
            'content' => '192.168.1.200',
        ];

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to update DNS record: Network error');

        $this->cloudflare->updateRecord($domainId, $recordId, $input);
    }

    public function test_delete_record_success(): void
    {
        $domainId = 'zone-123';
        $recordId = 'record-456';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records/{$recordId}" => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'record-456',
                ],
            ], 200),
        ]);

        $result = $this->cloudflare->deleteRecord($domainId, $recordId);

        $this->assertTrue($result);
    }

    public function test_delete_record_failure(): void
    {
        $domainId = 'zone-123';
        $recordId = 'invalid-record';

        Http::fake([
            "api.cloudflare.com/client/v4/zones/{$domainId}/dns_records/{$recordId}" => Http::response([], 404),
        ]);

        $result = $this->cloudflare->deleteRecord($domainId, $recordId);

        $this->assertFalse($result);
    }

    public function test_delete_record_exception(): void
    {
        $domainId = 'zone-123';
        $recordId = 'record-456';

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $result = $this->cloudflare->deleteRecord($domainId, $recordId);

        $this->assertFalse($result);
    }
}
