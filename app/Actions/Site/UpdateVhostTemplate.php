<?php

namespace App\Actions\Site;

use App\Actions\Webserver\GenerateCaddyConfig;
use App\Actions\Webserver\GenerateNginxConfig;
use App\Models\Site;

class UpdateVhostTemplate
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $validated = validator($input, [
            'template' => ['required', 'string', 'max:65535'],
        ])->validate();

        $site->vhost_template = $this->matchesDefault($site, $validated['template'])
            ? null
            : $validated['template'];
        $site->save();

        $site->webserver()->updateVHost($site);
    }

    private function matchesDefault(Site $site, string $template): bool
    {
        $generator = $site->webserver()::id() === 'caddy'
            ? app(GenerateCaddyConfig::class)
            : app(GenerateNginxConfig::class);

        return rtrim($template) === rtrim($generator->defaultTemplate());
    }
}
