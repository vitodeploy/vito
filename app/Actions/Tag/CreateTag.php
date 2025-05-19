<?php

namespace App\Actions\Tag;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateTag
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(User $user, array $input): Tag
    {
        Validator::make($input, self::rules())->validate();

        $tag = Tag::query()
            ->where('project_id', $user->current_project_id)
            ->where('name', $input['name'])
            ->first();

        if ($tag) {
            throw ValidationException::withMessages([
                'name' => ['Tag with this name already exists.'],
            ]);
        }

        $tag = new Tag([
            'project_id' => $user->currentProject?->id,
            'name' => $input['name'],
            'color' => $input['color'],
        ]);
        $tag->save();

        return $tag;
    }

    /**
     * @return array<string, mixed>
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
