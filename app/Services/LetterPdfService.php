<?php

namespace App\Services;

use App\Enums\LetterRequestStatus;
use App\Models\IssuedLetter;
use App\Models\LetterRequest;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Renders an approved letter request to PDF and stores it immutably on the
 * private disk.
 */
class LetterPdfService
{
    private const STORAGE_DIR = 'letters';

    public function __construct(private readonly LetterTemplateRenderer $renderer) {}

    /**
     * Generate and store the PDF for an approved request.
     *
     * Idempotent: a request that already has an issued letter is returned
     * as-is rather than re-rendered, so a retried queue job can never mint a
     * second document for the same request.
     */
    public function issue(LetterRequest $request, ?User $issuedBy = null): IssuedLetter
    {
        if ($request->issuedLetter !== null) {
            return $request->issuedLetter;
        }

        if (! in_array($request->status, [LetterRequestStatus::Approved, LetterRequestStatus::Issued], true)) {
            throw new RuntimeException('Only an approved request can be issued.');
        }

        $request->loadMissing(['employee.department', 'letterType']);

        $values = $this->renderer->values($request);
        $token = Str::random(48);

        $pdf = Pdf::loadView('letters.pdf', [
            'body' => $this->renderer->render($request->letterType->body_template, $values),
            'values' => $values,
            'request' => $request,
            'companyName' => $values['company_name'],
            'companyAddress' => $values['company_address'],
            'footerText' => (string) Setting::get('letter_footer_text', ''),
            'logo' => $this->embeddedImage(Setting::get('company_logo_path')),
            'signature' => $this->embeddedImage(Setting::get('signature_image_path')),
            'verificationUrl' => route('letters.verify', $token),
        ])->setPaper('a4');

        $output = $pdf->output();
        $path = self::STORAGE_DIR.'/'.$request->reference_number.'-'.Str::random(8).'.pdf';

        Storage::disk('private')->put($path, $output);

        return DB::transaction(function () use ($request, $path, $output, $token, $values, $issuedBy) {
            $letter = new IssuedLetter;

            $letter->forceFill([
                'letter_request_id' => $request->id,
                'reference_number' => $request->reference_number,
                'verification_token' => $token,
                'file_path' => $path,
                'file_hash' => hash('sha256', $output),
                'file_size' => strlen($output),
                'snapshot' => $this->renderer->snapshot($values),
                'issued_by' => $issuedBy?->id,
                'issued_at' => now(),
            ])->save();

            $request->forceFill(['status' => LetterRequestStatus::Issued])->save();

            return $letter;
        });
    }

    /**
     * Revoke an issued letter. The PDF is kept — the employee may already
     * hold a copy, and the audit trail must show what was issued — but the
     * public verification page now reports it as revoked.
     */
    public function revoke(IssuedLetter $letter, User $by, string $reason): IssuedLetter
    {
        if ($letter->isRevoked()) {
            throw new RuntimeException('This letter has already been revoked.');
        }

        $letter->forceFill([
            'revoked_at' => now(),
            'revoked_by' => $by->id,
            'revoked_reason' => $reason,
        ])->save();

        return $letter;
    }

    /**
     * Read a letterhead asset off the private disk as a data URI so dompdf
     * never has to fetch a remote (or local-file) URL — remote file access
     * stays disabled.
     */
    private function embeddedImage(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($path)) {
            return null;
        }

        $mime = $disk->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }
}
