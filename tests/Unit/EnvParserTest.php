<?php

namespace Tests\Unit;

use App\Helpers\EnvParser;
use PHPUnit\Framework\TestCase;

class EnvParserTest extends TestCase
{
    public function test_parse_simple_env(): void
    {
        $raw = "APP_NAME=Laravel\nAPP_ENV=production";
        $result = EnvParser::parse($raw);

        $this->assertCount(2, $result);
        $this->assertEquals('APP_NAME', $result[0]['key']);
        $this->assertEquals('Laravel', $result[0]['value']);
        $this->assertFalse($result[0]['is_secret']);
        $this->assertEquals('APP_ENV', $result[1]['key']);
        $this->assertEquals('production', $result[1]['value']);
    }

    public function test_parse_quoted_values(): void
    {
        $raw = 'APP_NAME="My Laravel App"';
        $result = EnvParser::parse($raw);

        $this->assertCount(1, $result);
        $this->assertEquals('My Laravel App', $result[0]['value']);
    }

    public function test_parse_single_quoted_values(): void
    {
        $raw = "APP_NAME='My Laravel App'";
        $result = EnvParser::parse($raw);

        $this->assertCount(1, $result);
        $this->assertEquals('My Laravel App', $result[0]['value']);
    }

    public function test_parse_skips_comments(): void
    {
        $raw = "# This is a comment\nAPP_NAME=Laravel\n# Another comment";
        $result = EnvParser::parse($raw);

        $this->assertCount(1, $result);
        $this->assertEquals('APP_NAME', $result[0]['key']);
    }

    public function test_parse_skips_empty_lines(): void
    {
        $raw = "APP_NAME=Laravel\n\n\nAPP_ENV=production";
        $result = EnvParser::parse($raw);

        $this->assertCount(2, $result);
    }

    public function test_parse_handles_value_with_equals(): void
    {
        $raw = 'APP_KEY=base64:abc=123=def=';
        $result = EnvParser::parse($raw);

        $this->assertCount(1, $result);
        $this->assertEquals('base64:abc=123=def=', $result[0]['value']);
    }

    public function test_is_secret_key_detects_password(): void
    {
        $raw = 'DB_PASSWORD=secret123';
        $result = EnvParser::parse($raw);

        $this->assertTrue($result[0]['is_secret']);
    }

    public function test_is_secret_key_detects_secret(): void
    {
        $raw = 'APP_SECRET=abc123';
        $result = EnvParser::parse($raw);

        $this->assertTrue($result[0]['is_secret']);
    }

    public function test_is_secret_key_detects_token(): void
    {
        $raw = 'API_TOKEN=xyz789';
        $result = EnvParser::parse($raw);

        $this->assertTrue($result[0]['is_secret']);
    }

    public function test_is_secret_key_detects_key(): void
    {
        $raw = 'APP_KEY=base64:abc123';
        $result = EnvParser::parse($raw);

        $this->assertTrue($result[0]['is_secret']);
    }

    public function test_is_secret_key_detects_private(): void
    {
        $raw = 'PRIVATE_KEY=-----BEGIN RSA-----';
        $result = EnvParser::parse($raw);

        $this->assertTrue($result[0]['is_secret']);
    }

    public function test_stringify_simple_variables(): void
    {
        $variables = [
            ['key' => 'APP_NAME', 'value' => 'Laravel'],
            ['key' => 'APP_ENV', 'value' => 'production'],
        ];
        $result = EnvParser::stringify($variables);

        $this->assertEquals("APP_NAME=Laravel\nAPP_ENV=production", $result);
    }

    public function test_stringify_quotes_values_with_spaces(): void
    {
        $variables = [
            ['key' => 'APP_NAME', 'value' => 'My Laravel App'],
        ];
        $result = EnvParser::stringify($variables);

        $this->assertEquals('APP_NAME="My Laravel App"', $result);
    }

    public function test_stringify_quotes_values_with_newlines(): void
    {
        $variables = [
            ['key' => 'MULTILINE', 'value' => "line1\nline2"],
        ];
        $result = EnvParser::stringify($variables);

        $this->assertEquals('MULTILINE="line1\nline2"', $result);
    }

    public function test_stringify_skips_empty_keys(): void
    {
        $variables = [
            ['key' => '', 'value' => 'empty'],
            ['key' => 'APP_NAME', 'value' => 'Laravel'],
        ];
        $result = EnvParser::stringify($variables);

        $this->assertEquals('APP_NAME=Laravel', $result);
    }

    public function test_roundtrip_preserves_data(): void
    {
        $original = "APP_NAME=Laravel\nDB_PASSWORD=secret123\nAPP_KEY=base64:abc=123=";
        $parsed = EnvParser::parse($original);
        $stringified = EnvParser::stringify($parsed);
        $reParsed = EnvParser::parse($stringified);

        $this->assertCount(count($parsed), $reParsed);
        foreach ($parsed as $i => $var) {
            $this->assertEquals($var['key'], $reParsed[$i]['key']);
            $this->assertEquals($var['value'], $reParsed[$i]['value']);
        }
    }

    public function test_mask_secrets_hides_secret_values(): void
    {
        $variables = [
            ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
            ['key' => 'DB_PASSWORD', 'value' => 'supersecret', 'is_secret' => true],
            ['key' => 'API_TOKEN', 'value' => 'token123', 'is_secret' => true],
        ];

        $masked = EnvParser::maskSecrets($variables);

        $this->assertEquals('Laravel', $masked[0]['value']);
        $this->assertEquals('', $masked[1]['value']);
        $this->assertEquals('', $masked[2]['value']);
    }

    public function test_merge_with_stored_preserves_empty_secret_values(): void
    {
        $stored = [
            ['key' => 'DB_PASSWORD', 'value' => 'original_secret', 'is_secret' => true],
            ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
        ];

        $incoming = [
            ['key' => 'DB_PASSWORD', 'value' => '', 'is_secret' => true], // Empty = keep stored
            ['key' => 'APP_NAME', 'value' => 'NewName', 'is_secret' => false],
        ];

        $merged = EnvParser::mergeWithStored($incoming, $stored);

        $this->assertEquals('original_secret', $merged[0]['value']);
        $this->assertEquals('NewName', $merged[1]['value']);
    }

    public function test_merge_with_stored_allows_updating_secret_values(): void
    {
        $stored = [
            ['key' => 'DB_PASSWORD', 'value' => 'original_secret', 'is_secret' => true],
        ];

        $incoming = [
            ['key' => 'DB_PASSWORD', 'value' => 'new_secret', 'is_secret' => true], // New value provided
        ];

        $merged = EnvParser::mergeWithStored($incoming, $stored);

        $this->assertEquals('new_secret', $merged[0]['value']);
    }

    public function test_merge_with_null_stored_returns_incoming(): void
    {
        $incoming = [
            ['key' => 'APP_NAME', 'value' => 'Laravel', 'is_secret' => false],
        ];

        $merged = EnvParser::mergeWithStored($incoming, null);

        $this->assertEquals($incoming, $merged);
    }
}
