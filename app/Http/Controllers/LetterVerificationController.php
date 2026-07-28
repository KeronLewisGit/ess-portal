<?php

namespace App\Http\Controllers;

use App\Models\IssuedLetter;
use Illuminate\View\View;

/**
 * Public letter verification — the ONLY unauthenticated route in the portal
 * besides auth itself.
 *
 * A recipient (bank, embassy) opens the URL printed on the letter and is told
 * whether it is genuine. Disclosure is deliberately minimal: reference, the
 * employee's INITIALS, letter type and issue date. No full name, no job
 * title, no salary, and no way to download the PDF.
 */
class LetterVerificationController extends Controller
{
    public function show(string $token): View
    {
        $letter = IssuedLetter::query()
            ->where('verification_token', $token)
            ->with('letterRequest.letterType')
            ->first();

        // An unknown token renders the same "not found" page as a malformed
        // one — nothing here distinguishes "never existed" from "deleted".
        if ($letter === null) {
            return view('letters.verify', ['letter' => null, 'initials' => null]);
        }

        return view('letters.verify', [
            'letter' => $letter,
            // Snapshotted at issue time so a later name change doesn't
            // retroactively alter what the letter verifies as.
            'initials' => $this->initialsFrom($letter),
        ]);
    }

    private function initialsFrom(IssuedLetter $letter): string
    {
        $name = (string) ($letter->snapshot['employee_name'] ?? '');

        $initials = collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->map(fn (string $part) => strtoupper($part[0]))
            ->take(3)
            ->implode('.');

        return $initials === '' ? '—' : $initials.'.';
    }
}
