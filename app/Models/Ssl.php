<?php

namespace App\Models;

use App\Enums\SslStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $site_id
 * @property string $type
 * @property string $certificate
 * @property string $pk
 * @property string $ca
 * @property Carbon $expires_at
 * @property string $status
 * @property Site $site
 * @property ?array $domains
 * @property int $log_id
 * @property string $email
 * @property bool $is_active
 * @property string $certificate_path
 * @property string $pk_path
 * @property string $ca_path
 * @property ?ServerLog $log
 */
class Ssl extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'type',
        'certificate',
        'pk',
        'ca',
        'expires_at',
        'status',
        'domains',
        'log_id',
        'email',
        'is_active',
        'certificate_path',
        'pk_path',
        'ca_path',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'certificate' => 'encrypted',
        'pk' => 'encrypted',
        'ca' => 'encrypted',
        'expires_at' => 'datetime',
        'domains' => 'array',
        'log_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public static array $statusColors = [
        SslStatus::CREATED => 'success',
        SslStatus::CREATING => 'warning',
        SslStatus::DELETING => 'warning',
        SslStatus::FAILED => 'danger',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function validateSetup(string $result): bool
    {
        if (! Str::contains($result, 'Successfully received certificate')) {
            return false;
        }

        if ($this->type == 'letsencrypt') {
            $this->certificate_path = '/etc/letsencrypt/live/'.$this->id.'/fullchain.pem';
            $this->pk_path = '/etc/letsencrypt/live/'.$this->id.'/privkey.pem';
            $this->save();
        }

        return true;
    }

    public function getDomains(): array
    {
        if (! empty($this->domains) && is_array($this->domains)) {
            return $this->domains;
        }

        $this->domains = [$this->site->domain];
        $this->save();

        return $this->domains;
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }
}
