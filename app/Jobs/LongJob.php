<?php

namespace App\Jobs;

trait LongJob
{
    public int $timeout = 600;
}
