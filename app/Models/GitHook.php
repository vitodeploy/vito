<?php

namespace App\Models;

use App\Exceptions\FailedToDestroyGitHook;
use Database\Factories\GitHookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $site_id
 * @property int $source_control_id
 * @property string $secret
 * @property array<string> $events
 * @property array<string, mixed> $actions
 * @property string $hook_id
 * @property array<string, mixed> $hook_response
 * @property Site $site
 * @property SourceControl $sourceControl
 */
class GitHook extends AbstractModel
{
    /** @use HasFactory<GitHookFactory> */
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

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<SourceControl, covariant $this>
     */
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

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(): void
    {
        $this->sourceControl->provider()->destroyHook($this->site->repository, $this->hook_id);
        $this->delete();
    }
}
