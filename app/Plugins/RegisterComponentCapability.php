<?php

namespace App\Plugins;

use App\ComponentCapabilities\AbstractComponentCapability;
use App\DTOs\DynamicForm;
use InvalidArgumentException;
use ReflectionClass;

class RegisterComponentCapability
{
    public function __construct(
        public string $name,
        public string $label = '',
        public string $handler = '',
        public ?DynamicForm $form = null,
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

    /**
     * @param  class-string<AbstractComponentCapability>  $handler
     */
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

    public function register(): void
    {
        if (! is_subclass_of($this->handler, AbstractComponentCapability::class) || ! (new ReflectionClass($this->handler))->isInstantiable()) {
            throw new InvalidArgumentException('Component capabilities must be instantiable and extend AbstractComponentCapability.');
        }

        $capabilities = config('component.capabilities', []);
        $capabilities[$this->name] = [
            'label' => $this->label !== '' ? $this->label : $this->handler::label(),
            'handler' => $this->handler,
            'form' => $this->form?->toArray(),
        ];

        config(['component.capabilities' => $capabilities]);
    }
}
