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
     * Whether the token is restricted to one or more projects.
     */
    public function isProjectScoped(): bool
    {
        return $this->getProjectIds() !== [];
    }

    /**
     * Whether this token may read/use a resource in the given project.
     *
     * Global resources are intentionally available to every project. Once a
     * token is project-scoped, write access to a global resource is rejected
     * because mutating it affects projects outside the token's scope.
     *
     * @param  int|null  $projectId  The resource's project_id, or null for a global resource.
     * @param  bool  $write  Whether this is a mutating operation.
     */
    public function allowsProject(?int $projectId, bool $write = false): bool
    {
        if (! $this->isProjectScoped()) {
            return true;
        }

        if ($projectId === null) {
            return ! $write;
        }

        return in_array($projectId, $this->getProjectIds(), true);
    }

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
}
