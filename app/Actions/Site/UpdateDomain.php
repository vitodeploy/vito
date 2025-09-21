<?php

namespace App\Actions\Site;

use App\Models\Service;
use App\Models\Site;
use App\Services\Webserver\Webserver;
use App\ValidationRules\DomainRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateDomain
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $this->validate($site, $input);

        $oldDomain = $site->domain;
        $newDomain = $input['domain'];

        // If domain hasn't changed, do nothing
        if ($oldDomain === $newDomain) {
            return;
        }

        // Check if site is ready for domain change
        if (! $site->isReady()) {
            throw new \Exception('Cannot change domain while site is not ready.');
        }

        // Check if modern deployment is enabled - it would break the system
        if ($site->modernDeploymentEnabled()) {
            throw new \Exception('Cannot change domain while modern deployment is enabled. Please disable modern deployment first, change the domain, then re-enable it.');
        }

        // Update the domain and path in the database
        $site->domain = $newDomain;
        $site->path = '/home/'.$site->user.'/'.$newDomain;
        $site->save();

        // Move the physical directory on the server
        $this->moveSiteDirectory($site, $oldDomain, $newDomain);

        // Update webserver configuration files
        $this->updateWebserverConfig($site, $oldDomain, $newDomain);

        // Recreate site level workers to pick up the new site path
        $this->recreateWorkers($site);
    }

    protected function moveSiteDirectory(Site $site, string $oldDomain, string $newDomain): void
    {
        $oldPath = '/home/'.$site->user.'/'.$oldDomain;
        $newPath = '/home/'.$site->user.'/'.$newDomain;

        // Move the directory from old path to new path
        $site->server->ssh()->exec(
            "sudo mv {$oldPath} {$newPath}",
            'move-site-directory',
            $site->id
        );
    }

    protected function updateWebserverConfig(Site $site, string $oldDomain, string $newDomain): void
    {
        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserver */
        $webserver = $service->handler();

        // Move and update the virtual host file with new domain
        $this->moveAndUpdateVirtualHost($site, $oldDomain, $newDomain, $webserver);
    }

    protected function moveAndUpdateVirtualHost(Site $site, string $oldDomain, string $newDomain, Webserver $webserver): void
    {
        $webserverId = $webserver::id();

        if ($webserverId === 'nginx') {
            // Move the old file to the new domain name
            $site->server->ssh()->exec(
                "sudo mv /etc/nginx/sites-available/{$oldDomain} /etc/nginx/sites-available/{$newDomain}",
                'move-vhost-file',
                $site->id
            );

            // Update the symlink if it exists
            $site->server->ssh()->exec(
                "sudo ln -sf /etc/nginx/sites-available/{$newDomain} /etc/nginx/sites-enabled/{$newDomain}",
                'update-vhost-symlink',
                $site->id
            );

            // Remove old symlink if it exists
            $site->server->ssh()->exec(
                "sudo rm -f /etc/nginx/sites-enabled/{$oldDomain}",
                'remove-old-symlink',
                $site->id
            );

        } elseif ($webserverId === 'caddy') {
            // Move the old file to the new domain name
            $site->server->ssh()->exec(
                "sudo mv /etc/caddy/sites-available/{$oldDomain} /etc/caddy/sites-available/{$newDomain}",
                'move-vhost-file',
                $site->id
            );
        }

        // Determine which blocks to regenerate based on site type
        $regenerateBlocks = ['core', 'force-ssl'];

        // Add load balancer blocks if this is a load balancer site
        if ($site->type === 'load-balancer') {
            $regenerateBlocks[] = 'load-balancer-upstream';
            $regenerateBlocks[] = 'load-balancer';
        }

        // Update the content of the virtual host file with the new domain
        $webserver->updateVHost($site, regenerate: $regenerateBlocks, restart: true);
    }

    protected function recreateWorkers(Site $site): void
    {
        if ($site->workers->isEmpty()) {
            return;
        }

        /** @var \App\Models\Service $service */
        $service = $site->server->processManager();

        /** @var \App\Services\ProcessManager\ProcessManager $processManager */
        $processManager = $service->handler();

        // Restart all workers for this site
        foreach ($site->workers as $worker) {
            try {
                $processManager->delete($worker->id, $worker->site_id);
                $processManager->create(
                    $worker->id,
                    $worker->command,
                    $worker->user,
                    $worker->auto_start,
                    $worker->auto_restart,
                    $worker->numprocs,
                    $worker->getLogFile(),
                    $worker->site->path,
                    $worker->site_id
                );
            } catch (\Throwable $e) {
                // Log the error but continue with other workers
                \Illuminate\Support\Facades\Log::error("Failed to restart worker {$worker->id}: ".$e->getMessage());
            }
        }
    }

    protected function validate(Site $site, array $input): void
    {
        Validator::make($input, [
            'domain' => [
                'required',
                'string',
                new DomainRule,
                Rule::unique('sites', 'domain')
                    ->where('server_id', $site->server_id)
                    ->ignore($site->id),
            ],
        ])->validate();

        // Additional validation to ensure site is ready
        if (! $site->isReady()) {
            $validator = validator([], []);
            $validator->errors()->add('domain', 'Cannot change domain while site is not ready.');
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}
