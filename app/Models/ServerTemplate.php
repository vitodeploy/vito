<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property string $name
 * @property array<string> $services
 * @property User $user
 */
class ServerTemplate extends AbstractModel
{
    /** @use HasFactory<\Database\Factories\ServerTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'services',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'services' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
