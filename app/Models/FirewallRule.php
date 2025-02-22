<?php

namespace App\Models;

use App\Enums\FirewallRuleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $name
 * @property string $type
 * @property string $protocol
 * @property int $port
 * @property string $source
 * @property ?string $mask
 * @property string $note
 * @property string $status
 * @property Server $server
 */
class FirewallRule extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'server_id',
        'type',
        'protocol',
        'port',
        'source',
        'mask',
        'note',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'port' => 'integer',
    ];

    public function getStatusColor(): string
    {
        return match ($this->status) {
            FirewallRuleStatus::CREATING,
            FirewallRuleStatus::UPDATING,
            FirewallRuleStatus::DELETING => 'warning',
            FirewallRuleStatus::READY => 'success',
            FirewallRuleStatus::FAILED => 'danger',
        };
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
