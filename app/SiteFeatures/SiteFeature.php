<?php

namespace App\SiteFeatures;

use App\Models\Site;

abstract class SiteFeature implements SiteFeatureInterface
{
    public function __construct(public Site $site) {}
}
