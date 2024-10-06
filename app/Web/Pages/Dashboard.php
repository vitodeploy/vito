<?php

namespace App\Web\Pages;

use Filament\Widgets\AccountWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
        ];
    }
}
