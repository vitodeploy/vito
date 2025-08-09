<?php

namespace App\Plugins;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class Plugins
{
    /**
     * @return array<array<string, string>>
     *
     * @throws FileNotFoundException
     */
    public function all(): array
    {
        $plugins = [];

        foreach (File::directories(plugins_path()) as $vendorDir) {
            foreach (File::directories($vendorDir) as $pluginDir) {
                $pluginComposer = $pluginDir.'/composer.json';
                if (File::exists($pluginComposer)) {
                    $json = json_decode(File::get($pluginComposer), true);
                    $plugins[] = [
                        'name' => $json['name'] ?? 'Unknown',
                        'version' => $json['version'] ?? 'Unknown',
                    ];
                }
            }
        }

        return $plugins;
    }

    /**
     * @throws Exception
     */
    public function install(string $url, ?string $branch = null, ?string $tag = null): string
    {
        $vendor = str($url)->beforeLast('/')->afterLast('/');
        $name = str($url)->afterLast('/');

        if (is_dir(storage_path("plugins/$vendor/$name"))) {
            File::deleteDirectory(storage_path("plugins/$vendor/$name"));
        }

        $command = "git clone $url ".storage_path("plugins/$vendor/$name");
        if ($branch) {
            $command .= " --branch $branch";
        }
        if ($tag) {
            $command .= " --tag $tag";
        }
        $command .= ' --single-branch';
        $result = Process::env(['PATH' => dirname(git_path())])->timeout(0)->run($command);
        $output = $result->output();

        if ($result->failed()) {
            throw new Exception($output);
        }

        $output .= $this->load();

        return $output;
    }

    /**
     * @throws Exception
     */
    public function load(): string
    {
        $storagePath = storage_path('plugins');
        $composerJson = base_path('composer.json');
        $composerLock = base_path('composer.lock');

        // Backup composer files
        File::copy($composerJson, $composerJson.'.bak');
        File::copy($composerLock, $composerLock.'.bak');

        $output = '';

        foreach (File::directories($storagePath) as $vendorDir) {
            foreach (File::directories($vendorDir) as $pluginDir) {
                $pluginComposer = $pluginDir.'/composer.json';
                if (File::exists($pluginComposer)) {
                    $json = json_decode(File::get($pluginComposer), true);
                    if (isset($json['name'])) {
                        $name = $json['name'];
                        // name must be in vendor/plugin format
                        if (! str_contains($name, '/')) {
                            continue;
                        }
                        $phpPath = php_path();
                        $composerPath = composer_path();
                        $result = Process::timeout(0)
                            ->env(['PATH' => dirname($phpPath).':'.dirname($composerPath)])
                            ->path(base_path())
                            ->run(composer_path()." require $name");
                        if ($result->failed()) {
                            throw new Exception($result->errorOutput());
                        }

                        $output .= $result->output();
                    }
                }
            }
        }

        return $output;
    }

    /**
     * @throws Exception
     */
    public function uninstall(string $name): string
    {
        $pluginPath = storage_path('plugins/'.$name);

        if (! File::exists($pluginPath)) {
            throw new Exception("Plugin not found: $name");
        }

        $result = Process::timeout(0)
            ->path(base_path())
            ->run("composer remove $name");

        if ($result->failed()) {
            throw new Exception($result->output());
        }

        File::deleteDirectory($pluginPath);

        return $result->output();
    }

    public function cleanup(): void
    {
        $composerJson = base_path('composer.json');
        $composerLock = base_path('composer.lock');

        if (File::exists($composerJson.'.bak')) {
            File::move($composerJson.'.bak', $composerJson);
        }

        if (File::exists($composerLock.'.bak')) {
            File::move($composerLock.'.bak', $composerLock);
        }
    }
}
