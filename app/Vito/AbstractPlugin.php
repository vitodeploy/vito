<?php

namespace App\Vito;

use App\Vito\Interfaces\PluginInterface;

abstract class AbstractPlugin implements PluginInterface
{
    protected string $name = '';

    protected string $description = '';

    public function boot(): void {}

    public function register(): void {}

    public function enable(): void {}

    public function disable(): void {}

    public function install(): void {}

    public function uninstall(): void {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
