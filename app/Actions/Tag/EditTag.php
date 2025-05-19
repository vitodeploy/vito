<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EditTag
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(Tag $tag, array $input): void
    {
        Validator::make($input, self::rules())->validate();

        $tag->name = $input['name'];
        $tag->color = $input['color'];

        $tag->save();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
            'color' => [
                'required',
                Rule::in(config('core.colors')),
            ],
        ];
    }
}
