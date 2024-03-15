<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;

class DeleteSourceControl
{
    public function delete(SourceControl $sourceControl): void
    {
        if ($sourceControl->sites()->exists()) {
            throw new \Exception('This source control is being used by a site.');
        }

        $sourceControl->delete();
    }
}
