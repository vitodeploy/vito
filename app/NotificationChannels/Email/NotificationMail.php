<?php

namespace App\NotificationChannels\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(string $subject, public string $text)
    {
        $this->subject = $subject;
    }

    public function build(): self
    {
        return $this->html($this->text);
    }
}
