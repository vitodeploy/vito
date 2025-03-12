<?php

namespace App\Web\Pages\Servers\Firewall;

use App\Actions\FirewallRule\ManageRule;
use App\Models\FirewallRule;
use App\Web\Pages\Servers\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Request;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/firewall';

    protected static ?string $title = 'Firewall';

    /**
     * @var array<string>
     */
    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        $this->authorize('viewAny', [FirewallRule::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\RulesList::class, ['server' => $this->server]],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getFirewallForm(?FirewallRule $record = null): array
    {
        return [
            TextInput::make('name')
                ->label('Purpose')
                ->default($record->name ?? null)
                ->rules(ManageRule::rules()['name']),
            Select::make('type')
                ->label('Type')
                ->default($record->type ?? 'allow')
                ->options([
                    'allow' => 'Allow',
                    'deny' => 'Deny',
                ])
                ->rules(ManageRule::rules()['type']),
            Select::make('protocol')
                ->label('Protocol')
                ->default($record->protocol ?? 'tcp')
                ->options([
                    'tcp' => 'TCP',
                    'udp' => 'UDP',
                ])
                ->rules(ManageRule::rules()['protocol']),
            TextInput::make('port')
                ->label('Port')
                ->default($record->port ?? null)
                ->rules(['required', 'integer']),
            Checkbox::make('source_any')
                ->label('Any Source')
                ->default(($record->source ?? null) == null)
                ->rules(['boolean'])
                ->helperText('Allow connections from any source, regardless of their IP address or subnet mask.')
                ->live(),
            TextInput::make('source')
                ->hidden(fn (Get $get): bool => $get('source_any') == true)
                ->label('Source')
                ->helperText('The IP address of the source of the connection.')
                ->rules(ManageRule::rules()['source'])
                ->default($record->source ?? null)
                ->suffixAction(
                    \Filament\Forms\Components\Actions\Action::make('get_ip')
                        ->icon('heroicon-o-globe-alt')
                        ->color('primary')
                        ->tooltip('Use My IP')
                        ->action(function ($set): void {
                            $ip = Request::ip();
                            $set('source', $ip);
                        })
                ),
            TextInput::make('mask')
                ->hidden(fn (Get $get): bool => $get('source_any') == true)
                ->label('Mask')
                ->default($record->mask ?? null)
                ->helperText('The subnet mask of the source of the connection. Leave blank for a single IP address.')
                ->rules(ManageRule::rules()['mask']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/firewall')
                ->openUrlInNewTab(),
            Action::make('create')
                ->authorize(fn () => auth()->user()?->can('create', [FirewallRule::class, $this->server]))
                ->label('Create a Rule')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::Large)
                ->modalHeading('Create Firewall Rule')
                ->modalDescription('Add a new rule to the firewall')
                ->modalSubmitActionLabel('Create')
                ->form(self::getFirewallForm())
                ->action(function (array $data): void {
                    run_action($this, function () use ($data): void {
                        app(ManageRule::class)->create($this->server, $data);

                        $this->dispatch('$refresh');

                        Notification::make()
                            ->success()
                            ->title('Applying Firewall Rule')
                            ->send();
                    });
                }),
        ];
    }
}
