<?php

namespace App\Exceptions;

use App\Models\ServerLog;
use Exception;
use Throwable;

class SSHError extends Exception
{
    protected ?ServerLog $log;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?ServerLog $log = null)
    {
        $this->log = $log;

        parent::__construct($message, $code, $previous);
    }

    public function getLog(): ?ServerLog
    {
        return $this->log;
    }
}
