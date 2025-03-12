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
    /** @use HasFactory<\Database\Factories\DeploymentScriptFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($deploymentScript): void {
            $deploymentScript->content = str_replace("\r\n", "\n", $deploymentScript->content);
        });
    }

    protected $fillable = [
        'site_id',
        'name',
        'content',
    ];

    protected $casts = [
        'site_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
