<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;
use InvalidArgumentException;

class RegisterServiceType
{
    /**
     * @param  array<string>  $versions
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private string $name,
        private string $type = '',
        private string $unit = '',
        private string $label = '',
        private string $handler = '',
        private ?DynamicForm $form = null,
        private array $versions = ['latest'],
        private array $data = []
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

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function unit(string $unit): self
    {
        $this->unit = $unit;

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
     * @param  array<string>  $versions
     */
    public function versions(array $versions): self
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function register(): void
    {
        $types = config('service.services');

        if (! $this->type) {
            throw new InvalidArgumentException('Service type must be specified.');
        }

        $types[$this->name] = [
            'type' => $this->type,
            'unit' => $this->unit,
            'label' => $this->label,
            'handler' => $this->handler,
            'form' => $this->form ? $this->form->toArray() : [],
            'versions' => $this->versions,
            'data' => $this->data,
        ];

        config(['service.services' => $types]);
    }
}
