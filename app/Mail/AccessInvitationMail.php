<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessInvitationMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public string $heading, public string $activationUrl) {}
    public function build(): self
    {
        return $this->subject($this->heading)->view('emails.access-invitation');
    }
}
