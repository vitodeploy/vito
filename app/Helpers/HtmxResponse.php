<?php

namespace App\Helpers;

use Illuminate\Http\Response;

class HtmxResponse extends Response
{
    public function redirect(string $redirect): self
    {
        $this->header('HX-Redirect', $redirect);

        return $this;
    }

    public function back(): self
    {
        return $this->redirect(back()->getTargetUrl());
    }

    public function refresh(): self
    {
        $this->header('HX-Refresh', true);

        return $this;
    }

    public function location(string $location): self
    {
        $this->header('HX-Location', $location);

        return $this;
    }
}
