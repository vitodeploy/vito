<?php

namespace App\Models;

use Database\Factories\IsolatedUserFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @property int $server_id
 * @property string $username
 * @property ?string $ssh_key
 * @property ?array<string, array{version?: string, status?: ?string}> $installed_tooling
 * @property Server $server
 * @property Collection<int, Site> $sites
 */
class IsolatedUser extends AbstractModel
{
    /** @use HasFactory<IsolatedUserFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'username',
        'ssh_key',
        'installed_tooling',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'ssh_key' => 'encrypted',
        'installed_tooling' => 'array',
    ];

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<Site, covariant $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function lock(): Lock
    {
        return Cache::lock("isolate:{$this->server_id}:{$this->username}", 60);
    }

    public function toolingVersion(string $toolId): ?string
    {
        $value = ($this->installed_tooling[$toolId]['version'] ?? null);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function toolingStatus(string $toolId): ?string
    {
        $value = ($this->installed_tooling[$toolId]['status'] ?? null);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setToolingVersion(string $toolId, string $version): void
    {
        $this->mutateTooling($toolId, ['version' => $version]);
    }

    public function setToolingStatus(string $toolId, ?string $status): void
    {
        $this->mutateTooling($toolId, ['status' => $status]);
    }

    public function clearTooling(string $toolId): void
    {
        DB::transaction(function () use ($toolId): void {
            /** @var self $fresh */
            $fresh = self::query()->lockForUpdate()->findOrFail($this->id);
            $data = $fresh->installed_tooling ?? [];
            unset($data[$toolId]);
            $fresh->installed_tooling = $data === [] ? null : $data;
            $fresh->save();
            $this->installed_tooling = $fresh->installed_tooling;
        });
    }

    /**
     * @param  array<string, mixed>  $patch
     *
     * @throws Throwable
     */
    private function mutateTooling(string $toolId, array $patch): void
    {
        DB::transaction(function () use ($toolId, $patch): void {
            /** @var self $fresh */
            $fresh = self::query()->lockForUpdate()->findOrFail($this->id);
            $data = $fresh->installed_tooling ?? [];
            $data[$toolId] = array_merge($data[$toolId] ?? [], $patch);
            $fresh->installed_tooling = $data;
            $fresh->save();
            $this->installed_tooling = $data;
        });
    }
}
