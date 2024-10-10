<?php

namespace App\Web\Pages\Servers\Metrics;

use App\Actions\Monitoring\UpdateMetricSettings;
use App\Models\Metric;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/metrics';

    protected static ?string $title = 'Metrics';

    public function mount(): void
    {
        $this->authorize('viewAny', [Metric::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\FilterForm::class, ['server' => $this->server]],
            [Widgets\MetricDetails::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('data-retention')
                ->button()
                ->color('gray')
                ->icon('heroicon-o-trash')
                ->label('Data Retention')
                ->modalWidth(MaxWidth::Large)
                ->form([
                    Select::make('data_retention')
                        ->options([
                            7 => '7 days',
                            14 => '14 days',
                            30 => '30 days',
                            60 => '60 days',
                            90 => '90 days',
                            180 => '180 days',
                            365 => '365 days',
                        ])
                        ->rules(UpdateMetricSettings::rules()['data_retention'])
                        ->label('Data Retention')
                        ->default($this->server->monitoring()?->type_data['data_retention'] ?? 30),
                ])
                ->modalSubmitActionLabel('Save')
                ->action(function (array $data) {
                    app(UpdateMetricSettings::class)->update($this->server, $data);

                    Notification::make()
                        ->success()
                        ->title('Data retention updated')
                        ->send();
                }),
        ];
    }
}
