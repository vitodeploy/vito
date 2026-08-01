<?php

use App\Plugins\LegacyPlugins;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->plugins = new LegacyPlugins;
    $this->pluginsBackupPath = storage_path('legacy_plugins_backup_'.time());

    vitoPestUnitPluginsLegacyPluginsTestMoveExistingPlugins();
    vitoPestUnitPluginsLegacyPluginsTestCleanupTestPlugins();
});

afterEach(function () {
    vitoPestUnitPluginsLegacyPluginsTestCleanupTestPlugins();
    vitoPestUnitPluginsLegacyPluginsTestRestoreExistingPlugins();

});

function vitoPestUnitPluginsLegacyPluginsTestMoveExistingPlugins(): void
{
    $pluginsPath = storage_path('plugins');

    if (File::exists($pluginsPath)) {
        File::moveDirectory($pluginsPath, test()->pluginsBackupPath);
    }

    File::makeDirectory($pluginsPath, 0755, true);
}

function vitoPestUnitPluginsLegacyPluginsTestRestoreExistingPlugins(): void
{
    $pluginsPath = storage_path('plugins');

    if (File::exists($pluginsPath)) {
        File::deleteDirectory($pluginsPath);
    }

    if (File::exists(test()->pluginsBackupPath)) {
        File::moveDirectory(test()->pluginsBackupPath, $pluginsPath);
    }
}

function vitoPestUnitPluginsLegacyPluginsTestCleanupTestPlugins(): void
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

test('all returns empty array when no plugins', function () {
    $result = $this->plugins->all();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('all returns plugins with valid composer json', function () {
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

    expect($result)->toHaveCount(1);
    expect($result[0]['name'])->toEqual('test-vendor/test-plugin');
    expect($result[0]['version'])->toEqual('1.0.0');
});

test('all handles missing name and version', function () {
    $pluginsPath = storage_path('plugins');
    $vendorPath = $pluginsPath.'/test-vendor';
    $pluginPath = $vendorPath.'/test-plugin';
    File::makeDirectory($pluginPath, 0755, true);

    File::put($pluginPath.'/composer.json', json_encode([]));

    $result = $this->plugins->all();

    expect($result)->toHaveCount(1);
    expect($result[0]['name'])->toEqual('Unknown');
    expect($result[0]['version'])->toEqual('Unknown');
});

test('all skips directories without composer json', function () {
    $pluginsPath = storage_path('plugins');
    $vendorPath = $pluginsPath.'/test-vendor';
    $pluginPath = $vendorPath.'/test-plugin';
    File::makeDirectory($pluginPath, 0755, true);

    $result = $this->plugins->all();

    expect($result)->toBeEmpty();
});

test('install creates plugin directory', function () {
    Process::fake([
        'git clone*' => Process::result(output: 'Cloning into plugin...'),
        'composer require*' => Process::result(output: 'Package installed successfully'),
    ]);

    $url = 'https://github.com/test-vendor/test-plugin.git';

    $result = $this->plugins->install($url);

    expect($result)->toBeString();
    $this->assertStringContainsString('Cloning into plugin...', $result);
});

test('install with branch', function () {
    Process::fake([
        'git clone https://github.com/test-vendor/test-plugin.git * --branch main*' => Process::result(output: 'Cloning...'),
        'composer require*' => Process::result(output: 'Package installed'),
    ]);

    $url = 'https://github.com/test-vendor/test-plugin.git';

    $result = $this->plugins->install($url, 'main');

    $this->assertStringContainsString('Cloning...', $result);
});

test('install with tag', function () {
    Process::fake([
        'git clone https://github.com/test-vendor/test-plugin.git * --tag v1.0.0*' => Process::result(output: 'Cloning...'),
        'composer require*' => Process::result(output: 'Package installed'),
    ]);

    $url = 'https://github.com/test-vendor/test-plugin.git';

    $result = $this->plugins->install($url, null, 'v1.0.0');

    $this->assertStringContainsString('Cloning...', $result);
});

test('install throws exception on git failure', function () {
    Process::fake([
        'git clone*' => Process::result(exitCode: 1, errorOutput: 'Git clone failed'),
    ]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Git clone failed');

    $url = 'https://github.com/test-vendor/test-plugin.git';
    $this->plugins->install($url);
});

test('load processes plugins with composer json', function () {
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
});

test('load skips plugins with invalid names', function () {
    $vendorPath = storage_path('plugins/test-vendor');
    $pluginPath = $vendorPath.'/test-plugin';
    File::makeDirectory($pluginPath, 0755, true);

    $composerData = [
        'name' => 'invalid-name-without-vendor',
        'version' => '1.0.0',
    ];
    File::put($pluginPath.'/composer.json', json_encode($composerData));

    $result = $this->plugins->load();

    expect($result)->toBeEmpty();
});

test('load throws exception on composer failure', function () {
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
});

test('uninstall removes plugin and runs composer remove', function () {
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
    expect(File::exists($pluginPath))->toBeFalse();
    expect(File::exists($flagFile))->toBeFalse();
});

test('uninstall throws exception for nonexistent plugin', function () {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Plugin not found: nonexistent/plugin');

    $this->plugins->uninstall('nonexistent/plugin');
});

test('uninstall throws exception on composer failure', function () {
    Process::fake([
        'composer remove test-vendor/test-plugin' => Process::result(exitCode: 1, output: 'Composer remove failed'),
    ]);

    $pluginPath = storage_path('plugins/test-vendor/test-plugin');
    File::makeDirectory($pluginPath, 0755, true);
    File::put($pluginPath.'/composer.json', json_encode(['name' => 'test-vendor/test-plugin']));

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Composer remove failed');

    $this->plugins->uninstall('test-vendor/test-plugin');
});

test('cleanup handles missing backup files', function () {
    $composerJsonBackup = base_path('composer.json.bak');
    $composerLockBackup = base_path('composer.lock.bak');

    if (File::exists($composerJsonBackup)) {
        File::delete($composerJsonBackup);
    }
    if (File::exists($composerLockBackup)) {
        File::delete($composerLockBackup);
    }

    $this->plugins->cleanup();

    expect(true)->toBeTrue();
});

test('execute install plugin scripts runs post install scripts', function () {
    Process::fake([
        'echo "Post install script"' => Process::result(output: 'Script executed'),
    ]);

    $composerJson = [
        'name' => 'test-vendor/test-plugin',
        'scripts' => [
            'post-package-install' => ['echo "Post install script"'],
        ],
    ];

    $reflection = new ReflectionClass($this->plugins);
    $method = $reflection->getMethod('executeInstallPluginScripts');

    $result = $method->invoke($this->plugins, $composerJson);

    $this->assertStringContainsString('Script executed', $result);
});

test('execute install plugin scripts skips if already installed', function () {
    $flagFile = storage_path('plugins/.installed/test-vendor/test-plugin');
    File::makeDirectory(dirname($flagFile), 0755, true);
    File::put($flagFile, now()->toISOString());

    $composerJson = [
        'name' => 'test-vendor/test-plugin',
        'scripts' => [
            'post-package-install' => ['echo "Post install script"'],
        ],
    ];

    $reflection = new ReflectionClass($this->plugins);
    $method = $reflection->getMethod('executeInstallPluginScripts');

    $result = $method->invoke($this->plugins, $composerJson);

    expect($result)->toBeEmpty();

    File::delete($flagFile);
});

test('execute composer scripts handles script failures', function () {
    Process::fake([
        'failing-command' => Process::result(exitCode: 1, errorOutput: 'Script failed'),
    ]);

    $composerJson = [
        'scripts' => [
            'post-package-install' => ['failing-command'],
        ],
    ];

    $reflection = new ReflectionClass($this->plugins);
    $method = $reflection->getMethod('executeComposerScripts');

    $result = $method->invoke($this->plugins, $composerJson, 'post-package-install');

    $this->assertStringContainsString('Warning: Plugin script failed: Script failed', $result);
});
