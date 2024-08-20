<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditTag
{
    public function edit(Tag $tag, array $input): void
    {
        $this->validate($input);

        $tag->name = $input['name'];
        $tag->color = $input['color'];

        $tag->save();
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $rules = [
            'name' => [
                'required',
            ],
            'color' => [
                'required',
                Rule::in(config('core.tag_colors')),
            ],
        ];
        Validator::make($input, $rules)->validate();
    }
}
