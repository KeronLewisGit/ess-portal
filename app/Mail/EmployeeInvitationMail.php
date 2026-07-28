<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $setupUrl,
    ) {}

    public function envelope(): Envelope
    {
        $company = Setting::get('company_name', (string) config('app.name'));

        return new Envelope(
            subject: "Your {$company} ESS Portal account",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.employee-invitation',
            with: [
                'companyName' => Setting::get('company_name', (string) config('app.name')),
                'userName' => $this->user->name,
                'setupUrl' => $this->setupUrl,
            ],
        );
    }
}
