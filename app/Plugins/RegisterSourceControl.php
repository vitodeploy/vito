<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;
use App\SourceControlProviders\SourceControlProvider;

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

        $editableFields = class_exists($this->handler)
            && is_a($this->handler, SourceControlProvider::class, true)
                ? $this->handler::editableFields()
                : [];

        $providers[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
            'connectable' => $this->connectable,
            'usable_for_sites' => $this->usableForSites,
            'editable_fields' => $editableFields,
        ];

        config(['source-control.providers' => $providers]);
    }
}
