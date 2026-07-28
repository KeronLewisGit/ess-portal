<?php

namespace App\Mail;

use App\Models\LetterRequest;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LetterRequestDecisionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public LetterRequest $letterRequest) {}

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', (string) config('app.name'));
        $outcome = $this->letterRequest->status->label();

        return new Envelope(
            subject: "{$company}: your letter request {$this->letterRequest->reference_number} was {$outcome}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.letter-request-decision',
            with: [
                'companyName' => Setting::get('company_name', (string) config('app.name')),
                'request' => $this->letterRequest,
                'url' => route('letter-requests.show', $this->letterRequest),
            ],
        );
    }
}
