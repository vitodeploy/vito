<?php

namespace App\Exceptions;

use Exception;

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
