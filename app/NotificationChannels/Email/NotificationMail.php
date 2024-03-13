<?php

namespace App\NotificationChannels\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $text;

    public function __construct(string $subject, string $text)
    {
        $this->subject = $subject;
        $this->text = $text;
    }

    public function build(): self
    {
        return $this->html($this->text);
    }
}
