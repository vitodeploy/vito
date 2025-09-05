<?php

namespace App\Plugins;

class RegisterViews
{
    private const string CONFIG_KEY = 'plugins.views';

    public function __construct(
        private readonly string $name,
        private string $path = '',
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function register(): void
    {
        if (empty($this->name) || empty($this->path)) {
            return;
        }

        $views = self::get();
        $views[$this->name] = $this->path;

        config([self::CONFIG_KEY => $views]);
    }

    public static function get(): array
    {
        return config(self::CONFIG_KEY) ?? [];
    }
}
