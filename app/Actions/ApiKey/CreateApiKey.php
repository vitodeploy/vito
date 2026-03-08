<?php

namespace App\Actions\ApiKey;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\NewAccessToken;

class CreateApiKey
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): NewAccessToken
    {
        $this->validate($user, $input);

        $abilities = ['read'];
        if ($input['permission'] === 'write') {
            $abilities[] = 'write';
        }

        if (! empty($input['projects'])) {
            foreach ($input['projects'] as $projectId) {
                $abilities[] = 'project:'.$projectId;
            }
        }

        return $user->createToken($input['name'], $abilities);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(User $user, array $input): void
    {
        $projectIds = $user->projects()->pluck('projects.id')->toArray();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'permission' => ['required', Rule::in(['read', 'write'])],
            'projects' => ['nullable', 'array'],
            'projects.*' => ['required', 'integer', Rule::in($projectIds)],
        ])->validate();
    }
}
