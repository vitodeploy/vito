<?php

namespace App\Web\Components;

use Filament\Pages\Page as BasePage;
use Illuminate\View\ComponentAttributeBag;

abstract class Page extends BasePage
{
    protected static string $view = 'components.page';

    protected ?string $live = '5s';

    /**
     * @var array<string, mixed>
     */
    protected array $extraAttributes = [];

    /**
     * @return array<string, mixed>
     */
    protected function getExtraAttributes(): array
    {
        $attributes = $this->extraAttributes;

        if (! in_array($this->getLive(), [null, '', '0'], true)) {
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

    /**
     * @return array<int, mixed>
     */
    public function getWidgets(): array
    {
        return [];
    }
}
