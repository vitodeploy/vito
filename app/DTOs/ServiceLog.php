<?php

namespace App\DTOs;

final class ServiceLog
{
    public const SOURCE_FILE = 'file';

    public const SOURCE_JOURNAL = 'journal';

    public function __construct(
        public string $key,
        public string $serviceLabel,
        public string $label,
        public string $source,
        public string $target,
    ) {}

    public function displayTarget(): string
    {
        if ($this->source === self::SOURCE_JOURNAL) {
            return 'journal: '.$this->target;
        }

        return $this->target;
    }
}
