<?php

namespace App\Models;

use Database\Factories\GithubAppFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $app_id
 * @property string $app_slug
 * @property string $name
 * @property string $client_id
 * @property string $client_secret
 * @property string $webhook_secret
 * @property string $private_key
 * @property ?string $html_url
 */
class GithubApp extends AbstractModel
{
    /** @use HasFactory<GithubAppFactory> */
    use HasFactory;

    protected $table = 'github_app';

    protected $fillable = [
        'app_id',
        'app_slug',
        'name',
        'client_id',
        'client_secret',
        'webhook_secret',
        'private_key',
        'html_url',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'client_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'private_key' => 'encrypted',
    ];

    public static function current(): ?self
    {
        return self::query()->first();
    }
}
