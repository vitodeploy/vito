<?php

namespace App\Jobs\Redirect;

use App\Jobs\Job;
use App\Models\Redirect;

class AddToServer extends Job
{
    protected Redirect $redirect;

    public function __construct(Redirect $redirect)
    {
        $this->redirect = $redirect;
    }

    public function handle(): void
    {
        /** @var array $redirects */
        $redirects = Redirect::query()->where('site_id', $this->redirect->site_id)->get();
        $this->redirect->site->server->webserver()->handler()->updateRedirects($this->redirect->site, $redirects);
        $this->redirect->status = 'ready';
        $this->redirect->save();
    }

    public function failed(): void
    {
        $this->redirect->status = 'failed';
        $this->redirect->delete();
    }
}
