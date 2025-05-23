<?php

namespace App\Web\Pages\Settings\Tags\Actions;

use App\Actions\Tag\EditTag;
use App\Models\Tag;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class Edit
{
    /**
     * @return array<int, mixed>
     */
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->rules(EditTag::rules()['name']),
            Select::make('color')
                ->searchable()
                ->options(
                    collect((array) config('core.tag_colors'))
                        ->mapWithKeys(fn ($color) => [$color => $color])
                )
                ->rules(fn ($get) => EditTag::rules()['color']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function action(Tag $tag, array $data): void
    {
        app(EditTag::class)->edit($tag, $data);
    }
}
