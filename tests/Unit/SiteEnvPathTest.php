<?php

namespace Tests\Unit;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteEnvPathTest extends TestCase
{
    use RefreshDatabase;

    private function site(?string $storedEnvPath = null): Site
    {
        return Site::factory()->make([
            'path' => '/home/vito/example.com',
            'type_data' => $storedEnvPath === null ? null : ['env_path' => $storedEnvPath],
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rejectedPathProvider(): array
    {
        return [
            'outside the site directory' => ['/etc/passwd'],
            'shell metacharacters' => ['/etc/passwd;id'],
            'command substitution' => ['/home/vito/example.com/$(id)'],
            'traversal' => ['/home/vito/example.com/../other/.env'],
            'trailing newline' => ["/home/vito/example.com/.env\n"],
            'empty string' => [''],
            'sibling directory sharing a prefix' => ['/home/vito/example.com-evil/.env'],
        ];
    }

    #[DataProvider('rejectedPathProvider')]
    public function test_it_rejects_paths_outside_the_site(string $path): void
    {
        $this->expectException(ValidationException::class);

        $this->site()->resolveEnvPath($path);
    }

    public function test_it_accepts_a_path_inside_the_site(): void
    {
        $this->assertEquals(
            '/home/vito/example.com/nested/.env',
            $this->site()->resolveEnvPath('/home/vito/example.com/nested/.env'),
        );
    }

    public function test_it_defaults_to_the_site_env_file(): void
    {
        $this->assertEquals('/home/vito/example.com/.env', $this->site()->resolveEnvPath());
    }

    public function test_it_exempts_the_stored_path_and_stays_idempotent(): void
    {
        $site = $this->site('/home/vito/legacy path/.env');

        $resolved = $site->resolveEnvPath();

        $this->assertEquals('/home/vito/legacy path/.env', $resolved);
        $this->assertEquals($resolved, $site->resolveEnvPath($resolved));
    }
}
