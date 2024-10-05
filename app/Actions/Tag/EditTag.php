<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Validation\Rule;

class EditTag
{
    public function edit(Tag $tag, array $input): void
    {
        $tag->name = $input['name'];
        $tag->color = $input['color'];

        $tag->save();
    }

    public static function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
            'color' => [
                'required',
                Rule::in(config('core.tag_colors')),
            ],
        ];
    }
}
