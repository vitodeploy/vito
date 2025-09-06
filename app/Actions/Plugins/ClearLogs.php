<?php

namespace App\Actions\Plugins;

use App\Models\Plugin;
use App\Models\PluginError;

final readonly class ClearLogs
{
    public function __construct() {}

    public function handle(Plugin $plugin): void
    {
        PluginError::where('plugin_id', $plugin->id)->delete();
    }
}
