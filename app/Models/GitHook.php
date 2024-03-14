<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property int $source_control_id
 * @property string $secret
 * @property array $events
 * @property array $actions
 * @property string $hook_id
 * @property array $hook_response
 * @property Site $site
 * @property SourceControl $sourceControl
 */
class GitHook extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'source_control_id',
        'secret',
        'events',
        'actions',
        'hook_id',
        'hook_response',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'source_control_id' => 'integer',
        'events' => 'array',
        'actions' => 'array',
        'hook_response' => 'json',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sourceControl(): BelongsTo
    {
        return $this->belongsTo(SourceControl::class);
    }

    public function deployHook(): void
    {
        $this->update(
            $this->sourceControl->provider()->deployHook($this->site->repository, $this->events, $this->secret)
        );
    }

    public function destroyHook(): void
    {
        $this->sourceControl->provider()->destroyHook($this->site->repository, $this->hook_id);
        $this->delete();
    }
}
