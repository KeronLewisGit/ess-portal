<?php

namespace App\Jobs;

use App\Mail\LetterReadyMail;
use App\Models\LetterRequest;
use App\Models\User;
use App\Services\LetterPdfService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Renders the PDF for a freshly approved request, off the HR request cycle so
 * a slow render never blocks the approval click.
 */
class GenerateLetterPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public LetterRequest $letterRequest,
        public ?int $issuedByUserId = null,
    ) {}

    public function handle(LetterPdfService $service): void
    {
        // The service is idempotent, so a retry after a partial failure
        // returns the existing letter instead of minting a second one.
        $letter = $service->issue(
            $this->letterRequest,
            $this->issuedByUserId !== null ? User::find($this->issuedByUserId) : null,
        );

        $email = $this->letterRequest->employee?->work_email;

        if ($email !== null) {
            Mail::to($email)->queue(new LetterReadyMail($letter->loadMissing('letterRequest.letterType')));
        }
    }

    /**
     * Deduplicate: only one generation job per request may be queued at once.
     */
    public function uniqueId(): string
    {
        return (string) $this->letterRequest->id;
    }
}
