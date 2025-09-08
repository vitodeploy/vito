<?php

namespace App\Plugins;

use RuntimeException;

class RegisterServerFeature
{
    public function __construct(
        public string $name,
        public string $label = '',
        public string $description = ''
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function register(): void
    {
        $features = config('server.features') ?? [];

        if (isset($features[$this->name])) {
            throw new RuntimeException("Feature '{$this->name}' already exists");
        }

        $features[$this->name] = [
            'label' => $this->label,
            'description' => $this->description,
        ];

        config(['server.features' => $features]);
    }
}
