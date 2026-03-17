<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;

class RegisterApplicationType
{
    public function __construct(
        public string $name,
        public string $label = '',
        public string $handler = '',
        public ?DynamicForm $form = null,
        public ?DynamicForm $editForm = null,
        public array $settings = [],
        public ?string $deployAction = null,
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

    public function editForm(DynamicForm $editForm): self
    {
        $this->editForm = $editForm;

        return $this;
    }

    /**
     * @param  array<\App\DTOs\SettingsField>  $fields
     */
    public function settingsFields(array $fields): self
    {
        $this->settings = $fields;

        return $this;
    }

    public function deployAction(string $deployAction): self
    {
        $this->deployAction = $deployAction;

        return $this;
    }

    public function register(): void
    {
        $types = config('application.types');

        $types[$this->name] = [
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
            'edit_form' => $this->editForm ? $this->editForm->toArray() : [],
            'settings_fields' => array_map(fn ($f) => $f->toArray(), $this->settings),
            'deploy_action' => $this->deployAction,
        ];

        config(['application.types' => $types]);
    }
}
