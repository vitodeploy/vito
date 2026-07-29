<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised at the provider boundary so no upstream exception — which may carry
 * credentials in its message or trace arguments — reaches the queue's failed-job
 * serialisation or the log.
 */
class PrivateNetworkSyncError extends Exception
{
    public function __construct(
        public readonly int $serverProviderId,
        public readonly string $provider,
        public readonly string $profile,
        public readonly ?int $status = null,
        public readonly ?string $region = null,
    ) {
        parent::__construct(sprintf(
            'Could not read private networks from %s connection "%s"%s%s.',
            $this->provider,
            $this->profile,
            $this->region !== null ? ' in region '.$this->region : '',
            $this->status !== null ? ' (HTTP '.$this->status.')' : '',
        ));
    }

    public function isPermissionError(): bool
    {
        return in_array($this->status, [401, 403], true);
    }
}
