<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property string $name
 * @property string $content
 * @property Site $site
 */
class DeploymentScript extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'content',
    ];

    protected $casts = [
        'site_id' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
