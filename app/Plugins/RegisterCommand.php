<?php

namespace App\Plugins;

class RegisterCommand
{
    private const string CONFIG_KEY = 'plugins.commands';

    public function __construct(
        private readonly string $class,
    ) {}

    public static function make(string $class): self
    {
        return new self($class);
    }

    public function register(): void
    {
        $data = self::get();

        $data[] = $this->class;

        config([self::CONFIG_KEY => $data]);
    }

    public static function get(): array
    {
        return config(self::CONFIG_KEY) ?? [];
    }
}
