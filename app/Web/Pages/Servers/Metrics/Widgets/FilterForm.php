<?php

namespace App\Web\Pages\Servers\Metrics\Widgets;

use App\Actions\Monitoring\GetMetrics;
use App\Models\Server;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Widgets\Widget;

class FilterForm extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'components.form';

    public ?array $data = [
        'period' => '1h',
        'from' => null,
        'to' => null,
    ];

    public function updated($name, $value): void
    {
        if ($value !== 'custom') {
            $this->dispatch('updateFilters', filters: $this->data);
        }

        if ($value === 'custom' && $this->data['from'] && $this->data['to']) {
            $this->dispatch('updateFilters', filters: $this->data);
        }
    }

    public Server $server;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(3)
                    ->schema([
                        Select::make('period')
                            ->live()
                            ->reactive()
                            ->options([
                                '10m' => '10 Minutes',
                                '30m' => '30 Minutes',
                                '1h' => '1 Hour',
                                '12h' => '12 Hours',
                                '1d' => '1 Day',
                                '7d' => '7 Days',
                                'custom' => 'Custom',
                            ])
                            ->rules(fn (Get $get) => GetMetrics::rules($get())['period']),
                        DatePicker::make('from')
                            ->reactive()
                            ->visible(fn (Get $get) => $get('period') === 'custom')
                            ->maxDate(fn (Get $get) => now())
                            ->rules(fn (Get $get) => GetMetrics::rules($get())['from']),
                        DatePicker::make('to')
                            ->reactive()
                            ->visible(fn (Get $get) => $get('period') === 'custom')
                            ->minDate(fn (Get $get) => $get('from') ?: now())
                            ->maxDate(now())
                            ->rules(fn (Get $get) => GetMetrics::rules($get())['to']),
                    ]),
                ViewField::make('data')
                    ->reactive()
                    ->view('components.dynamic-widget', [
                        'widget' => Metrics::class,
                        'params' => [
                            'server' => $this->server,
                            'filters' => $this->data,
                        ],
                    ]),
            ])
            ->statePath('data');
    }
}
