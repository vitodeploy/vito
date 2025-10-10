<?php

namespace App\Plugins;

class RegisterWorkflowAction
{
    public function __construct(
        public string $name,
        public string $label = '',
        public string $description = '',
        public string $category = '',
        public string $handler = '',
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

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function handler(string $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function register(): void
    {
        $actions = config('workflow.actions');

        $actions[$this->name] = [
            'label' => $this->label,
            'description' => $this->description,
            'category' => $this->category,
            'handler' => $this->handler,
        ];

        config(['workflow.actions' => $actions]);
    }
}
