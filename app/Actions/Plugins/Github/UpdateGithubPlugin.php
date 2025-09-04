<?php

namespace App\Actions\Plugins\Github;

use App\Actions\Plugins\InstallPlugin;
use App\Models\Plugin;
use Exception;
use File;

final readonly class UpdateGithubPlugin
{
    public function __construct(
        private InstallGithubPlugin $installPlugin,
    ) {}

    /**
     * @throws Exception
     */
    public function handle(Plugin $plugin): void
    {
        $this->installPlugin->handle($plugin->repo, $plugin);
    }
}
