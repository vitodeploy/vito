<?php

namespace App\Exceptions;

use App\Models\SourceControl;
use Exception;

class SourceControlIsNotConnected extends Exception
{
    public function __construct(protected SourceControl|string $sourceControl, string $message = null)
    {
        parent::__construct($message ?? 'Source control is not connected');
    }
}
