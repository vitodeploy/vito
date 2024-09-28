<?php

namespace App\Web\Components;

use App\Web\Traits\HasWidgets;
use Filament\Pages\Page as BasePage;

abstract class Page extends BasePage
{
    use HasWidgets;

    protected static string $view = 'web.components.page';
}
