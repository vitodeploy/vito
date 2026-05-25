<?php

namespace Tests\Unit\Actions\Webserver;

use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Ssl;
use App\Services\Webserver\Apache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateApacheConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server->webserver()?->update(['name' => Apache::id()]);
        $this->site->refresh();

        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);
    }

    public function test_php_site_generates_virtualhost_with_fpm_handler(): void
    {
        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('<VirtualHost *:80>', $vhost);
        $this->assertStringContainsString('ServerName '.$this->site->domain, $vhost);
        $this->assertStringContainsString('DocumentRoot '.$this->site->getWebDirectoryPath(), $vhost);
        $this->assertStringContainsString('<FilesMatch \.php$>', $vhost);
        $this->assertStringContainsString('SetHandler "proxy:unix:', $vhost);
        $this->assertStringContainsString('|fcgi://localhost"', $vhost);
        $this->assertStringContainsString('RewriteRule ^ index.php [L]', $vhost);
        $this->assertStringContainsString('RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/', $vhost);
        $this->assertStringContainsString('<Location "/.well-known/acme-challenge/">', $vhost);
    }

    public function test_acme_challenge_remains_public_when_basic_auth_enabled(): void
    {
        $this->site->type_data = [
            'basic_auth' => [
                'enabled' => true,
                'users' => [
                    ['username' => 'alice', 'apr1' => '$apr1$saltxxxx$hashhashhashhashhashhh', 'bcrypt' => '$2y$10$bcrypthashhere'],
                ],
            ],
        ];
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $authPos = strpos($vhost, 'Require valid-user');
        $acmePos = strpos($vhost, '<Location "/.well-known/acme-challenge/">');

        $this->assertNotFalse($authPos);
        $this->assertNotFalse($acmePos);
        $this->assertGreaterThan($authPos, $acmePos, 'ACME exemption must come after the auth Location so it wins for that path.');
    }

    public function test_reverse_proxy_excludes_acme_challenge_from_proxy(): void
    {
        $this->site->update([
            'type' => 'nodejs',
            'port' => 3000,
        ]);
        $this->site->refresh();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('ProxyPass /.well-known/acme-challenge/ !', $vhost);
    }

    public function test_basic_auth_adds_auth_directives_and_acme_exemption(): void
    {
        $this->site->type_data = [
            'basic_auth' => [
                'enabled' => true,
                'users' => [
                    ['username' => 'alice', 'apr1' => '$apr1$saltxxxx$hashhashhashhashhashhh', 'bcrypt' => '$2y$10$bcrypthashhere'],
                ],
            ],
        ];
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('AuthType Basic', $vhost);
        $this->assertStringContainsString('AuthName "'.$this->site->domain.'"', $vhost);
        $this->assertStringContainsString('AuthUserFile /etc/apache2/auth/site-'.$this->site->id.'.htpasswd', $vhost);
        $this->assertStringContainsString('Require valid-user', $vhost);
        $this->assertStringContainsString('<Location "/.well-known/acme-challenge/">', $vhost);
    }

    public function test_reverse_proxy_site_generates_proxypass(): void
    {
        $this->site->update([
            'type' => 'nodejs',
            'port' => 3000,
        ]);
        $this->site->refresh();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('ProxyPass / http://localhost:3000/', $vhost);
        $this->assertStringContainsString('ProxyPassReverse / http://localhost:3000/', $vhost);
    }

    public function test_force_ssl_redirect_serves_and_exempts_verification_challenge(): void
    {
        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => SslStatus::CREATED,
            'type' => 'letsencrypt',
            'domains' => [$this->site->domain],
        ]);

        $this->site->hostedDomains()->update(['ssl_id' => $ssl->id]);

        $this->site->update([
            'ssl_enabled' => true,
            'force_ssl' => true,
            'verification_key' => 'forcedKey55',
        ]);
        $this->site->refresh();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString(
            "RewriteCond %{REQUEST_URI} !^/\\.well-known/vito/\n    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]",
            $vhost,
            'The force-SSL redirect must exempt the verification path so port-80 verification is not 301-redirected.'
        );
        $this->assertStringContainsString(
            'Alias "/.well-known/vito/forcedKey55" "/var/lib/vito/verify/forcedKey55"',
            $vhost
        );
    }
}
