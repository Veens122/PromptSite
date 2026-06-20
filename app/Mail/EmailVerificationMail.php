<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationCode;
    public $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $verificationCode, $verificationUrl = null)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;

        // Use the provided URL, or generate one if null
        $this->verificationUrl = $verificationUrl ?? route('email.verify.link', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }



    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verification',
            with: [
                'user' => $this->user,
                'verificationCode' => $this->verificationCode,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}