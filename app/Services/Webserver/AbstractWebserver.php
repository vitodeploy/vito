<?php

namespace App\Services\Webserver;

use App\Models\Site;
use App\Services\AbstractService;
use Closure;
use InvalidArgumentException;

abstract class AbstractWebserver extends AbstractService implements Webserver
{
    public function creationRules(array $input): array
    {
        return [
            'name' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $webserverExists = $this->service->server->webserver();
                    if ($webserverExists) {
                        $fail('You already have a webserver service on the server.');
                    }
                },
            ],
        ];
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hasSite = $this->service->server->sites()
                        ->exists();
                    if ($hasSite) {
                        $fail('Cannot uninstall webserver while you have websites using it.');
                    }
                },
            ],
        ];
    }

    /**
     * @param  array<string, string>  $replace  replace blocks
     * @param  array<int, string>  $regenerate  regenerates the blocks
     * @param  array<string, string>  $append  appends to the blocks
     */
    protected function getUpdatedVHost(Site $site, string $vhost, array $replace = [], array $regenerate = [], array $append = []): string
    {
        foreach ($replace as $block => $replacement) {
            $vhost = preg_replace(
                '/#\['.$block.'](.*?)#\[\/'.$block.']/s',
                $replacement,
                $vhost
            );
        }

        foreach ($regenerate as $block) {
            $vhost = preg_replace(
                '/#\['.$block.'](.*?)#\[\/'.$block.']/s',
                $this->generateVhost($site, $block),
                $vhost
            );
        }

        foreach ($append as $block => $content) {
            /**
             * #[block]
             * content
             * content
             * content
             * content
             * --append-here--
             * #[/block]
             */
            $vhost = preg_replace(
                '/(#\['.$block.'](.*?)\n)(?=#\[\/'.$block.'])/s',
                "\$1$content\n",
                $vhost
            );
        }

        return $vhost;
    }

    protected function generateVhost(Site $site, ?string $block = null): string
    {
        $viewPath = 'ssh.services.webserver.'.$this::id().'.vhost-blocks.'.$block;
        if ($block) {
            if (! view()->exists($viewPath)) {
                throw new InvalidArgumentException("View for block '{$block}' does not exist.");
            }
            $vhost = view($viewPath, [
                'site' => $site,
            ]);
        } else {
            $vhost = $site->type()->vhost($this::id());
        }

        return format_nginx_config($vhost);
    }
}
