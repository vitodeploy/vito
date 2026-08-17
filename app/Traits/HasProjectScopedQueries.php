<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasProjectScopedQueries
{
    /**
     * The user's records in the given project, plus the global ones that belong
     * to every project.
     *
     * @return Builder<static>
     */
    public static function getByProjectId(int $projectId, User $user): Builder
    {
        return self::scopedToProjects($user, [$projectId]);
    }

    /**
     * The user's records that the request's API token is allowed to reach. A
     * token without project restrictions reaches all of them.
     *
     * @return Builder<static>
     */
    public static function getByTokenProjects(User $user): Builder
    {
        return self::scopedToProjects($user, $user->tokenProjectIds());
    }

    /**
     * @param  array<int>  $projectIds  An empty list applies no project filter.
     * @return Builder<static>
     */
    private static function scopedToProjects(User $user, array $projectIds): Builder
    {
        /** @var Builder<static> $query */
        $query = static::query();

        $query->where('user_id', $user->id);

        if ($projectIds !== []) {
            $query->where(function (Builder $query) use ($projectIds): void {
                $query->whereIn('project_id', $projectIds)->orWhereNull('project_id');
            });
        }

        return $query;
    }
}
