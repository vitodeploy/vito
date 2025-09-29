<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $project_id
 * @property ?int $user_id
 * @property ?string $email
 * @property UserRole $role
 * @property ?User $user
 * @property Project $project
 */
class UserProject extends Model
{
    protected $table = 'user_project';

    protected $fillable = [
        'project_id',
        'user_id',
        'email',
        'role',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'user_id' => 'integer',
        'role' => UserRole::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
