<?php

namespace App\Web\Pages\Servers\Sites\Pages\SSL;

use App\Actions\SSL\CreateSSL;
use App\Enums\SslType;
use App\Models\Ssl;
use App\Web\Fields\AlertField;
use App\Web\Pages\Servers\Sites\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
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
                ->url('https://vitodeploy.com/sites/ssl')
                ->openUrlInNewTab(),
            Action::make('force-ssl')
                ->label('Force SSL')
                ->tooltip(fn () => $this->site->force_ssl ? 'Disable force SSL' : 'Enable force SSL')
                ->icon(fn () => $this->site->force_ssl ? 'icon-force-ssl-enabled' : 'icon-force-ssl-disabled')
                ->requiresConfirmation()
                ->modalSubmitActionLabel(fn () => $this->site->force_ssl ? 'Disable' : 'Enable')
                ->action(function () {
                    $this->site->update([
                        'force_ssl' => ! $this->site->force_ssl,
                    ]);
                    $this->site->webserver()->updateVHost($this->site);
                    Notification::make()
                        ->success()
                        ->title('SSL status has been updated.')
                        ->send();
                    $this->dispatch('$refresh');
                })
                ->color('gray'),
            CreateAction::make('create')
                ->label('New Certificate')
                ->icon('heroicon-o-lock-closed')
                ->form([
                    AlertField::make('letsencrypt-info')
                        ->warning()
                        ->message('Let\'s Encrypt has rate limits. Read more about them <a href="https://letsencrypt.org/docs/rate-limits/" target="_blank" class="underline">here</a>.'),
                    Select::make('type')
                        ->options(
                            collect(config('core.ssl_types'))->mapWithKeys(fn ($type) => [$type => $type])
                        )
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['type'])
                        ->reactive(),
                    TextInput::make('email')
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['email'] ?? [])
                        ->visible(fn (Get $get) => $get('type') === SslType::LETSENCRYPT)
                        ->helperText('Email address to provide to Certbot.'),
                    Textarea::make('certificate')
                        ->rows(5)
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['certificate'])
                        ->visible(fn (Get $get) => $get('type') === SslType::CUSTOM),
                    Textarea::make('private')
                        ->label('Private Key')
                        ->rows(5)
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['private'])
                        ->visible(fn (Get $get) => $get('type') === SslType::CUSTOM),
                    DatePicker::make('expires_at')
                        ->format('Y-m-d')
                        ->rules(fn (Get $get) => CreateSSL::rules($get())['expires_at'])
                        ->visible(fn (Get $get) => $get('type') === SslType::CUSTOM),
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
