<?php

namespace App\Plugins;

use RuntimeException;

class RegisterSiteFeature
{
    public function __construct(
        public string $type,
        public string $name,
        public string $label = '',
        public string $description = ''
    ) {}

    public static function make(string $type, string $name): self
    {
        return new self($type, $name);
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
        if (! config()->has('site.types.'.$this->type)) {
            throw new RuntimeException('Site types not found');
        }

        $features = config('site.types.'.$this->type.'.features') ?? [];

        if (isset($features[$this->name])) {
            throw new RuntimeException("Feature '{$this->name}' already exists for type '{$this->type}'");
        }

        $features[$this->name] = [
            'label' => $this->label,
            'description' => $this->description,
        ];

        config(['site.types.'.$this->type.'.features' => $features]);
    }
}
