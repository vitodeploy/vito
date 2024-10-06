<?php

namespace App\Actions\User;

use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateProjects
{
    public function update(User $user, array $input): void
    {
        $this->validate($input);
        $user->projects()->sync($input['projects']);

        if ($user->currentProject && ! $user->projects->contains($user->currentProject)) {
            $user->current_project_id = null;
            $user->save();
        }

        $user->refresh();

        /** @var Project $firstProject */
        $firstProject = $user->projects->first();
        if (! $user->currentProject && $firstProject) {
            $user->current_project_id = $firstProject->id;
            $user->save();
        }
    }

    private function validate(array $input): void
    {
        validator($input, self::rules())->validate();
    }

    public static function rules(): array
    {
        return [
            'projects.*' => [
                'required',
                Rule::exists('projects', 'id'),
            ],
        ];
    }
}
