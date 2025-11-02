<?php

namespace App\Actions\Projects;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class GetProjects
{
    public function get(User $user, array $input, int $perPage = 10): Collection
    {
        $validated = $this->validate($input);

        $projectsQuery = $user->allProjects();

        if (! empty($validated['query'])) {
            $projectsQuery->where('name', 'like', "%{$validated['query']}%");
        }

        $page = $validated['page'] ?? 1;

        return $projectsQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
    }

    private function validate(array $input): array
    {
        return Validator::make($input, [
            'query' => [
                'nullable',
                'string',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ])->validate();
    }
}
