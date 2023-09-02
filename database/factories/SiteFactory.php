<?php

namespace Database\Factories;

use App\Enums\SiteType;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'type' => SiteType::LARAVEL,
            'domain' => 'test.com',
            'web_directory' => '/',
            'path' => '/home',
            'status' => 'ready',
            'progress' => '100',
            'php_version' => '8.2',
            'branch' => 'main',
        ];
    }
}
