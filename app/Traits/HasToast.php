<?php

namespace App\Traits;

use App\Helpers\Toast;

trait HasToast
{
    public function toast(): Toast
    {
        return new Toast();
    }
}
