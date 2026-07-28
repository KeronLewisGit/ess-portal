<?php

namespace App\Mail;

use App\Models\IssuedLetter;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LetterReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public IssuedLetter $issuedLetter) {}

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', (string) config('app.name'));

        return new Envelope(
            subject: "{$company}: your letter {$this->issuedLetter->reference_number} is ready",
        );
    }

    /**
     * The PDF is deliberately NOT attached — email is not a secure channel
     * and the letter may state salary. The employee signs in to download it.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.letter-ready',
            with: [
                'companyName' => Setting::get('company_name', (string) config('app.name')),
                'letter' => $this->issuedLetter,
                'url' => route('letter-requests.show', $this->issuedLetter->letter_request_id),
            ],
        );
    }
}
