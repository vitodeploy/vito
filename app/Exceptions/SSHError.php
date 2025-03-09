<?php

namespace App\Exceptions;

use App\Models\ServerLog;
use Exception;
use Throwable;

class SSHError extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, protected ?ServerLog $log = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getLog(): ?ServerLog
    {
        return $this->log;
    }
}
