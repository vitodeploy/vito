<?php

namespace App\Plugins;

use App\DTOs\DynamicForm;
use App\RuntimePresets\AbstractRuntimePreset;
use InvalidArgumentException;
use ReflectionClass;

class RegisterRuntimePreset
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
     * @param  class-string<AbstractRuntimePreset>  $handler
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
        if (! is_subclass_of($this->handler, AbstractRuntimePreset::class) || ! (new ReflectionClass($this->handler))->isInstantiable()) {
            throw new InvalidArgumentException('Runtime presets must be instantiable and extend AbstractRuntimePreset.');
        }

        $preset = new $this->handler;
        $runtimes = config('component.runtimes', []);
        $runtimes[$this->name] = [
            'label' => $this->label !== '' ? $this->label : $preset::label(),
            'handler' => $this->handler,
            'form' => ($this->form ?? $preset->form())->toArray(),
        ];

        config(['component.runtimes' => $runtimes]);
    }
}
