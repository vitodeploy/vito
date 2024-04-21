<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            try {
                if (Storage::disk($log->disk)->exists($log->name)) {
                    Storage::disk($log->disk)->delete($log->name);
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage(), ['exception' => $e]);
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

    public function getContent(): ?string
    {
        if ($this->is_remote) {
            return $this->server->os()->tail($this->name, 150);
        }

        if (Storage::disk($this->disk)->exists($this->name)) {
            return Storage::disk($this->disk)->get($this->name);
        }

        return '';
    }

    public static function log(Server $server, string $type, string $content, ?Site $site = null): void
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
