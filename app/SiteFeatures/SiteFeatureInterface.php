<?php

namespace App\SiteFeatures;

interface SiteFeatureInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<int, ActionInterface>
     */
    public function actions(): array;
}
