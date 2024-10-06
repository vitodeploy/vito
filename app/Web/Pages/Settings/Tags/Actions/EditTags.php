<?php

namespace App\Web\Pages\Settings\Tags\Actions;

use App\Actions\Tag\SyncTags;
use App\Models\Server;
use App\Models\Site;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;

class EditTags
{
    /**
     * @param  Site|Server  $taggable
     */
    public static function infolist(mixed $taggable): Action
    {
        return Action::make('edit_tags')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit Tags')
            ->hiddenLabel()
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Edit Tags')
            ->modalWidth(MaxWidth::Medium)
            ->form([
                Select::make('tags')
                    ->default($taggable->tags()->pluck('tags.id')->toArray())
                    ->options(function () {
                        return auth()->user()->currentProject->tags()->pluck('name', 'id')->toArray();
                    })
                    ->nestedRecursiveRules(SyncTags::rules(auth()->user()->currentProject->id)['tags.*'])
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('create_tag')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Create a new tag')
                            ->modalSubmitActionLabel('Create')
                            ->modalHeading('Create Tag')
                            ->modalWidth(MaxWidth::Medium)
                            ->form(Create::form())
                            ->action(function (array $data) {
                                Create::action($data);
                            }),
                    )
                    ->multiple(),
            ])
            ->action(function (array $data) use ($taggable) {
                app(SyncTags::class)->sync(auth()->user(), [
                    'taggable_id' => $taggable->id,
                    'taggable_type' => get_class($taggable),
                    'tags' => $data['tags'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Tags updated!')
                    ->send();
            });
    }
}
