<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @property int $server_id
 * @property ?int $site_id
 * @property string $type
 * @property string $name
 * @property string $disk
 * @property Server $server
 * @property ?Site $site
 * @property bool $is_remote
 */
class ServerLog extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'site_id',
        'type',
        'name',
        'disk',
        'is_remote',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'site_id' => 'integer',
        'is_remote' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (ServerLog $log) {
            if ($log->is_remote) {
                try {
                    if (Storage::disk($log->disk)->exists($log->name)) {
                        Storage::disk($log->disk)->delete($log->name);
                    }
                } catch (Exception $e) {
                    Log::error($e->getMessage(), ['exception' => $e]);
                }
            }
        });
    }

    public function getRouteKey(): string
    {
        return 'log';
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @throws Throwable
     */
    public function download(): StreamedResponse
    {
        if ($this->is_remote) {
            $tmpName = $this->server->id.'-'.strtotime('now').'-'.$this->type.'.log';
            $tmpPath = Storage::disk('local')->path($tmpName);

            $this->server->ssh()->download($tmpPath, $this->name);

            dispatch(function () use ($tmpPath) {
                if (File::exists($tmpPath)) {
                    File::delete($tmpPath);
                }
            })
                ->delay(now()->addMinutes(5))
                ->onQueue('default');

            return Storage::disk('local')->download($tmpName, str($this->name)->afterLast('/'));
        }

        return Storage::disk($this->disk)->download($this->name);
    }

    public static function getRemote($query, bool $active = true, ?Site $site = null)
    {
        $query->where('is_remote', $active);

        if ($site) {
            $query->where('name', 'like', $site->path.'%');
        }

        return $query;
    }

    public function write($buf): void
    {
        if (Str::contains($buf, 'VITO_SSH_ERROR')) {
            $buf = str_replace('VITO_SSH_ERROR', '', $buf);
        }
        if (Storage::disk($this->disk)->exists($this->name)) {
            Storage::disk($this->disk)->append($this->name, $buf);
        } else {
            Storage::disk($this->disk)->put($this->name, $buf);
        }
    }

    public function getContent($lines = null): ?string
    {
        if ($this->is_remote) {
            return $this->server->os()->tail($this->name, $lines ?? 150);
        }

        if (Storage::disk($this->disk)->exists($this->name)) {
            if ($lines) {
                return tail(Storage::disk($this->disk)->path($this->name), $lines);
            }

            return Storage::disk($this->disk)->get($this->name);
        }

        return '';
    }

    public static function log(Server $server, string $type, string $content, ?Site $site = null): static
    {
        $log = new static([
            'server_id' => $server->id,
            'site_id' => $site?->id,
            'name' => $server->id.'-'.strtotime('now').'-'.$type.'.log',
            'type' => $type,
            'disk' => config('core.logs_disk'),
        ]);
        $log->save();
        $log->write($content);

        return $log;
    }

    public static function make(Server $server, string $type): ServerLog
    {
        return new static([
            'server_id' => $server->id,
            'name' => $server->id.'-'.strtotime('now').'-'.$type.'.log',
            'type' => $type,
            'disk' => config('core.logs_disk'),
        ]);
    }

    public function forSite(Site|int $site): ServerLog
    {
        if ($site instanceof Site) {
            $site = $site->id;
        }

        if (is_int($site)) {
            $this->site_id = $site;
        }

        return $this;
    }
}
