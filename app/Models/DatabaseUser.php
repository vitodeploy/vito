<?php

namespace App\Models;

use App\Enums\DatabaseStatus;
use App\Jobs\DatabaseUser\CreateOnServer;
use App\Jobs\DatabaseUser\DeleteFromServer;
use App\Jobs\DatabaseUser\LinkUser;
use App\Jobs\DatabaseUser\UnlinkUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property string $username
 * @property string $password
 * @property array $databases
 * @property string $host
 * @property string $status
 * @property Server $server
 * @property string $full_user
 */
class DatabaseUser extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'username',
        'password',
        'databases',
        'host',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'password' => 'encrypted',
        'databases' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeHasDatabase(Builder $query, string $databaseName): Builder
    {
        return $query->where('databases', 'like', "%\"$databaseName\"%");
    }

    public function createOnServer(): void
    {
        dispatch(new CreateOnServer($this))->onConnection('ssh');
    }

    public function deleteFromServer(): void
    {
        $this->status = DatabaseStatus::DELETING;
        $this->save();

        dispatch(new DeleteFromServer($this))->onConnection('ssh');
    }

    public function linkNewDatabase(string $name): void
    {
        $linkedDatabases = $this->databases ?? [];
        if (! in_array($name, $linkedDatabases)) {
            $linkedDatabases[] = $name;
            $this->databases = $linkedDatabases;
            $this->unlinkUser();
            $this->linkUser();
            $this->save();
        }
    }

    public function linkUser(): void
    {
        dispatch(new LinkUser($this))->onConnection('ssh');
    }

    public function unlinkUser(): void
    {
        dispatch(new UnlinkUser($this))->onConnection('ssh');
    }

    public function getFullUserAttribute(): string
    {
        return $this->username.'@'.$this->host;
    }
}
