<?php

namespace App\SiteFeatures;

use App\Models\Site;

abstract class Action implements ActionInterface
{
    public function __construct(public Site $site) {}
}
