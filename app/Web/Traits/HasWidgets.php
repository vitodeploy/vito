<?php

namespace App\Web\Traits;

use Illuminate\View\ComponentAttributeBag;

trait HasWidgets
{
    protected ?string $live = '5s';

    protected array $extraAttributes = [];

    protected function getExtraAttributes(): array
    {
        $attributes = $this->extraAttributes;

        if ($this->getLive()) {
            $attributes['wire:poll.'.$this->getLive()] = '$dispatch(\'$refresh\')';
        }

        return $attributes;
    }

    public function getExtraAttributesBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraAttributes());
    }

    public function getLive(): ?string
    {
        return $this->live;
    }

    public function getWidgets(): array
    {
        return [];
    }
}
