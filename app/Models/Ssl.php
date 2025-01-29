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
 * @property string $ca_path
 * @property ?array $domains
 * @property int $log_id
 * @property string $email
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
    ];

    protected $casts = [
        'site_id' => 'integer',
        'certificate' => 'encrypted',
        'pk' => 'encrypted',
        'ca' => 'encrypted',
        'expires_at' => 'datetime',
        'domains' => 'array',
        'log_id' => 'integer',
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

    public function getCertsDirectoryPath(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return '/etc/letsencrypt/live/'.$this->site->domain;
        }

        if ($this->type == 'custom') {
            return '/etc/ssl/'.$this->site->domain;
        }

        return '';
    }

    public function getCertificatePath(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return $this->certificate;
        }

        if ($this->type == 'custom') {
            return $this->getCertsDirectoryPath().'/cert.pem';
        }

        return '';
    }

    public function getPkPath(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return $this->pk;
        }

        if ($this->type == 'custom') {
            return $this->getCertsDirectoryPath().'/privkey.pem';
        }

        return '';
    }

    public function getCaPath(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return $this->ca;
        }

        if ($this->type == 'custom') {
            return $this->getCertsDirectoryPath().'/fullchain.pem';
        }

        return '';
    }

    public function validateSetup(string $result): bool
    {
        if (! Str::contains($result, 'Successfully received certificate')) {
            return false;
        }

        if ($this->type == 'letsencrypt') {
            $this->certificate = $this->getCertsDirectoryPath().'/fullchain.pem';
            $this->pk = $this->getCertsDirectoryPath().'/privkey.pem';
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

    public function getEmailAttribute(?string $value): string
    {
        if ($value) {
            return $value;
        }

        return $this->site->server->creator->email;
    }
}
