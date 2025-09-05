<?php

namespace Tests\Unit\Plugins;

use App\Actions\Plugins\DisablePlugin;
use App\Actions\Plugins\DiscoverPlugins;
use App\Actions\Plugins\EnablePlugin;
use App\Actions\Plugins\Github\InstallGithubPlugin;
use App\Actions\Plugins\InstallPlugin;
use App\Actions\Plugins\UninstallPlugin;
use App\Models\Plugin;
use App\Models\PluginError;
use File;
use Tests\TestCase;

class PluginTest extends TestCase
{
    private string $backupPath;

    private string $pluginPath;

    private string $repoUrl = 'https://github.com/RichardAnderson/VitoOctanePlugin';

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginPath = app_path('Vito/Plugins');
        $this->backupPath = storage_path('plugins_backup_'.time());

        $this->movePlugins($this->pluginPath, $this->backupPath);
        File::makeDirectory($this->pluginPath, 0755, true);

        Plugin::truncate();
        PluginError::truncate();
    }

    protected function tearDown(): void
    {
        $this->movePlugins($this->backupPath, $this->pluginPath);

        parent::tearDown();
    }

    private function movePlugins(string $from, string $to): void
    {
        File::deleteDirectory($to);
        File::makeDirectory(path: $to, recursive: true, force: true);
        File::moveDirectory($from, $to, true);
    }

    private function installExamplePlugin(): Plugin
    {
        $action = app(InstallGithubPlugin::class);

        return $action->handle($this->repoUrl);
    }

    private function getPluginPath(Plugin $plugin): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->pluginPath, $plugin->folder]);
    }

    private function createFakePlugin(): Plugin
    {
        $folder = implode(DIRECTORY_SEPARATOR, ['ExampleUser', 'ExampleRepo']);
        $path = implode(DIRECTORY_SEPARATOR, [$this->pluginPath, $folder]);
        File::makeDirectory($path, 0755, true);

        $discovery = app(DiscoverPlugins::class);
        $discovery->handle();

        return Plugin::where('folder', $folder)->first();
    }

    public function test_can_install_plugin(): void
    {
        $plugin = $this->installExamplePlugin();
        $path = $this->getPluginPath($plugin);

        $this->assertThat(File::isDirectory($path), $this->isTrue());
        $this->assertThat(File::isEmptyDirectory($path), $this->isFalse());
        $this->assertThat($plugin->is_installed, $this->isTrue());
    }

    public function test_can_enable_plugin(): void
    {
        $plugin = $this->installExamplePlugin();

        $action = app(EnablePlugin::class);
        $action->handle($plugin);

        $plugin->refresh();
        $this->assertThat($plugin->is_enabled, $this->isTrue());
    }

    public function test_can_disable_plugin(): void
    {
        $plugin = $this->installExamplePlugin();

        $plugin->is_enabled = true;
        $plugin->save();

        $disable = app(DisablePlugin::class);
        $disable->handle($plugin);

        $plugin->refresh();
        $this->assertThat($plugin->is_enabled, $this->isFalse());
    }

    public function test_can_discovery_plugins(): void
    {
        $plugin = $this->createFakePlugin();

        $this->assertNotNull($plugin);
        $this->assertThat($plugin->namespace, $this->equalTo('App\\Vito\\Plugins\\ExampleUser\\ExampleRepo\\Plugin'));
        $this->assertThat($plugin->is_installed, $this->isFalse());
        $this->assertThat($plugin->is_enabled, $this->isFalse());
    }

    public function test_install_invalid_plugin_raises_error(): void
    {
        $plugin = $this->createFakePlugin();

        $install = app(InstallPlugin::class);
        $this->assertThrows(fn () => $install->handle($plugin));

        $plugin->refresh();
        $errors = PluginError::where('plugin_id', $plugin->id)->get();

        $this->assertThat($plugin->is_installed, $this->isFalse());
        $this->assertCount(1, $errors);
    }

    public function test_can_remove_local_plugin(): void
    {
        $plugin = $this->createFakePlugin();
        $folder = $plugin->folder;
        $path = $this->getPluginPath($plugin);

        $uninstall = app(UninstallPlugin::class);
        $uninstall->handle($plugin);

        $plugin = Plugin::where('folder', $folder)->first();
        $this->assertThat($plugin, $this->isNull());
        $this->assertThat(File::isDirectory($path), $this->IsFalse());
    }

    public function test_can_uninstall_plugin(): void
    {
        $plugin = $this->installExamplePlugin();
        $path = $this->getPluginPath($plugin);

        $plugin->is_enabled = false;
        $plugin->save();

        $folder = $plugin->folder;

        $uninstall = app(UninstallPlugin::class);
        $uninstall->handle($plugin);

        $plugin = Plugin::where('folder', $folder)->first();
        $this->assertThat($plugin, $this->isNull());
        $this->assertThat(File::isDirectory($path), $this->IsFalse());
    }

    public function test_cannot_uninstall_enabled_plugin(): void
    {
        $plugin = $this->installExamplePlugin();
        $path = $this->getPluginPath($plugin);

        $plugin->is_enabled = true;
        $plugin->save();

        $uninstall = app(UninstallPlugin::class);

        $this->assertThrows(fn () => $uninstall->handle($plugin));

        $plugin->refresh();
        $this->assertThat($plugin->is_enabled, $this->isTrue());
        $this->assertThat($plugin->is_installed, $this->isTrue());
        $this->assertThat(File::isDirectory($path), $this->isTrue());
    }

    public function test_cannot_enable_enabled_plugin(): void
    {
        $plugin = $this->installExamplePlugin();

        $plugin->is_enabled = true;
        $plugin->save();

        $enable = app(EnablePlugin::class);

        $this->assertThrows(fn () => $enable->handle($plugin));

        $plugin->refresh();
        $this->assertThat($plugin->is_enabled, $this->isTrue());
        $this->assertThat($plugin->is_installed, $this->isTrue());
    }

    public function test_cannot_disable_disabled_plugin(): void
    {
        $plugin = $this->installExamplePlugin();
        $path = $this->getPluginPath($plugin);

        $plugin->is_enabled = false;
        $plugin->save();

        $disable = app(DisablePlugin::class);

        $this->assertThrows(fn () => $disable->handle($plugin));

        $plugin->refresh();
        $this->assertThat($plugin->is_enabled, $this->isFalse());
        $this->assertThat($plugin->is_installed, $this->isTrue());
    }
}
