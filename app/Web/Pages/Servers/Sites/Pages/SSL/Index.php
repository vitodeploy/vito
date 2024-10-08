<?php

namespace App\Web\Pages\Servers\Sites\Pages\SSL;

use App\Actions\SSL\CreateSSL;
use App\Models\Ssl;
use App\Web\Pages\Servers\Sites\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/ssl';

    protected static ?string $title = 'SSL';

    public function mount(): void
    {
        $this->authorize('viewAny', [Ssl::class, $this->site, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\SslsList::class, ['site' => $this->site]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/ssl.html')
                ->openUrlInNewTab(),
            CreateAction::make('create')
                ->label('New Certificate')
                ->icon('heroicon-o-lock-closed')
                ->form([
                    Select::make('type')
                        ->options(
                            collect(config('core.ssl_types'))->mapWithKeys(fn ($type) => [$type => $type])
                        )
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['type'])
                        ->reactive(),
                    Textarea::make('certificate')
                        ->rows(5)
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['certificate'])
                        ->visible(fn (Get $get) => $get('type') === 'custom'),
                    Textarea::make('private')
                        ->label('Private Key')
                        ->rows(5)
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['private'])
                        ->visible(fn (Get $get) => $get('type') === 'custom'),
                    DatePicker::make('expires_at')
                        ->format('Y-m-d')
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['expires_at'])
                        ->visible(fn (Get $get) => $get('type') === 'custom'),
                    Checkbox::make('aliases')
                        ->label("Set SSL for site's aliases as well"),
                ])
                ->createAnother(false)
                ->modalWidth(MaxWidth::Large)
                ->using(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(CreateSSL::class)->create($this->site, $data);

                        $this->dispatch('$refresh');
                    });
                }),
        ];
    }
}
