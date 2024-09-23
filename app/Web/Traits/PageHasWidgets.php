<?php

namespace App\Web\Traits;

trait PageHasWidgets
{
    public function getView(): string
    {
        return 'web.components.page';
    }

    abstract public function getWidgets(): array;
}
