<?php

namespace App\DTOs;

class DynamicField
{
    /**
     * @param  array<int, mixed>|null  $options
     */
    public function __construct(
        private string $name,
        private string $type = 'text',
        private string $label = '',
        private mixed $default = null,
        private ?string $placeholder = null,
        private ?string $description = null,
        private ?array $options = null
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function component(): self
    {
        $this->type = 'component';

        return $this;
    }

    public function text(): self
    {
        $this->type = 'text';

        return $this;
    }

    public function select(): self
    {
        $this->type = 'select';

        return $this;
    }

    public function checkbox(): self
    {
        $this->type = 'checkbox';

        return $this;
    }

    public function alert(): self
    {
        $this->type = 'alert';

        return $this;
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

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function placeholder(?string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param  array<int, mixed>|null  $options
     */
    public function options(?array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'label' => $this->label,
            'default' => $this->default,
            'placeholder' => $this->placeholder,
            'description' => $this->description,
            'options' => $this->options,
        ];
    }
}
