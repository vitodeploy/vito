<?php

namespace App\Models;

use App\Enums\SslStatus;
use App\Jobs\Ssl\Deploy;
use App\Jobs\Ssl\Remove;
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
 * @property string $certs_directory_path
 * @property string $certificate_path
 * @property string $pk_path
 * @property string $ca_path
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
    ];

    protected $casts = [
        'site_id' => 'integer',
        'certificate' => 'encrypted',
        'pk' => 'encrypted',
        'ca' => 'encrypted',
        'expires_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getCertsDirectoryPathAttribute(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return '/etc/letsencrypt/live/'.$this->site->domain;
        }

        if ($this->type == 'custom') {
            return '/etc/ssl/'.$this->site->domain;
        }

        return '';
    }

    public function getCertificatePathAttribute(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return $this->certificate;
        }

        if ($this->type == 'custom') {
            return $this->certs_directory_path.'/cert.pem';
        }

        return '';
    }

    public function getPkPathAttribute(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return $this->pk;
        }

        if ($this->type == 'custom') {
            return $this->certs_directory_path.'/privkey.pem';
        }

        return '';
    }

    public function getCaPathAttribute(): ?string
    {
        if ($this->type == 'letsencrypt') {
            return $this->ca;
        }

        if ($this->type == 'custom') {
            return $this->certs_directory_path.'/fullchain.pem';
        }

        return '';
    }

    public function deploy(): void
    {
        dispatch(new Deploy($this))->onConnection('ssh');
    }

    public function remove(): void
    {
        $this->status = SslStatus::DELETING;
        $this->save();
        dispatch(new Remove($this))->onConnection('ssh');
    }

    public function validateSetup(string $result): bool
    {
        if (! Str::contains($result, 'Successfully received certificate')) {
            return false;
        }

        if ($this->type == 'letsencrypt') {
            $this->certificate = $this->certs_directory_path.'/fullchain.pem';
            $this->pk = $this->certs_directory_path.'/privkey.pem';
            $this->save();
        }

        return true;
    }
}
