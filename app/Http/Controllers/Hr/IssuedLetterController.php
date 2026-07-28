<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\IssuedLetter;
use App\Services\LetterPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IssuedLetterController extends Controller
{
    public function __construct(private readonly LetterPdfService $service) {}

    /**
     * Revoke an issued letter — e.g. it was issued in error, or the facts it
     * states no longer hold. The PDF is kept (the holder may already have a
     * copy) but public verification now reports it as revoked.
     */
    public function revoke(Request $request, IssuedLetter $issuedLetter): RedirectResponse
    {
        $this->authorize('revoke', $issuedLetter);

        $validated = $request->validate([
            'revoked_reason' => ['required', 'string', 'max:255'],
        ], [
            'revoked_reason.required' => 'Please give a reason for revoking this letter.',
        ]);

        $this->service->revoke($issuedLetter, $request->user(), $validated['revoked_reason']);

        return redirect()
            ->route('hr.approvals.show', $issuedLetter->letter_request_id)
            ->with('status', "Letter {$issuedLetter->reference_number} revoked.");
    }
}
