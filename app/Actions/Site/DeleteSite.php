<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Service;
use App\Models\Site;
use App\Services\PHP\PHP;
use App\Services\Webserver\Webserver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DeleteSite
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function delete(Site $site, array $input): void
    {
        $this->validate($site, $input);

        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserverHandler */
        $webserverHandler = $service->handler();
        $webserverHandler->deleteSite($site);

        if ($site->isIsolated()) {
            /** @var Service $phpService */
            $phpService = $site->server->php();
            /** @var PHP $php */
            $php = $phpService->handler();
            $php->removeFpmPool($site->user, $site->php_version, $site->id);

            $os = $site->server->os();
            $os->deleteIsolatedUser($site->user);
        }

        $site->delete();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Site $site, array $input): void
    {
        Validator::make($input, [
            'domain' => [
                'required',
                Rule::in($site->domain),
            ],
        ])->validate();
    }
}
