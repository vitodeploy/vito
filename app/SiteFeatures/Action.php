<?php

namespace App\SiteFeatures;

use App\DTOs\DynamicForm;
use App\Models\Site;

abstract class Action implements ActionInterface
{
    public function __construct(public Site $site) {}

    public function form(): ?DynamicForm
    {
        return null;
    }
}
