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
 * @property string $status
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

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        RedirectStatus::CREATING => 'warning',
        RedirectStatus::READY => 'success',
        RedirectStatus::DELETING => 'warning',
        RedirectStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
