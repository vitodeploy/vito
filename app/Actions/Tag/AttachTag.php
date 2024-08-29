<?php

namespace App\Actions\Tag;

use App\Models\Server;
use App\Models\Site;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttachTag
{
    public function attach(User $user, array $input): Tag
    {
        $this->validate($input);

        /** @var Server|Site $taggable */
        $taggable = $input['taggable_type']::findOrFail($input['taggable_id']);

        $tag = Tag::query()->where('name', $input['name'])->first();
        if ($tag) {
            if (! $taggable->tags->contains($tag->id)) {
                $taggable->tags()->attach($tag->id);
            }

            return $tag;
        }

        $tag = new Tag([
            'project_id' => $user->currentProject->id,
            'name' => $input['name'],
            'color' => config('core.tag_colors')[array_rand(config('core.tag_colors'))],
        ]);
        $tag->save();

        $taggable->tags()->attach($tag->id);

        return $tag;
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => [
                'required',
            ],
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
