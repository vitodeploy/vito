<?php

namespace App\DTOs;

class SettingsField
{
    public function __construct(
        private string $name,
        private string $fieldType = 'info',
        private string $label = '',
        private string $source = 'type_data',
        private ?string $key = null,
        private string $format = 'text',
        private ?string $actionLabel = null,
        private string $actionType = 'sidebar',
        private ?string $loadRoute = null,
        private ?string $submitRoute = null,
        private string $submitMethod = 'PUT',
        private ?string $resetRoute = null,
        private ?DynamicForm $form = null,
    ) {
        $this->key = $this->key ?? $this->name;
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /** Read value from application model fields (domain, aliases, force_ssl, etc.) */
    public function fromApplication(?string $key = null): self
    {
        $this->source = 'application';
        if ($key) {
            $this->key = $key;
        }

        return $this;
    }

    /** Read value from application.type_data (default) */
    public function fromTypeData(?string $key = null): self
    {
        $this->source = 'type_data';
        if ($key) {
            $this->key = $key;
        }

        return $this;
    }

    public function asLink(): self
    {
        $this->format = 'link';

        return $this;
    }

    public function asBadge(): self
    {
        $this->format = 'badge';

        return $this;
    }

    public function asBoolean(): self
    {
        $this->format = 'boolean';

        return $this;
    }

    /**
     * Make this field a sidebar action button.
     */
    public function asSidebarAction(string $actionLabel): self
    {
        $this->fieldType = 'sidebar-action';
        $this->actionLabel = $actionLabel;
        $this->actionType = 'sidebar';

        return $this;
    }

    public function loadRoute(string $route): self
    {
        $this->loadRoute = $route;

        return $this;
    }

    public function submitRoute(string $route): self
    {
        $this->submitRoute = $route;

        return $this;
    }

    public function submitMethod(string $method): self
    {
        $this->submitMethod = $method;

        return $this;
    }

    public function resetRoute(string $route): self
    {
        $this->resetRoute = $route;

        return $this;
    }

    public function form(DynamicForm $form): self
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'field_type' => $this->fieldType,
            'label' => $this->label,
        ];

        if ($this->fieldType === 'info') {
            $data['source'] = $this->source;
            $data['key'] = $this->key;
            $data['format'] = $this->format;
        }

        if ($this->fieldType === 'sidebar-action') {
            $data['action_label'] = $this->actionLabel;
            $data['action_type'] = $this->actionType;
            $data['load_route'] = $this->loadRoute;
            $data['submit_route'] = $this->submitRoute;
            $data['submit_method'] = $this->submitMethod;
            $data['reset_route'] = $this->resetRoute;
            $data['form'] = $this->form ? $this->form->toArray() : [];
        }

        return $data;
    }
}
