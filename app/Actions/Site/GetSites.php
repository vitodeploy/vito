<?php

namespace App\Actions\Site;

use App\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class GetSites
{
    public function get(Server $server, array $input, int $perPage = 10): Collection
    {
        $validated = $this->validate($input);

        $sitesQuery = $server->sites();

        if (! empty($validated['query'])) {
            $sitesQuery->where('domain', 'like', "%{$validated['query']}%");
        }

        $page = $validated['page'] ?? 1;

        return $sitesQuery
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
