<?php

namespace App\Models;

use App\Exceptions\FailedToDestroyGitHook;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<SourceControl, $this>
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
