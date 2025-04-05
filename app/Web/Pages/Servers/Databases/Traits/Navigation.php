<?php

namespace App\Web\Pages\Servers\Databases\Traits;

use App\Models\Backup;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\User;
use App\Web\Pages\Servers\Databases\Backups;
use App\Web\Pages\Servers\Databases\Index as Databases;
use App\Web\Pages\Servers\Databases\Users;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

trait Navigation
{
    public function getSecondSubNavigation(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $items = [];

        if ($user->can('viewAny', [Database::class, $this->server])) {
            $items[] = NavigationItem::make(Databases::getNavigationLabel())
                ->icon('heroicon-o-circle-stack')
                ->isActiveWhen(fn () => request()->routeIs(Databases::getRouteName()))
                ->url(Databases::getUrl(parameters: ['server' => $this->server]));
        }

        if ($user->can('viewAny', [DatabaseUser::class, $this->server])) {
            $items[] = NavigationItem::make(Users::getNavigationLabel())
                ->icon('heroicon-o-users')
                ->isActiveWhen(fn () => request()->routeIs(Users::getRouteName()))
                ->url(Users::getUrl(parameters: ['server' => $this->server]));
        }

        if ($user->can('viewAny', [Backup::class, $this->server])) {
            $items[] = NavigationItem::make(Backups::getNavigationLabel())
                ->icon('heroicon-o-cloud')
                ->isActiveWhen(fn () => request()->routeIs(Backups::getRouteName()))
                ->url(Backups::getUrl(parameters: ['server' => $this->server]));
        }

        return [
            NavigationGroup::make()
                ->items($items),
        ];
    }
}
