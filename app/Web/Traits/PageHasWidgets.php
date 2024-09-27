<?php

namespace App\Web\Traits;

use Illuminate\View\ComponentAttributeBag;

trait PageHasWidgets
{
    protected array $extraAttributes = [
        'wire:poll.5s' => '$dispatch(\'$refresh\')',
    ];

    protected function getExtraAttributes(): array
    {
        return $this->extraAttributes;
    }

    public function getView(): string
    {
        return 'web.components.page';
    }

    public function getExtraAttributesBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraAttributes());
    }

    abstract public function getWidgets(): array;
}
