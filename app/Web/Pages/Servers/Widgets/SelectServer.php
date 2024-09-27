<?php

namespace App\Web\Pages\Servers\Widgets;

use App\Models\Server;
use App\Web\Resources\Server\Pages\CreateServer;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SelectServer extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'web.components.form';

    public int|string|null $server = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function mount(): void
    {
        $server = Server::query()
            ->where('project_id', auth()->user()->current_project_id)
            ->find(session()->get('current_server_id'));
        if ($server && auth()->user()->can('view', $server)) {
            $this->server = $server->id;
        }
    }

    protected function getFormSchema(): array
    {
        $options = $this->query()
            ->limit(10)
            ->pluck('name', 'id')
            ->toArray();

        return [
            Select::make('server')
                ->name('server')
                ->model($this->server)
                ->searchable()
                ->options($options)
                ->searchPrompt('Search...')
                ->getSearchResultsUsing(function ($search) {
                    return $this->query()
                        ->where('name', 'like', "%{$search}%")
                        ->limit(10)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->extraAttributes(['class' => '-mx-2 pointer-choices'])
                ->live()
                ->hintIcon('heroicon-o-question-mark-circle')
                ->hintIconTooltip('Filter resources by default based on a server')
                ->suffixAction(
                    Action::make('create')
                        ->icon('heroicon-o-plus')
                        ->tooltip('Create a new server')
                        ->url(CreateServer::getUrl())
                ),
        ];
    }

    private function query(): Builder
    {
        return Server::query()
            ->where(function (Builder $query) {
                $query->where('project_id', auth()->user()->current_project_id);
            });
    }

    public function updatedServer($value): void
    {
        if (! $value) {
            session()->forget('current_server_id');
            $this->redirect(url()->previous());

            return;
        }

        $server = Server::query()->find($value);
        if (! $server) {
            session()->forget('current_server_id');

            return;
        }

        $this->authorize('view', $server);
        session()->put('current_server_id', $value);

        $this->redirect(url()->previous());
    }
}
