<?php

namespace App\Web\Pages\Servers;

use App\Models\Server;
use App\Web\Pages\Servers\Widgets\Installing;
use App\Web\Traits\PageHasServer;
use App\Web\Traits\PageHasWidgets;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class View extends Page
{
    use PageHasServer;
    use PageHasWidgets;

    protected static ?string $slug = 'servers/{server}';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Overview';

    public Server $server;

    public string $previousStatus;

    public function mount(): void
    {
        $this->previousStatus = $this->server->status;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view', request()->route('server')) ?? false;
    }

    #[On('$refresh')]
    public function refresh(): void
    {
        $currentStatus = $this->server->refresh()->status;

        if ($this->previousStatus !== $currentStatus) {
            $this->redirect(static::getUrl(parameters: ['server' => $this->server]));
        }

        $this->previousStatus = $currentStatus;
    }

    public function getWidgets(): array
    {
        if ($this->server->isInstalling()) {
            return [
                [Installing::class, ['server' => $this->server]],
            ];
        }

        return [];
    }
}
