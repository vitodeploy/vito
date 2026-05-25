<?php

namespace App\Services;

use App\DTOs\ServiceLog;

interface HasLogs
{
    /**
     * @return array<int, ServiceLog>
     */
    public function logs(): array;
}
