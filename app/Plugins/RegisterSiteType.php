<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;

class RegisterSiteType
{
    public function __construct(
        public string $name,
        public string $label = '',
        public string $handler = '',
        public ?DynamicForm $form = null,
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
        $types = config('site.types');

        $types[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
        ];

        config(['site.types' => $types]);
    }
}
