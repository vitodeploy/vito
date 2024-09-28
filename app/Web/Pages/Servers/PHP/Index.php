<?php

namespace App\Web\Pages\Servers\PHP;

use App\Actions\PHP\InstallNewPHP;
use App\Models\Server;
use App\Models\Service;
use App\Web\Components\Page;
use App\Web\Pages\Servers\PHP\Widgets\PHPList;
use App\Web\Traits\PageHasServer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

class Index extends Page
{
    use PageHasServer;

    protected static ?string $slug = 'servers/{server}/php';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'PHP';

    public Server $server;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', [Service::class, static::getServerFromRoute()]) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            [PHPList::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        $phps = [];
        foreach (config('core.php_versions') as $version) {
            if (! $this->server->service('php', $version) && $version !== 'none') {
                $phps[] = Action::make($version)
                    ->label($version)
                    ->requiresConfirmation()
                    ->modalHeading('Install PHP '.$version)
                    ->modalSubmitActionLabel('Install')
                    ->action(function () use ($version) {
                        app(InstallNewPHP::class)->install($this->server, ['version' => $version]);

                        $this->dispatch('$refresh');
                    });
            }
        }

        return [
            ActionGroup::make($phps)
                ->authorize(fn () => auth()->user()?->can('create', [Service::class, $this->server]))
                ->label('Install PHP')
                ->icon('heroicon-o-plus')
                ->dropdownPlacement('bottom-end')
                ->color('primary')
                ->button(),
        ];
    }
}
