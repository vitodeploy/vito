<?php

namespace App\Plugins;

class RegisterBind
{
    private const string CONFIG_KEY = 'plugins.binds';

    public function __construct(
        private readonly string $abstract,
        private string $concrete = '',
    ) {}

    public static function make(string $abstract): self
    {
        return new self($abstract);
    }

    public function to(string $concrete): self
    {
        $this->concrete = $concrete;

        return $this;
    }

    public function register(): void
    {
        if (empty($this->abstract) || empty($this->concrete)) {
            return;
        }

        $views = self::get();
        $views[$this->abstract] = $this->concrete;

        config([self::CONFIG_KEY => $views]);
    }

    public static function get(): array
    {
        return config(self::CONFIG_KEY) ?? [];
    }
}
