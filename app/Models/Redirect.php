<?php

namespace App\Models;

use App\Enums\RedirectStatus;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $site_id
 * @property string $from
 * @property string $to
 * @property string $mode
 * @property RedirectStatus $status
 * @property Site $site
 */
class Redirect extends AbstractModel
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'from',
        'to',
        'mode',
        'status',
    ];

    protected $casts = [
        'status' => RedirectStatus::class,
    ];

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
