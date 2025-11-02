<?php

namespace App\Actions\Server;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class GetServers
{
    public function get(Project $project, array $input, int $perPage = 10): Collection
    {
        $validated = $this->validate($input);

        $serversQuery = $project->servers();

        if (! empty($validated['query'])) {
            $serversQuery->where('name', 'like', "%{$validated['query']}%");
        }

        $page = $validated['page'] ?? 1;

        return $serversQuery
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
