<?php

namespace App\Services;

use App\Enums\LetterRequestStatus;
use App\Mail\LetterRequestDecisionMail;
use App\Models\Employee;
use App\Models\LetterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Owns the letter request status workflow. Every transition goes through
 * here so the guards live in one place rather than being re-derived in each
 * controller: policies decide WHO may act, this decides whether the
 * transition is legal at all.
 */
class LetterRequestService
{
    public function __construct(private readonly DocumentSequenceService $sequences) {}

    /**
     * Create a draft for an employee. Nothing is submitted yet and no
     * reference number is burned.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDraft(Employee $employee, array $data): LetterRequest
    {
        $request = new LetterRequest($data);

        // employee_id and status are never mass assignable.
        $request->forceFill([
            'employee_id' => $employee->id,
            'status' => LetterRequestStatus::Draft,
        ])->save();

        return $request;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(LetterRequest $request, array $data): LetterRequest
    {
        $this->assert($request->status->isEditable(), 'Only a draft can be edited.');

        $request->update($data);

        return $request;
    }

    /**
     * Submit a draft for HR approval, assigning its reference number.
     */
    public function submit(LetterRequest $request): LetterRequest
    {
        $this->assert($request->status->isEditable(), 'Only a draft can be submitted.');

        return DB::transaction(function () use ($request) {
            // Assigned once, at submission, from the type's own prefix — an
            // abandoned draft never consumes a number.
            $request->forceFill([
                'reference_number' => $request->reference_number
                    ?? $this->sequences->next($request->letterType->reference_prefix),
                'status' => LetterRequestStatus::Submitted,
                'submitted_at' => now(),
            ])->save();

            return $request;
        });
    }

    public function approve(LetterRequest $request, User $decider, ?string $notes = null): LetterRequest
    {
        $this->assert($request->status->isPending(), 'Only a pending request can be approved.');

        return $this->decide($request, $decider, LetterRequestStatus::Approved, $notes);
    }

    public function reject(LetterRequest $request, User $decider, string $reason): LetterRequest
    {
        $this->assert($request->status->isPending(), 'Only a pending request can be rejected.');

        return $this->decide($request, $decider, LetterRequestStatus::Rejected, $reason);
    }

    /**
     * Employee withdraws their own request.
     */
    public function cancel(LetterRequest $request): LetterRequest
    {
        $this->assert($request->status->isCancellable(), 'This request can no longer be cancelled.');

        $request->forceFill(['status' => LetterRequestStatus::Cancelled])->save();

        return $request;
    }

    private function decide(
        LetterRequest $request,
        User $decider,
        LetterRequestStatus $status,
        ?string $notes,
    ): LetterRequest {
        $request->forceFill([
            'status' => $status,
            'decided_by' => $decider->id,
            'decided_at' => now(),
            'decision_notes' => $notes,
        ])->save();

        $this->notify($request);

        return $request;
    }

    /**
     * Tell the employee the outcome. Queued so a slow mail relay can't fail
     * the HR request, and never fatal — a mail failure must not roll back a
     * recorded decision.
     */
    private function notify(LetterRequest $request): void
    {
        $email = $request->employee?->work_email;

        if ($email === null) {
            return;
        }

        Mail::to($email)->queue(new LetterRequestDecisionMail($request->fresh('letterType')));
    }

    private function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }
}
