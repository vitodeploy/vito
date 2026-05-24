<?php

namespace App\DTOs;

use App\Tooling\ToolingInterface;

class DynamicField
{
    public function __construct(
        private string $name,
        private string $type = 'text',
        private string $label = '',
        private mixed $default = null,
        private ?string $placeholder = null,
        private ?string $description = null,
        private ?array $options = null,
        private ?array $optionLabels = null,
        private ?array $link = null,
        private ?string $className = null,
        private ?array $componentProps = null,
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

    public function password(): self
    {
        $this->type = 'password';

        return $this;
    }

    public function passwordWithToggle(): self
    {
        $this->type = 'password-with-toggle';

        return $this;
    }

    public function textarea(): self
    {
        $this->type = 'textarea';

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

    public function tooling(): self
    {
        $this->type = 'tooling';

        return $this;
    }

    /**
     * @param  class-string<ToolingInterface>  $toolClass
     */
    public function toolingPicker(string $toolClass): self
    {
        $this->type = 'tooling-picker';
        $this->options = [$toolClass::id()];

        if ($this->label === '') {
            $this->label = $toolClass::label().' Version';
        }

        $versions = $toolClass::supportedVersions();
        if ($versions !== []) {
            $this->default = $versions[0];
        }

        return $this;
    }

    /**
     * @param  array<int, class-string<ToolingInterface>>  $toolClasses
     * @param  array<class-string<ToolingInterface>, string>  $labelOverrides
     */
    public function toolingSelector(array $toolClasses, array $labelOverrides = []): self
    {
        $this->type = 'tooling-selector';
        $this->options = array_map(fn (string $cls) => $cls::id(), $toolClasses);

        if ($labelOverrides !== []) {
            $this->optionLabels = [];
            foreach ($labelOverrides as $cls => $label) {
                $this->optionLabels[$cls::id()] = $label;
            }
        }

        if ($this->default === null && $this->options !== []) {
            $this->default = $this->options[0];
        }

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

    public function options(?array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function link(string $label, string $url): self
    {
        $this->link = [
            'label' => $label,
            'url' => $url,
        ];

        return $this;
    }

    public function className(?string $className): self
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function componentProps(array $props): self
    {
        $this->componentProps = $props;

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
            'optionLabels' => $this->optionLabels,
            'link' => $this->link,
            'className' => $this->className,
            'componentProps' => $this->componentProps,
        ];
    }
}
