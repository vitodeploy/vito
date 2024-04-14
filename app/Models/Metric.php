<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property float $load
 * @property float $memory_total
 * @property float $memory_used
 * @property float $memory_free
 * @property float $disk_total
 * @property float $disk_used
 * @property float $disk_free
 * @property Server $server
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'load',
        'memory_total',
        'memory_used',
        'memory_free',
        'disk_total',
        'disk_used',
        'disk_free',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'load' => 'float',
        'memory_total' => 'float',
        'memory_used' => 'float',
        'memory_free' => 'float',
        'disk_total' => 'float',
        'disk_used' => 'float',
        'disk_free' => 'float',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
