<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;
use RuntimeException;

class RegisterServerFeatureAction
{
    public function __construct(
        public string $feature,
        public string $name,
        public string $label = '',
        public string $handler = '',
        public ?DynamicForm $form = null,
    ) {}

    public static function make(string $feature, string $name): self
    {
        return new self($feature, $name);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function handler(string $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function form(DynamicForm $form): self
    {
        $this->form = $form;

        return $this;
    }

    public function register(): void
    {
        $feature = config('server.features.'.$this->feature);

        if (! $feature) {
            throw new RuntimeException("Feature '{$this->feature}' not found");
        }

        $actions = $feature['actions'] ?? [];
        if (isset($actions[$this->name])) {
            throw new RuntimeException("Action '{$this->name}' already exists for feature '{$this->feature}'");
        }

        $actions[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
        ];

        config(['server.features.'.$this->feature.'.actions' => $actions]);
    }
}
