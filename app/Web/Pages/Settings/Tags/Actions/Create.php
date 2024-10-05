<?php

namespace App\Web\Pages\Settings\Tags\Actions;

use App\Actions\Tag\CreateTag;
use App\Models\Tag;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class Create
{
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->rules(fn ($get) => CreateTag::rules()['name']),
            Select::make('color')
                ->prefixIcon('heroicon-s-tag')
                ->prefixIconColor(fn (Get $get) => $get('color'))
                ->searchable()
                ->options(
                    collect(config('core.tag_colors'))
                        ->mapWithKeys(fn ($color) => [$color => $color])
                )
                ->reactive()
                ->rules(fn ($get) => CreateTag::rules()['color']),
        ];
    }

    /**
     * @throws Exception
     */
    public static function action(array $data): Tag
    {
        try {
            return app(CreateTag::class)->create(auth()->user(), $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
