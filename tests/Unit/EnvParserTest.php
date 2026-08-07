<?php

use App\Helpers\EnvParser;

test('parse simple env', function () {
    $raw = "APP_NAME=Laravel\nAPP_ENV=production";
    $result = EnvParser::parse($raw);

    expect($result)->toHaveCount(2);
    expect($result[0]['key'])->toEqual('APP_NAME');
    expect($result[0]['value'])->toEqual('Laravel');
    expect($result[0]['is_secret'])->toBeFalse();
    expect($result[1]['key'])->toEqual('APP_ENV');
    expect($result[1]['value'])->toEqual('production');
});

test('parse quoted values', function () {
    $raw = 'APP_NAME="My Laravel App"';
    $result = EnvParser::parse($raw);

    expect($result)->toHaveCount(1);
    expect($result[0]['value'])->toEqual('My Laravel App');
});

test('parse single quoted values', function () {
    $raw = "APP_NAME='My Laravel App'";
    $result = EnvParser::parse($raw);

    expect($result)->toHaveCount(1);
    expect($result[0]['value'])->toEqual('My Laravel App');
});

test('parse skips comments', function () {
    $raw = "# This is a comment\nAPP_NAME=Laravel\n# Another comment";
    $result = EnvParser::parse($raw);

    expect($result)->toHaveCount(1);
    expect($result[0]['key'])->toEqual('APP_NAME');
});

test('parse skips empty lines', function () {
    $raw = "APP_NAME=Laravel\n\n\nAPP_ENV=production";
    $result = EnvParser::parse($raw);

    expect($result)->toHaveCount(2);
});

test('parse handles value with equals', function () {
    $raw = 'APP_KEY=base64:abc=123=def=';
    $result = EnvParser::parse($raw);

    expect($result)->toHaveCount(1);
    expect($result[0]['value'])->toEqual('base64:abc=123=def=');
});

test('is secret key detects password', function () {
    $raw = 'DB_PASSWORD=secret123';
    $result = EnvParser::parse($raw);

    expect($result[0]['is_secret'])->toBeTrue();
});

test('is secret key detects secret', function () {
    $raw = 'APP_SECRET=abc123';
    $result = EnvParser::parse($raw);

    expect($result[0]['is_secret'])->toBeTrue();
});

test('is secret key detects token', function () {
    $raw = 'API_TOKEN=xyz789';
    $result = EnvParser::parse($raw);

    expect($result[0]['is_secret'])->toBeTrue();
});

test('is secret key detects key', function () {
    $raw = 'APP_KEY=base64:abc123';
    $result = EnvParser::parse($raw);

    expect($result[0]['is_secret'])->toBeTrue();
});

test('is secret key detects private', function () {
    $raw = 'PRIVATE_KEY=-----BEGIN RSA-----';
    $result = EnvParser::parse($raw);

    expect($result[0]['is_secret'])->toBeTrue();
});

test('stringify simple variables', function () {
    $variables = [
        ['key' => 'APP_NAME', 'value' => 'Laravel'],
        ['key' => 'APP_ENV', 'value' => 'production'],
    ];
    $result = EnvParser::stringify($variables);

    expect($result)->toEqual("APP_NAME=Laravel\nAPP_ENV=production");
});

test('stringify quotes values with spaces', function () {
    $variables = [
        ['key' => 'APP_NAME', 'value' => 'My Laravel App'],
    ];
    $result = EnvParser::stringify($variables);

    expect($result)->toEqual('APP_NAME="My Laravel App"');
});

test('stringify quotes values with newlines', function () {
    $variables = [
        ['key' => 'MULTILINE', 'value' => "line1\nline2"],
    ];
    $result = EnvParser::stringify($variables);

    expect($result)->toEqual('MULTILINE="line1\nline2"');
});

test('stringify leaves a value containing double quotes unquoted when nothing forces quoting', function () {
    $variables = [
        ['key' => 'VITE_PAYMENT_METHODS_MOLLIE', 'value' => '["ideal","paybybank","bancontact"]'],
    ];
    $result = EnvParser::stringify($variables);

    expect($result)->toEqual('VITE_PAYMENT_METHODS_MOLLIE=["ideal","paybybank","bancontact"]');
});

/**
 * @return array<string, array<int, string>>
 */
dataset('jsonArrayOnDiskProvider', function () {
    return [
        'bare' => ['VITE_PAYMENT_METHODS_MOLLIE=["ideal","paybybank","bancontact"]'],
        'single quoted' => ["VITE_PAYMENT_METHODS_MOLLIE='[\"ideal\",\"paybybank\",\"bancontact\"]'"],
        'already escaped' => ['VITE_PAYMENT_METHODS_MOLLIE="[\\"ideal\\",\\"paybybank\\",\\"bancontact\\"]"'],
    ];
});

test('saving rewrites every on disk form of a json array value as valid json', function (string $raw) {
    $parsed = EnvParser::parse($raw);

    expect($parsed)->toHaveCount(1);
    expect($parsed[0]['value'])->toEqual('["ideal","paybybank","bancontact"]');
    expect(EnvParser::stringify($parsed))->toEqual('VITE_PAYMENT_METHODS_MOLLIE=["ideal","paybybank","bancontact"]');
})->with('jsonArrayOnDiskProvider');

test('stringify prefers single quotes over escaping double quotes', function () {
    $variables = [
        ['key' => 'K', 'value' => 'he said "hi"'],
    ];
    $result = EnvParser::stringify($variables);

    expect($result)->toEqual('K=\'he said "hi"\'');
});

test('stringify keeps a mid string double quote unquoted', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => 'x"y']]);

    expect($result)->toEqual('K=x"y');
});

test('stringify quotes values containing a hash', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => '#fff']]);

    expect($result)->toEqual('K="#fff"');
});

test('stringify quotes a value that starts with a double quote', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => '"hello"']]);

    expect($result)->toEqual('K=\'"hello"\'');
});

test('stringify quotes a value that starts with a single quote', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => "'hello'"]]);

    expect($result)->toEqual('K="\'hello\'"');
});

test('stringify escapes carriage returns rather than writing them raw', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => "a\rb"]]);

    expect($result)->toEqual('K="a\rb"');
    expect(EnvParser::parse($result)[0]['value'])->toEqual("a\rb");
});

test('stringify does not single quote a carriage return value', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => "a\rb\"c"]]);

    expect($result)->toEqual('K="a\rb\"c"');
    expect(EnvParser::parse($result)[0]['value'])->toEqual("a\rb\"c");
});

test('stringify escapes when both quote styles are present', function () {
    $result = EnvParser::stringify([['key' => 'K', 'value' => 'it\'s "quoted"']]);

    expect($result)->toEqual('K="it\'s \\"quoted\\""');
});

test('stringify skips empty keys', function () {
    $variables = [
        ['key' => '', 'value' => 'empty'],
        ['key' => 'APP_NAME', 'value' => 'Laravel'],
    ];
    $result = EnvParser::stringify($variables);

    expect($result)->toEqual('APP_NAME=Laravel');
});

test('roundtrip preserves data', function () {
    $original = "APP_NAME=Laravel\nDB_PASSWORD=secret123\nAPP_KEY=base64:abc=123=";
    $parsed = EnvParser::parse($original);
    $stringified = EnvParser::stringify($parsed);
    $reParsed = EnvParser::parse($stringified);

    expect($reParsed)->toHaveCount(count($parsed));
    foreach ($parsed as $i => $var) {
        expect($reParsed[$i]['key'])->toEqual($var['key']);
        expect($reParsed[$i]['value'])->toEqual($var['value']);
    }
});

/**
 * @return array<string, array<int, string>>
 */
dataset('roundtripValueProvider', function () {
    return [
        'double quote' => ['he said "hi"'],
        'literal backslash n unquoted' => ['C:\name'],
        'literal backslash n quoted' => ['%h \n %t'],
        'hash' => ['#fff'],
        'spaces' => ['My Laravel App'],
        'trailing backslash inside quotes' => ['abc def\\'],
        'trailing backslash unquoted' => ['trail\\'],
        'multi line' => ["line1\nline2"],
        'empty' => [''],
        'base64 padding' => ['base64:abc=123='],
        'backslash and quote' => ['C:\dir "x"'],
        'json array' => ['["ideal","paybybank","bancontact"]'],
        'leading double quote' => ['"hello"'],
        'leading single quote' => ["'hello'"],
        'tab' => ["a\tb"],
        'trailing tab' => ["a\t"],
        'carriage return' => ["a\rb"],
        'carriage return with double quote' => ["a\rb\"c"],
    ];
});

test('roundtrip preserves every value shape', function (string $value) {
    $stringified = EnvParser::stringify([['key' => 'K', 'value' => $value]]);
    $reParsed = EnvParser::parse($stringified);

    expect($reParsed)->toHaveCount(1);
    expect($reParsed[0]['key'])->toEqual('K');
    expect($reParsed[0]['value'])->toEqual($value);
})->with('roundtripValueProvider');

/**
 * @return array<string, array<int, string>>
 */
dataset('representableContentProvider', function () {
    return [
        'comments and blank lines' => ["# Application\n\nAPP_NAME=TestApp"],
        'crlf line endings' => ["APP_NAME=TestApp\r\nAPP_ENV=production"],
        'single quoted value' => ["A='single quoted'"],
        'duplicate keys' => ["A=1\nA=2"],
        'whitespace around equals' => [' A = 1 '],
        'dashed key' => ['MY-KEY=1'],
        'dotted key' => ['MY.KEY=1'],
        'leading digit key' => ['2FA_ENABLED=1'],
        'empty value' => ['DB_PASSWORD='],
        'escaped quotes' => ['K="he said \\"hi\\""'],
    ];
});

test('is representable accepts content the form can hold', function (string $content) {
    expect(EnvParser::isRepresentable($content, EnvParser::parse($content)))->toBeTrue();
})->with('representableContentProvider');

/**
 * @return array<string, array<int, string>>
 */
dataset('unrepresentableContentProvider', function () {
    return [
        'pem block' => ["KEY=\"-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAAKCAQEA\n-----END RSA PRIVATE KEY-----\""],
        'continuation line containing equals' => ["EXTRA_CONFIG=\"foo=1\nbar=2\""],
        'single quoted multi line' => ["A='foo=1\nbar=2'"],
        'escaped closing quote' => ["A=\"foo\\\"\nb=2\""],
        'export prefix' => ['export FOO=bar'],
    ];
});

test('is representable rejects content the form would mangle', function (string $content) {
    expect(EnvParser::isRepresentable($content, EnvParser::parse($content)))->toBeFalse();
})->with('unrepresentableContentProvider');

test('mask secrets hides secret values', function () {
    $variables = [
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
        ['key' => 'DB_PASSWORD', 'value' => 'supersecret', 'is_secret' => true],
        ['key' => 'API_TOKEN', 'value' => 'token123', 'is_secret' => true],
    ];

    $masked = EnvParser::maskSecrets($variables);

    expect($masked[0]['value'])->toEqual('Laravel');
    expect($masked[1]['value'])->toEqual('');
    expect($masked[2]['value'])->toEqual('');
});

test('merge with live preserves empty secret values', function () {
    $live = [
        ['key' => 'DB_PASSWORD', 'value' => 'original_secret', 'is_secret' => true],
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
    ];

    $incoming = [
        ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => true],
        ['key' => 'APP_NAME', 'value' => 'NewName', 'is_secret' => false],
    ];

    $merged = EnvParser::mergeWithLive($incoming, $live);

    expect($merged[0]['value'])->toEqual('original_secret');
    expect($merged[1]['value'])->toEqual('NewName');
});

test('merge with live allows updating secret values', function () {
    $live = [
        ['key' => 'DB_PASSWORD', 'value' => 'original_secret', 'is_secret' => true],
    ];

    $incoming = [
        ['key' => 'DB_PASSWORD', 'value' => 'new_secret', 'is_secret' => true],
    ];

    $merged = EnvParser::mergeWithLive($incoming, $live);

    expect($merged[0]['value'])->toEqual('new_secret');
});

test('merge with empty live returns incoming', function () {
    $incoming = [
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
    ];

    expect(EnvParser::mergeWithLive($incoming, []))->toEqual($incoming);
});

test('merge restores secret from live file value', function () {
    $live = [
        ['key' => 'DB_PASSWORD', 'value' => 'rotated_secret', 'is_secret' => true],
    ];

    $incoming = [
        ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => true],
    ];

    $merged = EnvParser::mergeWithLive($incoming, $live);

    expect($merged[0]['value'])->toEqual('rotated_secret');
});

test('merge does not restore non secret empty values', function () {
    $live = [
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
    ];

    $incoming = [
        ['key' => 'APP_NAME', 'value' => '', 'is_secret' => false],
    ];

    $merged = EnvParser::mergeWithLive($incoming, $live);

    expect($merged[0]['value'])->toEqual('');
});

test('secret keys from flat list', function () {
    expect(EnvParser::secretKeys(['APP_KEY', 'JWT_SECRET']))->toEqual(['APP_KEY', 'JWT_SECRET']);
});

test('secret keys from legacy variable array', function () {
    $legacy = [
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
        ['key' => 'DB_PASSWORD', 'value' => 'secret', 'is_secret' => true],
        ['key' => 'APP_KEY', 'value' => 'base64:abc', 'is_secret' => true],
    ];

    expect(EnvParser::secretKeys($legacy))->toEqual(['DB_PASSWORD', 'APP_KEY']);
});

test('secret keys deduplicates', function () {
    expect(EnvParser::secretKeys(['APP_KEY', 'APP_KEY']))->toEqual(['APP_KEY']);
});

test('secret keys from null returns empty', function () {
    expect(EnvParser::secretKeys(null))->toEqual([]);
});

test('classify marks only stored secret keys', function () {
    $parsed = [
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
        ['key' => 'DB_PASSWORD', 'value' => 'plain', 'is_secret' => true],
    ];

    $classified = EnvParser::classify($parsed, ['APP_NAME']);

    expect($classified[0]['is_secret'])->toBeTrue();
    expect($classified[1]['is_secret'])->toBeFalse();
});

test('classify reads legacy stored shape', function () {
    $parsed = [
        ['key' => 'DB_PASSWORD', 'value' => 'plain', 'is_secret' => false],
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
    ];

    $legacy = [
        ['key' => 'DB_PASSWORD', 'value' => 'old', 'is_secret' => true],
        ['key' => 'APP_NAME', 'value' => 'old', 'is_secret' => false],
    ];

    $classified = EnvParser::classify($parsed, $legacy);

    expect($classified[0]['is_secret'])->toBeTrue();
    expect($classified[1]['is_secret'])->toBeFalse();
});

test('classify with null stored falls back to auto detection', function () {
    $parsed = [
        ['key' => 'DB_PASSWORD', 'value' => 'plain', 'is_secret' => true],
        ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
    ];

    $classified = EnvParser::classify($parsed, null);

    expect($classified[0]['is_secret'])->toBeTrue();
    expect($classified[1]['is_secret'])->toBeFalse();
});

test('classify with empty stored list is authoritative', function () {
    $parsed = [
        ['key' => 'DB_PASSWORD', 'value' => 'plain', 'is_secret' => true],
    ];

    $classified = EnvParser::classify($parsed, []);

    expect($classified[0]['is_secret'])->toBeFalse();
});
