<?php

namespace Tests\Unit\Actions\SSL;

use App\Actions\SSL\CertificateParser;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CertificateParserTest extends TestCase
{
    public function test_parses_certificate_with_san(): void
    {
        $cert = $this->generateCertWithSan(['DNS:example.com', 'DNS:www.example.com']);

        $result = CertificateParser::parse($cert);

        $this->assertArrayHasKey('expires_at', $result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertContains('example.com', $result['domains']);
        $this->assertContains('www.example.com', $result['domains']);
    }

    public function test_parses_certificate_with_wildcard_san(): void
    {
        $cert = $this->generateCertWithSan(['DNS:*.example.com', 'DNS:example.com']);

        $result = CertificateParser::parse($cert);

        $this->assertContains('*.example.com', $result['domains']);
        $this->assertContains('example.com', $result['domains']);
    }

    public function test_falls_back_to_cn_when_no_san(): void
    {
        $cert = $this->generateCertWithoutSan('fallback.example.com');

        $result = CertificateParser::parse($cert);

        $this->assertContains('fallback.example.com', $result['domains']);
    }

    public function test_extracts_expiry_date(): void
    {
        $cert = $this->generateCertWithSan(['DNS:example.com'], 365);

        $result = CertificateParser::parse($cert);

        $this->assertTrue($result['expires_at']->isFuture());
        $this->assertTrue($result['expires_at']->isAfter(now()->addDays(360)));
        $this->assertTrue($result['expires_at']->isBefore(now()->addDays(370)));
    }

    public function test_throws_on_invalid_certificate(): void
    {
        $this->expectException(ValidationException::class);

        CertificateParser::parse('not a valid certificate');
    }

    public function test_throws_on_empty_string(): void
    {
        $this->expectException(ValidationException::class);

        CertificateParser::parse('');
    }

    public function test_deduplicates_domains(): void
    {
        $cert = $this->generateCertWithSan(['DNS:example.com', 'DNS:example.com']);

        $result = CertificateParser::parse($cert);

        $this->assertCount(1, $result['domains']);
    }

    public function test_lowercases_domains(): void
    {
        $cert = $this->generateCertWithSan(['DNS:EXAMPLE.COM']);

        $result = CertificateParser::parse($cert);

        $this->assertContains('example.com', $result['domains']);
    }

    /**
     * @param  array<int, string>  $sans
     */
    private function generateCertWithSan(array $sans, int $days = 365): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);

        $config = tempnam(sys_get_temp_dir(), 'openssl');
        $sanString = implode(',', $sans);
        file_put_contents($config, "
[req]
distinguished_name = req_dn
req_extensions = v3_req
[req_dn]
CN = example.com
[v3_req]
subjectAltName = {$sanString}
");

        $csr = openssl_csr_new(
            ['commonName' => 'example.com'],
            $key,
            ['config' => $config, 'req_extensions' => 'v3_req']
        );

        $cert = openssl_csr_sign(
            $csr,
            null,
            $key,
            $days,
            ['config' => $config, 'x509_extensions' => 'v3_req']
        );

        openssl_x509_export($cert, $certPem);
        unlink($config);

        return $certPem;
    }

    private function generateCertWithoutSan(string $cn): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => $cn], $key);
        $cert = openssl_csr_sign($csr, null, $key, 365);

        openssl_x509_export($cert, $certPem);

        return $certPem;
    }
}
