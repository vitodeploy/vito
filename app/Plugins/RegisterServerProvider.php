<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;
use App\Enums\OperatingSystem;

class RegisterServerProvider
{
    /**
     * @param  array<int, string>|null  $operatingSystems
     */
    public function __construct(
        private string $name,
        private string $label = '',
        private string $handler = '',
        private ?DynamicForm $form = null,
        private string $defaultUser = '',
        private ?DynamicForm $createForm = null,
        private ?int $provisionTimeout = null,
        private ?DynamicForm $editForm = null,
        private ?string $debianDefaultUser = null,
        private ?array $operatingSystems = null,
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

    /**
     * Credential fields users can change after connecting, rendered in the
     * connection's edit form. Never include secrets here.
     */
    public function editForm(DynamicForm $editForm): self
    {
        $this->editForm = $editForm;

        return $this;
    }

    public function defaultUser(string $defaultUser, ?string $debian = null): self
    {
        $this->defaultUser = $defaultUser;
        $this->debianDefaultUser = $debian;

        return $this;
    }

    /**
     * Operating systems this provider can create, as OperatingSystem values.
     * Providers that don't declare any are offered the Ubuntu releases only.
     *
     * @param  array<int, string>  $operatingSystems
     */
    public function operatingSystems(array $operatingSystems): self
    {
        $this->operatingSystems = $operatingSystems;

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
            'debian_default_user' => $this->debianDefaultUser ?? $this->defaultUser,
            'operating_systems' => $this->operatingSystems ?? array_values(array_filter(
                config('core.operating_systems'),
                fn (string $os): bool => ! OperatingSystem::from($os)->isDebian(),
            )),
            'create_form' => $this->createForm ? $this->createForm->toArray() : [],
            'edit_form' => $this->editForm ? $this->editForm->toArray() : [],
            'provision_timeout' => $this->provisionTimeout,
        ];

        config(['server-provider.providers' => $providers]);
    }
}
