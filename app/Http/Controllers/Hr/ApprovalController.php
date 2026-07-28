<?php

namespace App\Http\Controllers\Hr;

use App\Enums\LetterRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\LetterRequest;
use App\Services\LetterRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The HR approval queue for letter requests.
 *
 * Any HR staff may work the queue, but approving a request that discloses
 * salary is restricted to HR admins by LetterRequestPolicy::approve().
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly LetterRequestService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LetterRequest::class);
        $this->authorize('access-hr-area');

        $status = $request->string('status')->toString();

        $query = LetterRequest::query()
            ->with(['employee.department', 'letterType', 'decidedBy']);

        if (in_array($status, LetterRequestStatus::values(), true)) {
            $query->where('status', $status)->latest('updated_at');
        } else {
            // Default view is the work to be done: pending, oldest first.
            $status = LetterRequestStatus::Submitted->value;
            $query->pending();
        }

        return view('hr.approvals.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'pendingCount' => LetterRequest::query()->pending()->count(),
        ]);
    }

    public function show(LetterRequest $letterRequest): View
    {
        $this->authorize('view', $letterRequest);
        $this->authorize('access-hr-area');

        return view('hr.approvals.show', [
            'request' => $letterRequest->load(['employee.department', 'letterType', 'decidedBy']),
        ]);
    }

    public function approve(Request $request, LetterRequest $letterRequest): RedirectResponse
    {
        $this->authorize('approve', $letterRequest);

        $validated = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->approve($letterRequest, $request->user(), $validated['decision_notes'] ?? null);

        return redirect()
            ->route('hr.approvals.index')
            ->with('status', "Request {$letterRequest->reference_number} approved.");
    }

    public function reject(Request $request, LetterRequest $letterRequest): RedirectResponse
    {
        $this->authorize('reject', $letterRequest);

        // A rejection must always carry a reason — the employee is told why.
        $validated = $request->validate([
            'decision_notes' => ['required', 'string', 'max:2000'],
        ], [
            'decision_notes.required' => 'Please give a reason for the rejection.',
        ]);

        $this->service->reject($letterRequest, $request->user(), $validated['decision_notes']);

        return redirect()
            ->route('hr.approvals.index')
            ->with('status', "Request {$letterRequest->reference_number} rejected.");
    }
}
