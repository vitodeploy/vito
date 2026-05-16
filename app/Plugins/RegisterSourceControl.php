<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;

class RegisterSourceControl
{
    public function __construct(
        private string $name,
        private string $label = '',
        private string $handler = '',
        private ?DynamicForm $form = null,
        private bool $connectable = true,
        private bool $usableForSites = true,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
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

    public function connectable(bool $connectable): self
    {
        $this->connectable = $connectable;

        return $this;
    }

    public function usableForSites(bool $usableForSites): self
    {
        $this->usableForSites = $usableForSites;

        return $this;
    }

    public function register(): void
    {
        $providers = config('source-control.providers');

        $providers[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
            'connectable' => $this->connectable,
            'usable_for_sites' => $this->usableForSites,
        ];

        config(['source-control.providers' => $providers]);
    }
}
