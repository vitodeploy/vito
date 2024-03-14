<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected $casts = [
        'server_id' => 'integer',
        'site_id' => 'integer',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (ServerLog $log) {
            if (Storage::disk($log->disk)->exists($log->name)) {
                Storage::disk($log->disk)->delete($log->name);
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
}
