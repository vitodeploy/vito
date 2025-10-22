<?php

namespace App\Actions\Redirect;

use App\Enums\RedirectStatus;
use App\Jobs\Redirect\RedirectDeleteJob;
use App\Models\Redirect;
use App\Models\Site;

class DeleteRedirect
{
    public function delete(Site $site, Redirect $redirect): void
    {
        $redirect->status = RedirectStatus::DELETING;
        $redirect->save();

        dispatch(new RedirectDeleteJob($site, $redirect))->onQueue('ssh');
    }
}
