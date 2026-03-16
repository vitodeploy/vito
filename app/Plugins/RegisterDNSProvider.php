<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;

class RegisterDNSProvider
{
    public function __construct(
        private string $name,
        private string $label = '',
        private string $handler = '',
        private ?DynamicForm $form = null,
        private ?DynamicForm $editForm = null,
        private array $proxyTypes = [],
        private bool $supportsCreatedAt = true,
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

    public function editForm(DynamicForm $editForm): self
    {
        $this->editForm = $editForm;

        return $this;
    }

    public function proxyTypes(array $proxyTypes): self
    {
        $this->proxyTypes = $proxyTypes;

        return $this;
    }

    public function supportsCreatedAt(bool $supportsCreatedAt): self
    {
        $this->supportsCreatedAt = $supportsCreatedAt;

        return $this;
    }

    public function register(): void
    {
        $providers = config('dns-provider.providers');

        $providers[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
            'edit_form' => $this->editForm ? $this->editForm->toArray() : [],
            'proxy_types' => $this->proxyTypes,
            'supports_created_at' => $this->supportsCreatedAt,
        ];

        config(['dns-provider.providers' => $providers]);
    }
}
