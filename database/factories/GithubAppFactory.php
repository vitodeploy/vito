<?php

namespace Database\Factories;

use App\Models\GithubApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GithubApp>
 */
class GithubAppFactory extends Factory
{
    protected $model = GithubApp::class;

    public function definition(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $pem = '';
        if ($resource !== false) {
            openssl_pkey_export($resource, $pem);
        }

        return [
            'app_id' => $this->faker->unique()->numberBetween(100000, 999999),
            'app_slug' => 'vito-test-'.$this->faker->unique()->lexify('????'),
            'name' => 'Vito Test',
            'client_id' => 'Iv1.'.$this->faker->lexify('????????????????'),
            'client_secret' => $this->faker->sha1,
            'webhook_secret' => $this->faker->sha1,
            'private_key' => $pem,
            'html_url' => 'https://github.com/apps/vito-test',
        ];
    }
}
