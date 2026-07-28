<?php

namespace App\Http\Controllers;

use App\Models\IssuedLetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves issued letter PDFs off the private disk.
 *
 * Two gates, deliberately both: the URL must carry a valid unexpired
 * signature (`signed` middleware on the route) AND the signed-in user must
 * pass the policy. A leaked link alone is not enough, and neither is being
 * signed in without a link.
 */
class LetterDownloadController extends Controller
{
    /**
     * Mint a short-lived signed URL and redirect to it, so the download link
     * itself is never long-lived or shareable beyond its expiry.
     */
    public function redirect(IssuedLetter $issuedLetter): RedirectResponse
    {
        $this->authorize('download', $issuedLetter);

        return redirect()->to(URL::signedRoute(
            'letters.download',
            ['issuedLetter' => $issuedLetter->id],
            now()->addMinutes((int) config('ess.signed_url_expiry_minutes', 15)),
        ));
    }

    public function download(IssuedLetter $issuedLetter): StreamedResponse
    {
        $this->authorize('download', $issuedLetter);

        $disk = Storage::disk('private');

        abort_unless($disk->exists($issuedLetter->file_path), 404);

        // The stored hash is the authority on what we issued; if the bytes on
        // disk no longer match, refuse rather than serve a tampered document.
        abort_unless($issuedLetter->fileIsIntact(), 409, 'This document failed its integrity check.');

        return $disk->download(
            $issuedLetter->file_path,
            $issuedLetter->reference_number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
