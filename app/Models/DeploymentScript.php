<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
