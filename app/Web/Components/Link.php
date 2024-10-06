<?php

namespace App\Web\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Link extends Component implements Htmlable
{
    public function __construct(public string $href, public string $text, public bool $external = false) {}

    public function render(): View
    {
        return view('components.link');
    }

    public function toHtml(): View|string
    {
        return $this->render()->with([
            'href' => $this->href,
            'text' => $this->text,
            'external' => $this->external,
        ]);
    }
}
