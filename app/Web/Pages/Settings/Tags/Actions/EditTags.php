<?php

namespace App\Web\Pages\Settings\Tags\Actions;

use App\Actions\Tag\SyncTags;
use App\Models\Server;
use App\Models\Site;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action as TableAction;

class EditTags
{
    /**
     * @param  Site|Server  $taggable
     */
    public static function infolist(mixed $taggable): InfolistAction
    {
        return InfolistAction::make('edit_tags')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit Tags')
            ->hiddenLabel()
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Edit Tags')
            ->modalWidth(MaxWidth::Medium)
            ->form(static::form($taggable))
            ->action(static::action($taggable));
    }

    /**
     * @param  Site|Server  $taggable
     */
    public static function table(mixed $taggable): TableAction
    {
        return TableAction::make('edit_tags')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit Tags')
            ->hiddenLabel()
            ->modalSubmitActionLabel('Save')
            ->modalHeading('Edit Tags')
            ->modalWidth(MaxWidth::Medium)
            ->form(static::form($taggable))
            ->action(static::action($taggable));
    }

    /**
     * @param  Site|Server  $taggable
     */
    private static function form(mixed $taggable): array
    {
        return [
            Select::make('tags')
                ->default($taggable->tags()->pluck('tags.id')->toArray())
                ->options(function () {
                    return auth()->user()->currentProject->tags()->pluck('name', 'id')->toArray();
                })
                ->nestedRecursiveRules(SyncTags::rules(auth()->user()->currentProject->id)['tags.*'])
                ->suffixAction(
                    FormAction::make('create_tag')
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
        ];
    }

    /**
     * @param  Site|Server  $taggable
     */
    private static function action(mixed $taggable): \Closure
    {
        return function (array $data) use ($taggable) {
            app(SyncTags::class)->sync(auth()->user(), [
                'taggable_id' => $taggable->id,
                'taggable_type' => get_class($taggable),
                'tags' => $data['tags'],
            ]);

            Notification::make()
                ->success()
                ->title('Tags updated!')
                ->send();
        };
    }
}
