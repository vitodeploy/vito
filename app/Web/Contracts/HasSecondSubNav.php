<?php

namespace App\Web\Contracts;

interface HasSecondSubNav
{
    /**
     * @return array<mixed>
     */
    public function getSecondSubNavigation(): array;
}
