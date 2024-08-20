<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class DeleteTag
{
    public function delete(Tag $tag): void
    {
        DB::table('taggables')->where('tag_id', $tag->id)->delete();
        $tag->delete();
    }
}
