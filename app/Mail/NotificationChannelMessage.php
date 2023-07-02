<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class NotificationChannelMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var mixed
     */
    public $text;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $text)
    {
        $this->subject = $subject;
        $this->text = $text;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->text instanceof MailMessage) {
            return $this->markdown('vendor.notifications.email', $this->text->data());
        }

        return $this->markdown('emails.notification-channel-message', [
            'subject' => $this->subject,
            'text' => $this->text,
        ]);
    }
}
