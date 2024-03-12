<?php

namespace App\Models;

use App\SourceControlProviders\SourceControlProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $provider
 * @property ?string $profile
 * @property ?string $url
 * @property string $access_token
 */
class SourceControl extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'profile',
        'url',
        'access_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
    ];

    public function provider(): SourceControlProvider
    {
        $providerClass = config('core.source_control_providers_class')[$this->provider];

        return new $providerClass($this);
    }

    public function getRepo(?string $repo = null): ?array
    {
        return $this->provider()->getRepo($repo);
    }
}
