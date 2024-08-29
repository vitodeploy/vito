<?php

namespace App\Actions\Tag;

use App\Models\Server;
use App\Models\Site;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DetachTag
{
    public function detach(Tag $tag, array $input): void
    {
        $this->validate($input);

        /** @var Server|Site $taggable */
        $taggable = $input['taggable_type']::findOrFail($input['taggable_id']);

        $taggable->tags()->detach($tag->id);
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'taggable_id' => [
                'required',
                'integer',
            ],
            'taggable_type' => [
                'required',
                Rule::in(config('core.taggable_types')),
            ],
        ])->validate();
    }
}
