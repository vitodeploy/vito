<?php

namespace Tests\Unit\Actions\SSL;

use App\Actions\SSL\CreateServerCsr;
use PHPUnit\Framework\TestCase;

class ParseDomainsTest extends TestCase
{
    public function test_single_domain(): void
    {
        $result = CreateServerCsr::parseDomains('example.com');

        $this->assertEquals(['example.com'], $result);
    }

    public function test_multiple_comma_separated_domains(): void
    {
        $result = CreateServerCsr::parseDomains('example.com,api.example.com');

        $this->assertEquals(['example.com', 'api.example.com'], $result);
    }

    public function test_comma_separated_with_spaces(): void
    {
        $result = CreateServerCsr::parseDomains('example.com, api.example.com , mail.example.com');

        $this->assertEquals(['example.com', 'api.example.com', 'mail.example.com'], $result);
    }

    public function test_www_domain_kept_as_is(): void
    {
        $result = CreateServerCsr::parseDomains('www.example.com');

        $this->assertEquals(['www.example.com'], $result);
    }

    public function test_www_domain_with_other_domains(): void
    {
        $result = CreateServerCsr::parseDomains('www.example.com, api.example.com');

        $this->assertEquals(['www.example.com', 'api.example.com'], $result);
    }

    public function test_bare_domain_does_not_add_www(): void
    {
        $result = CreateServerCsr::parseDomains('example.com');

        $this->assertNotContains('www.example.com', $result);
        $this->assertEquals(['example.com'], $result);
    }

    public function test_www_and_bare_both_kept(): void
    {
        $result = CreateServerCsr::parseDomains('www.example.com, example.com');

        $this->assertEquals(['www.example.com', 'example.com'], $result);
    }

    public function test_duplicate_domains_are_deduplicated(): void
    {
        $result = CreateServerCsr::parseDomains('example.com, example.com, api.example.com');

        $this->assertEquals(['example.com', 'api.example.com'], $result);
    }

    public function test_domains_are_lowercased(): void
    {
        $result = CreateServerCsr::parseDomains('Example.COM, API.Example.com');

        $this->assertEquals(['example.com', 'api.example.com'], $result);
    }

    public function test_www_lowercased(): void
    {
        $result = CreateServerCsr::parseDomains('WWW.Example.com');

        $this->assertEquals(['www.example.com'], $result);
    }

    public function test_empty_parts_are_ignored(): void
    {
        $result = CreateServerCsr::parseDomains('example.com,,, api.example.com,');

        $this->assertEquals(['example.com', 'api.example.com'], $result);
    }

    public function test_wildcard_domain(): void
    {
        $result = CreateServerCsr::parseDomains('*.example.com');

        $this->assertEquals(['*.example.com'], $result);
    }

    public function test_subdomain(): void
    {
        $result = CreateServerCsr::parseDomains('mail.example.com');

        $this->assertEquals(['mail.example.com'], $result);
    }

    public function test_multiple_www_domains(): void
    {
        $result = CreateServerCsr::parseDomains('www.example.com, www.other.com');

        $this->assertEquals(['www.example.com', 'www.other.com'], $result);
    }

    public function test_single_domain_with_whitespace(): void
    {
        $result = CreateServerCsr::parseDomains('  example.com  ');

        $this->assertEquals(['example.com'], $result);
    }
}
