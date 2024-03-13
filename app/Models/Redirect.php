<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property int $mode
 * @property string $from
 * @property string $to
 * @property string $status
 */
class Redirect extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'mode',
        'from',
        'to',
        'status',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'mode' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
