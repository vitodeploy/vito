<?php

namespace App\Web\Pages\Settings\Tags\Actions;

use App\Actions\Tag\CreateTag;
use App\Models\Tag;
use App\Models\User;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class Create
{
    /**
     * @return array<int, mixed>
     */
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->rules(fn ($get) => CreateTag::rules()['name']),
            Select::make('color')
                ->prefixIcon('heroicon-s-tag')
                ->prefixIconColor(fn (Get $get): mixed => $get('color'))
                ->searchable()
                ->options(
                    collect((array) config('core.tag_colors'))
                        ->mapWithKeys(fn ($color) => [$color => $color])
                )
                ->reactive()
                ->rules(fn ($get) => CreateTag::rules()['color']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function action(array $data): Tag
    {
        try {
            /** @var User $user */
            $user = auth()->user();

            return app(CreateTag::class)->create($user, $data);
        } catch (Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
