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
 * @property bool $websocket
 * @property RedirectStatus $status
 * @property Site $site
 */
class Redirect extends AbstractModel
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    public const int MODE_PROXY = 1000;

    protected $fillable = [
        'site_id',
        'from',
        'to',
        'mode',
        'websocket',
        'status',
    ];

    protected $casts = [
        'websocket' => 'boolean',
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
