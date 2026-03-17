<?php

namespace App\Actions\SSL;

use App\Models\Ssl;

class ActivateSSL
{
    public function activate(Ssl $ssl): void
    {
        $parent = $ssl->parent();
        $parent->ssls()->update(['is_active' => false]);
        $ssl->is_active = true;
        $ssl->save();

        $parent->refreshVhost();
    }
}
