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
        private ?DynamicForm $createForm = null,
        private ?int $provisionTimeout = null,
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

    /**
     * Extra provider-specific fields rendered on the create-server form.
     */
    public function createForm(DynamicForm $createForm): self
    {
        $this->createForm = $createForm;

        return $this;
    }

    public function defaultUser(string $defaultUser): self
    {
        $this->defaultUser = $defaultUser;

        return $this;
    }

    /**
     * How long the install waits for a new server to boot and accept SSH, for
     * providers that provision slower than the default.
     */
    public function provisionTimeout(int $seconds): self
    {
        $this->provisionTimeout = $seconds;

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
            'create_form' => $this->createForm ? $this->createForm->toArray() : [],
            'provision_timeout' => $this->provisionTimeout,
        ];

        config(['server-provider.providers' => $providers]);
    }
}
