<?php

namespace App\Plugins\Interfaces;

interface PluginInterface
{
    public function boot(): void;

    public function enable(): void;

    public function disable(): void;

    public function install(): void;

    public function uninstall(): void;

    public function getName(): string;

    public function getDescription(): string;
}
