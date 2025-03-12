<?php

namespace App\Actions\Tag;

use App\Models\Server;
use App\Models\Site;
use App\Models\Tag;
use Illuminate\Validation\Rule;

class SyncTags
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function sync(array $input): void
    {
        /** @var Server|Site $taggable */
        $taggable = $input['taggable_type']::findOrFail($input['taggable_id']);

        $tags = Tag::query()->whereIn('id', $input['tags'])->get();

        $taggable->tags()->sync($tags->pluck('id'));
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(int $projectId): array
    {
        return [
            'tags.*' => [
                'required',
                Rule::exists('tags', 'id')->where('project_id', $projectId),
            ],
            'taggable_id' => [
                'required',
                'integer',
            ],
            'taggable_type' => [
                'required',
                Rule::in(config('core.taggable_types')),
            ],
        ];
    }
}
