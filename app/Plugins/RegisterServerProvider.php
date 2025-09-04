<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;

class RegisterServerProvider
{
    public function __construct(
        private string $name,
        private string $label = '',
        private string $handler = '',
        private ?DynamicForm $form = null,
        private string $defaultUser = '',
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

    public function defaultUser(string $defaultUser): self
    {
        $this->defaultUser = $defaultUser;

        return $this;
    }

    public function register(): void
    {
        $providers = config('server-provider.providers');

        $providers[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
            'default_user' => $this->defaultUser,
        ];

        config(['server-provider.providers' => $providers]);
    }
}
