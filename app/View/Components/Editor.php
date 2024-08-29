<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Editor extends Component
{
    public string $id;

    public string $name;

    public ?string $value;

    public array $options;

    public function __construct(
        string $name,
        ?string $value,
        public string $lang,
        public bool $readonly = false,
        public bool $lineNumbers = true,
    ) {
        $this->id = $name.'-'.Str::random(8);
        $this->name = $name;
        $this->value = json_encode($value ?? '');
        $this->options = $this->getOptions();
    }

    private function getOptions(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lang' => $this->lang,
            'value' => $this->value,
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.editor');
    }
}
