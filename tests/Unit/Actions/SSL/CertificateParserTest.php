<?php

use App\Actions\SSL\CertificateParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('parses certificate with san', function () {
    $cert = vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithSan(['DNS:example.com', 'DNS:www.example.com']);

    $result = CertificateParser::parse($cert);

    expect($result)->toHaveKey('expires_at');
    expect($result)->toHaveKey('domains');
    expect($result['domains'])->toContain('example.com');
    expect($result['domains'])->toContain('www.example.com');
});

test('parses certificate with wildcard san', function () {
    $cert = vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithSan(['DNS:*.example.com', 'DNS:example.com']);

    $result = CertificateParser::parse($cert);

    expect($result['domains'])->toContain('*.example.com');
    expect($result['domains'])->toContain('example.com');
});

test('falls back to cn when no san', function () {
    $cert = vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithoutSan('fallback.example.com');

    $result = CertificateParser::parse($cert);

    expect($result['domains'])->toContain('fallback.example.com');
});

test('extracts expiry date', function () {
    $cert = vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithSan(['DNS:example.com'], 365);

    $result = CertificateParser::parse($cert);

    expect($result['expires_at']->isFuture())->toBeTrue();
    expect($result['expires_at']->isAfter(now()->addDays(360)))->toBeTrue();
    expect($result['expires_at']->isBefore(now()->addDays(370)))->toBeTrue();
});

test('throws on invalid certificate', function () {
    $this->expectException(ValidationException::class);

    CertificateParser::parse('not a valid certificate');
});

test('throws on empty string', function () {
    $this->expectException(ValidationException::class);

    CertificateParser::parse('');
});

test('deduplicates domains', function () {
    $cert = vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithSan(['DNS:example.com', 'DNS:example.com']);

    $result = CertificateParser::parse($cert);

    expect($result['domains'])->toHaveCount(1);
});

test('lowercases domains', function () {
    $cert = vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithSan(['DNS:EXAMPLE.COM']);

    $result = CertificateParser::parse($cert);

    expect($result['domains'])->toContain('example.com');
});

/**
 * @param  array<int, string>  $sans
 */
function vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithSan(array $sans, int $days = 365): string
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

function vitoPestUnitActionsSSLCertificateParserTestGenerateCertWithoutSan(string $cn): string
{
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new(['commonName' => $cn], $key);
    $cert = openssl_csr_sign($csr, null, $key, 365);

    openssl_x509_export($cert, $certPem);

    return $certPem;
}
