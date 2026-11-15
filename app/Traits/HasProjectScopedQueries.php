<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasProjectScopedQueries
{
    /**
     * @return Builder<static>
     */
    public static function getByProjectId(int $projectId, User $user): Builder
    {
        return self::scopedToProjects($user, [$projectId]);
    }

    /**
     * @return Builder<static>
     */
    public static function getByTokenProjects(User $user): Builder
    {
        return self::scopedToProjects($user, $user->tokenProjectIds());
    }

    /**
     * @param  array<int>  $projectIds
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
