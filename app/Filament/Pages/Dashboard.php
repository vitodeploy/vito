<?php

namespace App\Filament\Pages;

use Filament\Widgets\AccountWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            AccountWidget::class
        ];
    }
}
