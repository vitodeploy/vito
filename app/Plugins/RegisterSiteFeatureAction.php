<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;
use RuntimeException;

class RegisterSiteFeatureAction
{
    public function __construct(
        public string $type,
        public string $feature,
        public string $name,
        public string $label = '',
        public string $handler = '',
        public ?DynamicForm $form = null,
    ) {}

    public static function make(string $type, string $feature, string $name): self
    {
        return new self($type, $feature, $name);
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
        if (! config()->has('site.types.'.$this->type)) {
            throw new RuntimeException('Site types not found');
        }

        $feature = config('site.types.'.$this->type.'.features.'.$this->feature);

        if (! $feature) {
            throw new RuntimeException("Feature '{$this->feature}' not found for type '{$this->type}'");
        }

        $actions = $feature['actions'] ?? [];
        if (isset($actions[$this->name])) {
            throw new RuntimeException("Action '{$this->name}' already exists for feature '{$this->feature}' in type '{$this->type}'");
        }

        $actions[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
        ];

        config(['site.types.'.$this->type.'.features.'.$this->feature.'.actions' => $actions]);
    }
}
