<?php

namespace App\Contracts;

interface Notification
{
    public function subject(): string;

    public function message(bool $mail = false): mixed;
}
