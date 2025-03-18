<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'from',
        'to',
        'mode',
    ];

    /**
     * @return BelongsTo<Site, Redirect>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
