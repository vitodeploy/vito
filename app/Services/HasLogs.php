<?php

namespace App\Services;

interface HasLogs
{
    /**
     * @return array<int, ServiceLog>
     */
    public function logs(): array;
}
