<?php

namespace App\Web\Pages\Servers\CronJobs;

use App\Actions\CronJob\CreateCronJob;
use App\Models\CronJob;
use App\Web\Pages\Servers\Page;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/cronjobs';

    protected static ?string $title = 'Cron Jobs';

    protected $listeners = ['$refresh'];

    public function mount(): void
    {
        $this->authorize('viewAny', [CronJob::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\CronJobsList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/servers/cronjobs.html')
                ->openUrlInNewTab(),
            Action::make('create')
                ->authorize(fn () => auth()->user()?->can('create', [CronJob::class, $this->server]))
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::ExtraLarge)
                ->form([
                    TextInput::make('command')
                        ->rules(fn (callable $get) => CreateCronJob::rules($get())['command'])
                        ->helperText(fn () => view('components.link', [
                            'href' => 'https://vitodeploy.com/servers/cronjobs.html',
                            'external' => true,
                            'text' => 'How the command should look like?',
                        ])),
                    Select::make('user')
                        ->rules(fn (callable $get) => CreateCronJob::rules($get())['user'])
                        ->options([
                            'vito' => $this->server->ssh_user,
                            'root' => 'root',
                        ]),
                    Select::make('frequency')
                        ->options(config('core.cronjob_intervals'))
                        ->reactive()
                        ->rules(fn (callable $get) => CreateCronJob::rules($get())['frequency']),
                    TextInput::make('custom')
                        ->label('Custom Frequency (Cron)')
                        ->rules(fn (callable $get) => CreateCronJob::rules($get())['custom'])
                        ->visible(fn (callable $get) => $get('frequency') === 'custom')
                        ->placeholder('0 * * * *'),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreateCronJob::class)->create($this->server, $data);
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title($e->getMessage())
                            ->send();

                        throw $e;
                    }

                    $this->dispatch('$refresh');
                }),
        ];
    }
}
