<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentSignupVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $verificationCode)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your AiStar Prep account email');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-signup-verification',
            with: ['verificationCode' => $this->verificationCode],
        );
    }
}
