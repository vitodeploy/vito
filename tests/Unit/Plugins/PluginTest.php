<?php

use App\Actions\Plugins\BootPlugins;
use App\Actions\Plugins\DisablePlugin;
use App\Actions\Plugins\DiscoverPlugins;
use App\Actions\Plugins\EnablePlugin;
use App\Actions\Plugins\GetPluginInstance;
use App\Actions\Plugins\Github\DownloadRelease;
use App\Actions\Plugins\Github\GetReleaseInfo;
use App\Actions\Plugins\Github\InstallGithubPlugin;
use App\Actions\Plugins\InstallPlugin;
use App\Actions\Plugins\UninstallPlugin;
use App\DTOs\GitHub\AuthorDto;
use App\DTOs\GitHub\ReleaseDto;
use App\Models\Plugin;
use App\Models\PluginError;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repoUrl = 'https://github.com/RichardAnderson/VitoOctanePlugin';
    $this->pluginPath = app_path('Vito/Plugins');
    $this->backupPath = storage_path('plugins_backup_'.time());

    vitoPestUnitPluginsPluginTestMovePlugins($this->pluginPath, $this->backupPath);
    File::makeDirectory($this->pluginPath, 0755, true);

    Plugin::truncate();
    PluginError::truncate();

    app(GetPluginInstance::class)->clear();
});

afterEach(function () {
    vitoPestUnitPluginsPluginTestMovePlugins($this->backupPath, $this->pluginPath);
});

function vitoPestUnitPluginsPluginTestInstallExamplePlugin(): Plugin
{
    $fromFile = implode(
        DIRECTORY_SEPARATOR,
        [__DIR__, 'Example', 'Repo', 'Plugin.php'],
    );

    $toFile = implode(
        DIRECTORY_SEPARATOR,
        [test()->pluginPath, 'Example', 'Repo', 'Plugin.php']
    );

    File::ensureDirectoryExists(dirname($toFile));
    if (! File::copy($fromFile, $toFile)) {
        test()->fail("Failed to copy example plugin from '$fromFile' to '$toFile'");
    }

    $folder = implode(DIRECTORY_SEPARATOR, ['Example', 'Repo']);

    $discovery = app(DiscoverPlugins::class);
    $discovery->handle();

    return Plugin::where('folder', $folder)->first();
}

function vitoPestUnitPluginsPluginTestMovePlugins(string $from, string $to): void
{
    File::deleteDirectory($to);
    File::makeDirectory(path: $to, recursive: true, force: true);
    File::moveDirectory($from, $to, true);
}

function vitoPestUnitPluginsPluginTestCreateTestReleaseDto(string $tagName = '1.0.2', string $repoName = 'repo'): ReleaseDto
{
    return new ReleaseDto(
        url: "https://api.github.com/repos/username/{$repoName}/releases/123456",
        tagName: $tagName,
        name: "Release {$tagName}",
        draft: false,
        preRelease: false,
        createdAt: Carbon::now(),
        updatedAt: Carbon::now(),
        publishedAt: Carbon::now(),
        author: new AuthorDto('username', 'https://api.github.com/username', 'individual'),
        tarUrl: "https://api.github.com/repos/username/{$repoName}/tarball/{$tagName}",
        zipUrl: "https://github.com/username/{$repoName}/archive/{$tagName}.zip",
        body: "Release notes for version {$tagName}"
    );
}

function vitoPestUnitPluginsPluginTestInstallDemoPlugin(): Plugin
{
    $zip = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Artifacts', 'VitoOctanePlugin-1.0.2.zip']);

    app()->bind(DownloadRelease::class, function () use ($zip) {
        $mock = Mockery::mock(DownloadRelease::class);
        $mock->shouldReceive('handle')
            ->andReturnUsing(function ($release, $location) use ($zip) {
                File::ensureDirectoryExists(dirname($location));
                if (! File::copy($zip, $location)) {
                    throw new Exception("Unable to copy file from $zip to $location");
                }
            });

        return $mock;
    });

    app()->bind(GetReleaseInfo::class, function () {
        $mock = Mockery::mock(GetReleaseInfo::class);
        $mock->shouldReceive('handle')
            ->andReturn(vitoPestUnitPluginsPluginTestCreateTestReleaseDto());

        return $mock;
    });

    $action = app(InstallGithubPlugin::class);

    return $action->handle(test()->repoUrl);
}

function vitoPestUnitPluginsPluginTestGetPluginPath(Plugin $plugin): string
{
    return implode(DIRECTORY_SEPARATOR, [test()->pluginPath, $plugin->folder]);
}

function vitoPestUnitPluginsPluginTestCreateFakePlugin(): Plugin
{
    $folder = implode(DIRECTORY_SEPARATOR, ['ExampleUser', 'ExampleRepo']);
    $path = implode(DIRECTORY_SEPARATOR, [test()->pluginPath, $folder]);
    File::makeDirectory($path, 0755, true);

    $discovery = app(DiscoverPlugins::class);
    $discovery->handle();

    return Plugin::where('folder', $folder)->first();
}

test('can install plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();
    $path = vitoPestUnitPluginsPluginTestGetPluginPath($plugin);

    expect(File::isDirectory($path))->toMatchConstraint($this->isTrue());
    expect(File::isEmptyDirectory($path))->toMatchConstraint($this->isFalse());
    expect($plugin->is_installed)->toMatchConstraint($this->isTrue());
});

test('can enable plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();

    $action = app(EnablePlugin::class);
    $action->handle($plugin);

    $plugin->refresh();
    expect($plugin->is_enabled)->toMatchConstraint($this->isTrue());
});

test('can disable plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();

    $plugin->is_enabled = true;
    $plugin->save();

    $disable = app(DisablePlugin::class);
    $disable->handle($plugin);

    $plugin->refresh();
    expect($plugin->is_enabled)->toMatchConstraint($this->isFalse());
});

test('can discovery plugins', function () {
    $plugin = vitoPestUnitPluginsPluginTestCreateFakePlugin();

    expect($plugin)->not->toBeNull();
    expect($plugin->namespace)->toMatchConstraint($this->equalTo('App\\Vito\\Plugins\\ExampleUser\\ExampleRepo\\Plugin'));
    expect($plugin->is_installed)->toMatchConstraint($this->isFalse());
    expect($plugin->is_enabled)->toMatchConstraint($this->isFalse());
});

test('install invalid plugin raises error', function () {
    $plugin = vitoPestUnitPluginsPluginTestCreateFakePlugin();

    $install = app(InstallPlugin::class);
    $this->assertThrows(fn () => $install->handle($plugin));

    $plugin->refresh();
    $errors = PluginError::where('plugin_id', $plugin->id)->get();

    expect($plugin->is_installed)->toMatchConstraint($this->isFalse());
    expect($errors)->toHaveCount(1);
});

test('can remove local plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestCreateFakePlugin();
    $folder = $plugin->folder;
    $path = vitoPestUnitPluginsPluginTestGetPluginPath($plugin);

    $uninstall = app(UninstallPlugin::class);
    $uninstall->handle($plugin);

    $plugin = Plugin::where('folder', $folder)->first();
    expect($plugin)->toMatchConstraint($this->isNull());
    expect(File::isDirectory($path))->toMatchConstraint($this->IsFalse());
});

test('can uninstall plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();
    $path = vitoPestUnitPluginsPluginTestGetPluginPath($plugin);

    $plugin->is_enabled = false;
    $plugin->save();

    $folder = $plugin->folder;

    $uninstall = app(UninstallPlugin::class);
    $uninstall->handle($plugin);

    $plugin = Plugin::where('folder', $folder)->first();
    expect($plugin)->toMatchConstraint($this->isNull());
    expect(File::isDirectory($path))->toMatchConstraint($this->IsFalse());
});

test('cannot uninstall enabled plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();
    $path = vitoPestUnitPluginsPluginTestGetPluginPath($plugin);

    $plugin->is_enabled = true;
    $plugin->save();

    $uninstall = app(UninstallPlugin::class);

    $this->assertThrows(fn () => $uninstall->handle($plugin));

    $plugin->refresh();
    expect($plugin->is_enabled)->toMatchConstraint($this->isTrue());
    expect($plugin->is_installed)->toMatchConstraint($this->isTrue());
    expect(File::isDirectory($path))->toMatchConstraint($this->isTrue());
});

test('cannot enable enabled plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();

    $plugin->is_enabled = true;
    $plugin->save();

    $enable = app(EnablePlugin::class);

    $this->assertThrows(fn () => $enable->handle($plugin));

    $plugin->refresh();
    expect($plugin->is_enabled)->toMatchConstraint($this->isTrue());
    expect($plugin->is_installed)->toMatchConstraint($this->isTrue());
});

test('cannot disable disabled plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallDemoPlugin();

    $plugin->is_enabled = false;
    $plugin->save();

    $disable = app(DisablePlugin::class);

    $this->assertThrows(fn () => $disable->handle($plugin));

    $plugin->refresh();
    expect($plugin->is_enabled)->toMatchConstraint($this->isFalse());
    expect($plugin->is_installed)->toMatchConstraint($this->isTrue());
});

test('install method called', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallExamplePlugin();

    $installer = app(InstallPlugin::class);
    $installer->handle($plugin);

    $implementation = app(GetPluginInstance::class)->handle($plugin);
    $methods = $implementation->getMethods();

    expect($methods)->toMatchConstraint($this->arrayHasKey('install'));
    expect($methods)->toHaveCount(1);
    expect($methods['install'])->toMatchConstraint($this->equalTo(1));
});

test('enable method called', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallExamplePlugin();
    $plugin->is_installed = true;
    $plugin->save();

    $action = app(EnablePlugin::class);
    $action->handle($plugin);

    $implementation = app(GetPluginInstance::class)->handle($plugin);
    $methods = $implementation->getMethods();

    expect($methods)->toMatchConstraint($this->arrayHasKey('enable'));
    expect($methods)->toHaveCount(1);
    expect($methods['enable'])->toMatchConstraint($this->equalTo(1));
});

test('disable method called', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallExamplePlugin();
    $plugin->is_installed = true;
    $plugin->is_enabled = true;
    $plugin->save();

    $action = app(DisablePlugin::class);
    $action->handle($plugin);

    $implementation = app(GetPluginInstance::class)->handle($plugin);
    $methods = $implementation->getMethods();

    expect($methods)->toMatchConstraint($this->arrayHasKey('disable'));
    expect($methods)->toHaveCount(1);
    expect($methods['disable'])->toMatchConstraint($this->equalTo(1));
});

test('boot method called for enabled plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallExamplePlugin();
    $plugin->is_installed = true;
    $plugin->is_enabled = true;
    $plugin->save();

    $action = app(BootPlugins::class);
    $action->handle();

    $implementation = app(GetPluginInstance::class)->handle($plugin);
    $methods = $implementation->getMethods();

    expect($methods)->toMatchConstraint($this->arrayHasKey('boot'));
    expect($methods)->toHaveCount(1);
    expect($methods['boot'])->toMatchConstraint($this->equalTo(1));
});

test('boot method not called for disabled plugin', function () {
    $plugin = vitoPestUnitPluginsPluginTestInstallExamplePlugin();
    $plugin->is_installed = true;
    $plugin->is_enabled = false;
    $plugin->save();

    $action = app(BootPlugins::class);
    $action->handle();

    $implementation = app(GetPluginInstance::class)->handle($plugin);
    $methods = $implementation->getMethods();

    expect($methods)->toHaveCount(0);
});
