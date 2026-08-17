<?php

namespace App\Models;

use App\Traits\HasTimezoneTimestamps;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property array<string> $abilities
 * @property Carbon $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasTimezoneTimestamps;

    /**
     * Get the project IDs this token is scoped to.
     *
     * @return array<int>
     */
    public function getProjectIds(): array
    {
        return collect($this->abilities)
            ->filter(fn (string $ability) => str_starts_with($ability, 'project:'))
            ->map(fn (string $ability) => (int) str_replace('project:', '', $ability))
            ->values()
            ->all();
    }

    /**
     * Check if the token has access to the given project.
     * Tokens with no project restrictions have access to all projects (backward compatible).
     */
    public function hasProjectAccess(Project $project): bool
    {
        $projectIds = $this->getProjectIds();

        if (empty($projectIds)) {
            return true;
        }

        return in_array($project->id, $projectIds);
    }

    /**
     * Check if the token may reach a resource that belongs to the given project.
     * A null project id means the resource is global: every project may read it,
     * but writing it would affect projects outside the token's scope.
     */
    public function allowsProjectId(?int $projectId, bool $write = false): bool
    {
        $projectIds = $this->getProjectIds();

        if ($projectIds === []) {
            return true;
        }

        if ($projectId === null) {
            return ! $write;
        }

        return in_array($projectId, $projectIds, true);
    }
}
