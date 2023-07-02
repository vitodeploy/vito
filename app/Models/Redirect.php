<?php

namespace App\Models;

use App\Jobs\Redirect\AddToServer;
use App\Jobs\Redirect\DeleteFromServer;
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

    public function addToServer(): void
    {
        dispatch(new AddToServer($this))->onConnection('ssh');
    }

    public function deleteFromServer(): void
    {
        $this->status = 'deleting';
        $this->save();
        dispatch(new DeleteFromServer($this))->onConnection('ssh');
    }
}
