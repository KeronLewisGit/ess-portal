<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterRequest\StoreLetterRequestRequest;
use App\Http\Requests\LetterRequest\UpdateLetterRequestRequest;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Services\LetterRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The employee-facing side of letter requests. Every query is scoped to the
 * signed-in user's own employee_id; HR works the queue elsewhere.
 */
class LetterRequestController extends Controller
{
    public function __construct(private readonly LetterRequestService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', LetterRequest::class);

        return view('letter-requests.index', [
            'requests' => LetterRequest::query()
                ->forEmployee(auth()->user()->employee_id)
                ->with('letterType')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LetterRequest::class);

        return view('letter-requests.create', [
            'letterTypes' => $this->activeTypes(),
        ]);
    }

    public function store(StoreLetterRequestRequest $request): RedirectResponse
    {
        $letterRequest = $this->service->createDraft(
            auth()->user()->employee,
            $request->safe()->only(['letter_type_id', 'addressed_to', 'purpose', 'include_salary']),
        );

        // "Save draft" keeps it editable; the default action submits it.
        if ($request->input('action') === 'draft') {
            return redirect()
                ->route('letter-requests.show', $letterRequest)
                ->with('status', 'Draft saved.');
        }

        return $this->submit($letterRequest);
    }

    public function show(LetterRequest $letterRequest): View
    {
        $this->authorize('view', $letterRequest);

        return view('letter-requests.show', [
            'request' => $letterRequest->load(['letterType', 'employee', 'decidedBy', 'issuedLetter']),
        ]);
    }

    public function edit(LetterRequest $letterRequest): View
    {
        $this->authorize('update', $letterRequest);

        return view('letter-requests.edit', [
            'request' => $letterRequest,
            'letterTypes' => $this->activeTypes(),
        ]);
    }

    public function update(UpdateLetterRequestRequest $request, LetterRequest $letterRequest): RedirectResponse
    {
        $this->service->updateDraft(
            $letterRequest,
            $request->safe()->only(['letter_type_id', 'addressed_to', 'purpose', 'include_salary']),
        );

        if ($request->input('action') === 'draft') {
            return redirect()
                ->route('letter-requests.show', $letterRequest)
                ->with('status', 'Draft updated.');
        }

        return $this->submit($letterRequest);
    }

    /**
     * Submit a saved draft for approval.
     */
    public function submit(LetterRequest $letterRequest): RedirectResponse
    {
        $this->authorize('submit', $letterRequest);

        $letterRequest = $this->service->submit($letterRequest->load('letterType'));

        return redirect()
            ->route('letter-requests.show', $letterRequest)
            ->with('status', "Request {$letterRequest->reference_number} submitted for approval.");
    }

    /**
     * Withdraw one's own draft or pending request.
     */
    public function cancel(LetterRequest $letterRequest): RedirectResponse
    {
        $this->authorize('cancel', $letterRequest);

        $this->service->cancel($letterRequest);

        return redirect()
            ->route('letter-requests.index')
            ->with('status', 'Request cancelled.');
    }

    private function activeTypes()
    {
        return LetterType::active()->orderBy('name')->get();
    }
}
