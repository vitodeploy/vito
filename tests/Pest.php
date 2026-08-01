<?php

use App\Enums\LoadBalancerMethod;
use App\SiteTypes\BunSite;
use App\SiteTypes\Laravel;
use App\SiteTypes\LoadBalancer;
use App\SiteTypes\NodeSite;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\PHPMyAdmin;
use App\SiteTypes\Wordpress;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\ArchTestCase;
use Tests\TestCase;

pest()->extend(ArchTestCase::class)->in('Arch');

pest()->extend(TestCase::class)->in(
    'Feature',
    'Unit/*/',
    'Unit/AbstractProxiedSiteTypeTest.php',
    'Unit/BunSiteTest.php',
    'Unit/IsolatedUserModelTest.php',
    'Unit/NodeSiteTest.php',
    'Unit/SiteEnvPathTest.php',
    'Unit/SiteShellEnvironmentTest.php',
);

/**
 * Every PHP file under the given sub-directory, relative to app/ unless another
 * base directory is given.
 *
 * @return array<int, SplFileInfo>
 */
function vitoArchFiles(string $directory = '', ?string $base = null): array
{
    $path = ($base ?? app_path()).($directory === '' ? '' : DIRECTORY_SEPARATOR.$directory);

    return iterator_to_array(
        Finder::create()->files()->in($path)->name('*.php'),
        false
    );
}

/**
 * Every concrete, autoloadable class under the given app/ sub-directory.
 *
 * @return array<int, class-string>
 */
function vitoArchClasses(string $directory = ''): array
{
    $classes = [];

    foreach (vitoArchFiles($directory) as $file) {
        if (preg_match('/^(?:final |abstract |readonly )*class /m', $file->getContents()) !== 1) {
            continue;
        }

        $class = Str::of($file->getRealPath())
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''])
            ->prepend('App\\')
            ->toString();

        if (! class_exists($class) || (new ReflectionClass($class))->isAbstract()) {
            continue;
        }

        $classes[] = $class;
    }

    return $classes;
}

/**
 * @return array<array<array<string, mixed>>>
 */
function vitoPestSiteCreateData(): array
{
    return [
        [
            [
                'type' => Laravel::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'repository' => 'test/test',
                'branch' => 'main',
                'composer' => true,
                'node_version' => 'none',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => Wordpress::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'title' => 'Example',
                'username' => 'example',
                'email' => 'email@example.com',
                'password' => 'password',
                'database' => '1',
                'database_user' => '1',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => PHPBlank::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'web_directory' => 'public',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => PHPMyAdmin::id(),
                'domain' => 'example.com',
                'php_version' => '8.2',
                'version' => '5.1.2',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => LoadBalancer::id(),
                'domain' => 'example.com',
                'user' => 'example',
                'method' => LoadBalancerMethod::ROUND_ROBIN->value,
            ],
        ],
        [
            [
                'type' => NodeSite::id(),
                'domain' => 'example.com',
                'node_version' => '23',
                'package_manager' => 'node',
                'port' => '3000',
                'repository' => 'test/test',
                'branch' => 'main',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => NodeSite::id(),
                'domain' => 'example.com',
                'node_version' => '22',
                'package_manager' => 'yarn',
                'yarn_version' => '4',
                'port' => '3000',
                'repository' => 'test/test',
                'branch' => 'main',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => NodeSite::id(),
                'domain' => 'example.com',
                'node_version' => '22',
                'package_manager' => 'pnpm',
                'pnpm_version' => '9',
                'port' => '3000',
                'repository' => 'test/test',
                'branch' => 'main',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => BunSite::id(),
                'domain' => 'example.com',
                'bun_version' => '1.2',
                'port' => '3000',
                'repository' => 'test/test',
                'branch' => 'main',
                'user' => 'example',
            ],
        ],
        [
            [
                'type' => BunSite::id(),
                'domain' => 'example.com',
                'bun_version' => '1.1',
                'port' => '3000',
                'repository' => 'test/test',
                'branch' => 'main',
                'user' => 'example',
            ],
        ],
    ];
}
