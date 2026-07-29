<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised when discovered networks were read from the provider but could not be written. The
 * sweep continues so one bad network cannot suppress pruning for the rest, and this is thrown
 * at the end so the job reports failure rather than a silent partial sync. Carries no message
 * from the underlying database error, which can echo back the values it was given.
 */
class PrivateNetworkPersistError extends Exception
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $networks,
    ) {
        parent::__construct(sprintf(
            '%d discovered network(s) could not be reconciled for project %d.',
            $this->networks,
            $this->projectId,
        ));
    }
}
