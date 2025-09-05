<?php

namespace Tests\Unit\Plugins;

use App\Plugins\LegacyPlugins;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class LegacyPluginsTest extends TestCase
{
    private LegacyPlugins $plugins;

    private string $pluginsBackupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins = new LegacyPlugins;
        $this->pluginsBackupPath = storage_path('legacy_plugins_backup_'.time());

        $this->moveExistingPlugins();
        $this->cleanupTestPlugins();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestPlugins();
        $this->restoreExistingPlugins();

        parent::tearDown();
    }

    private function moveExistingPlugins(): void
    {
        $pluginsPath = storage_path('plugins');

        if (File::exists($pluginsPath)) {
            File::moveDirectory($pluginsPath, $this->pluginsBackupPath);
        }

        File::makeDirectory($pluginsPath, 0755, true);
    }

    private function restoreExistingPlugins(): void
    {
        $pluginsPath = storage_path('plugins');

        if (File::exists($pluginsPath)) {
            File::deleteDirectory($pluginsPath);
        }

        if (File::exists($this->pluginsBackupPath)) {
            File::moveDirectory($this->pluginsBackupPath, $pluginsPath);
        }
    }

    private function cleanupTestPlugins(): void
    {
        $pluginsPath = storage_path('plugins');
        if (! File::exists($pluginsPath)) {
            return;
        }

        $directories = File::directories($pluginsPath);
        foreach ($directories as $directory) {
            $dirName = basename($directory);
            if (str_starts_with($dirName, 'test-')) {
                File::deleteDirectory($directory);
            }
        }

        $installedPath = $pluginsPath.'/.installed';
        if (File::exists($installedPath)) {
            $installedDirectories = File::directories($installedPath);
            foreach ($installedDirectories as $directory) {
                $dirName = basename($directory);
                if (str_starts_with($dirName, 'test-')) {
                    File::deleteDirectory($directory);
                }
            }
        }
    }

    public function test_all_returns_empty_array_when_no_plugins(): void
    {
        $result = $this->plugins->all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_all_returns_plugins_with_valid_composer_json(): void
    {
        $pluginsPath = storage_path('plugins');
        $vendorPath = $pluginsPath.'/test-vendor';
        $pluginPath = $vendorPath.'/test-plugin';
        File::makeDirectory($pluginPath, 0755, true);

        $composerData = [
            'name' => 'test-vendor/test-plugin',
            'version' => '1.0.0',
        ];
        File::put($pluginPath.'/composer.json', json_encode($composerData));

        $result = $this->plugins->all();

        $this->assertCount(1, $result);
        $this->assertEquals('test-vendor/test-plugin', $result[0]['name']);
        $this->assertEquals('1.0.0', $result[0]['version']);
    }

    public function test_all_handles_missing_name_and_version(): void
    {
        $pluginsPath = storage_path('plugins');
        $vendorPath = $pluginsPath.'/test-vendor';
        $pluginPath = $vendorPath.'/test-plugin';
        File::makeDirectory($pluginPath, 0755, true);

        File::put($pluginPath.'/composer.json', json_encode([]));

        $result = $this->plugins->all();

        $this->assertCount(1, $result);
        $this->assertEquals('Unknown', $result[0]['name']);
        $this->assertEquals('Unknown', $result[0]['version']);
    }

    public function test_all_skips_directories_without_composer_json(): void
    {
        $pluginsPath = storage_path('plugins');
        $vendorPath = $pluginsPath.'/test-vendor';
        $pluginPath = $vendorPath.'/test-plugin';
        File::makeDirectory($pluginPath, 0755, true);

        $result = $this->plugins->all();

        $this->assertEmpty($result);
    }

    public function test_install_creates_plugin_directory(): void
    {
        Process::fake([
            'git clone*' => Process::result(output: 'Cloning into plugin...'),
            'composer require*' => Process::result(output: 'Package installed successfully'),
        ]);

        $url = 'https://github.com/test-vendor/test-plugin.git';

        $result = $this->plugins->install($url);

        $this->assertIsString($result);
        $this->assertStringContainsString('Cloning into plugin...', $result);
    }

    public function test_install_with_branch(): void
    {
        Process::fake([
            'git clone https://github.com/test-vendor/test-plugin.git * --branch main*' => Process::result(output: 'Cloning...'),
            'composer require*' => Process::result(output: 'Package installed'),
        ]);

        $url = 'https://github.com/test-vendor/test-plugin.git';

        $result = $this->plugins->install($url, 'main');

        $this->assertStringContainsString('Cloning...', $result);
    }

    public function test_install_with_tag(): void
    {
        Process::fake([
            'git clone https://github.com/test-vendor/test-plugin.git * --tag v1.0.0*' => Process::result(output: 'Cloning...'),
            'composer require*' => Process::result(output: 'Package installed'),
        ]);

        $url = 'https://github.com/test-vendor/test-plugin.git';

        $result = $this->plugins->install($url, null, 'v1.0.0');

        $this->assertStringContainsString('Cloning...', $result);
    }

    public function test_install_throws_exception_on_git_failure(): void
    {
        Process::fake([
            'git clone*' => Process::result(exitCode: 1, errorOutput: 'Git clone failed'),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Git clone failed');

        $url = 'https://github.com/test-vendor/test-plugin.git';
        $this->plugins->install($url);
    }

    public function test_load_processes_plugins_with_composer_json(): void
    {
        Process::fake([
            'composer require test-vendor/test-plugin' => Process::result(output: 'Package installed'),
        ]);

        $vendorPath = storage_path('plugins/test-vendor');
        $pluginPath = $vendorPath.'/test-plugin';
        File::makeDirectory($pluginPath, 0755, true);

        $composerData = [
            'name' => 'test-vendor/test-plugin',
            'version' => '1.0.0',
        ];
        File::put($pluginPath.'/composer.json', json_encode($composerData));

        $result = $this->plugins->load();

        $this->assertStringContainsString('Package installed', $result);
    }

    public function test_load_skips_plugins_with_invalid_names(): void
    {
        $vendorPath = storage_path('plugins/test-vendor');
        $pluginPath = $vendorPath.'/test-plugin';
        File::makeDirectory($pluginPath, 0755, true);

        $composerData = [
            'name' => 'invalid-name-without-vendor',
            'version' => '1.0.0',
        ];
        File::put($pluginPath.'/composer.json', json_encode($composerData));

        $result = $this->plugins->load();

        $this->assertEmpty($result);
    }

    public function test_load_throws_exception_on_composer_failure(): void
    {
        Process::fake([
            'composer require test-vendor/test-plugin' => Process::result(exitCode: 1, errorOutput: 'Composer failed'),
        ]);

        $vendorPath = storage_path('plugins/test-vendor');
        $pluginPath = $vendorPath.'/test-plugin';
        File::makeDirectory($pluginPath, 0755, true);

        $composerData = [
            'name' => 'test-vendor/test-plugin',
            'version' => '1.0.0',
        ];
        File::put($pluginPath.'/composer.json', json_encode($composerData));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Composer failed');

        $this->plugins->load();
    }

    public function test_uninstall_removes_plugin_and_runs_composer_remove(): void
    {
        Process::fake([
            'echo "Uninstalling..."' => Process::result(output: 'Uninstalling...'),
            'composer remove test-vendor/test-plugin' => Process::result(output: 'Package removed'),
        ]);

        $pluginPath = storage_path('plugins/test-vendor/test-plugin');
        File::makeDirectory($pluginPath, 0755, true);

        $composerData = [
            'name' => 'test-vendor/test-plugin',
            'scripts' => [
                'pre-package-uninstall' => ['echo "Uninstalling..."'],
            ],
        ];
        File::put($pluginPath.'/composer.json', json_encode($composerData));

        $flagFile = storage_path('plugins/.installed/test-vendor/test-plugin');
        File::makeDirectory(dirname($flagFile), 0755, true);
        File::put($flagFile, now()->toISOString());

        $result = $this->plugins->uninstall('test-vendor/test-plugin');

        $this->assertStringContainsString('Package removed', $result);
        $this->assertFalse(File::exists($pluginPath));
        $this->assertFalse(File::exists($flagFile));
    }

    public function test_uninstall_throws_exception_for_nonexistent_plugin(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Plugin not found: nonexistent/plugin');

        $this->plugins->uninstall('nonexistent/plugin');
    }

    public function test_uninstall_throws_exception_on_composer_failure(): void
    {
        Process::fake([
            'composer remove test-vendor/test-plugin' => Process::result(exitCode: 1, output: 'Composer remove failed'),
        ]);

        $pluginPath = storage_path('plugins/test-vendor/test-plugin');
        File::makeDirectory($pluginPath, 0755, true);
        File::put($pluginPath.'/composer.json', json_encode(['name' => 'test-vendor/test-plugin']));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Composer remove failed');

        $this->plugins->uninstall('test-vendor/test-plugin');
    }

    public function test_cleanup_handles_missing_backup_files(): void
    {
        $composerJsonBackup = base_path('composer.json.bak');
        $composerLockBackup = base_path('composer.lock.bak');

        if (File::exists($composerJsonBackup)) {
            File::delete($composerJsonBackup);
        }
        if (File::exists($composerLockBackup)) {
            File::delete($composerLockBackup);
        }

        $this->plugins->cleanup();

        $this->assertTrue(true);
    }

    public function test_execute_install_plugin_scripts_runs_post_install_scripts(): void
    {
        Process::fake([
            'echo "Post install script"' => Process::result(output: 'Script executed'),
        ]);

        $composerJson = [
            'name' => 'test-vendor/test-plugin',
            'scripts' => [
                'post-package-install' => ['echo "Post install script"'],
            ],
        ];

        $reflection = new \ReflectionClass($this->plugins);
        $method = $reflection->getMethod('executeInstallPluginScripts');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugins, $composerJson);

        $this->assertStringContainsString('Script executed', $result);
    }

    public function test_execute_install_plugin_scripts_skips_if_already_installed(): void
    {
        $flagFile = storage_path('plugins/.installed/test-vendor/test-plugin');
        File::makeDirectory(dirname($flagFile), 0755, true);
        File::put($flagFile, now()->toISOString());

        $composerJson = [
            'name' => 'test-vendor/test-plugin',
            'scripts' => [
                'post-package-install' => ['echo "Post install script"'],
            ],
        ];

        $reflection = new \ReflectionClass($this->plugins);
        $method = $reflection->getMethod('executeInstallPluginScripts');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugins, $composerJson);

        $this->assertEmpty($result);

        File::delete($flagFile);
    }

    public function test_execute_composer_scripts_handles_script_failures(): void
    {
        Process::fake([
            'failing-command' => Process::result(exitCode: 1, errorOutput: 'Script failed'),
        ]);

        $composerJson = [
            'scripts' => [
                'post-package-install' => ['failing-command'],
            ],
        ];

        $reflection = new \ReflectionClass($this->plugins);
        $method = $reflection->getMethod('executeComposerScripts');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugins, $composerJson, 'post-package-install');

        $this->assertStringContainsString('Warning: Plugin script failed: Script failed', $result);
    }
}
