<?php

namespace App\Web\Pages\Servers\Sites\Pages\Queues;

use App\Actions\Queue\CreateQueue;
use App\Models\Queue;
use App\Web\Pages\Servers\Sites\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/sites/{site}/queues';

    protected static ?string $title = 'Queues';

    public function mount(): void
    {
        $this->authorize('viewAny', [Queue::class, $this->site, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\QueuesList::class, ['site' => $this->site]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('read-the-docs')
                ->label('Read the Docs')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('https://vitodeploy.com/sites/queues')
                ->openUrlInNewTab(),
            CreateAction::make('create')
                ->icon('heroicon-o-plus')
                ->createAnother(false)
                ->modalWidth(MaxWidth::ExtraLarge)
                ->label('New Queue')
                ->form([
                    TextInput::make('command')
                        ->rules(CreateQueue::rules($this->site)['command'])
                        ->helperText('Example: php /home/vito/your-site/artisan queue:work'),
                    Select::make('user')
                        ->rules(fn (callable $get) => CreateQueue::rules($this->site)['user'])
                        ->options([
                            'root' => 'root',
                            $this->site->user => $this->site->user,
                        ]),
                    TextInput::make('numprocs')
                        ->default(1)
                        ->rules(CreateQueue::rules($this->site)['numprocs'])
                        ->helperText('Number of processes'),
                    Grid::make()
                        ->schema([
                            Checkbox::make('auto_start')
                                ->default(false),
                            Checkbox::make('auto_restart')
                                ->default(false),
                        ]),
                ])
                ->using(function (array $data) {
                    run_action($this, function () use ($data) {
                        app(CreateQueue::class)->create($this->site, $data);

                        $this->dispatch('$refresh');
                    });
                }),
        ];
    }
}
