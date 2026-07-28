<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\LetterType\StoreLetterTypeRequest;
use App\Http\Requests\LetterType\UpdateLetterTypeRequest;
use App\Models\LetterType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LetterTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LetterType::class);

        return view('hr.letter-types.index', [
            'letterTypes' => LetterType::query()
                ->withCount('letterRequests')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LetterType::class);

        return view('hr.letter-types.create', [
            'letterType' => new LetterType(['is_active' => true, 'reference_prefix' => 'LTR']),
        ]);
    }

    public function store(StoreLetterTypeRequest $request): RedirectResponse
    {
        LetterType::create($request->validated());

        return redirect()
            ->route('hr.letter-types.index')
            ->with('status', 'Letter template created.');
    }

    public function edit(LetterType $letterType): View
    {
        $this->authorize('update', $letterType);

        return view('hr.letter-types.edit', [
            'letterType' => $letterType,
        ]);
    }

    public function update(UpdateLetterTypeRequest $request, LetterType $letterType): RedirectResponse
    {
        $letterType->update($request->validated());

        return redirect()
            ->route('hr.letter-types.index')
            ->with('status', 'Letter template updated.');
    }

    /**
     * Only ever succeeds for a template nothing has used — the policy checks
     * that. Templates in use are deactivated instead.
     */
    public function destroy(LetterType $letterType): RedirectResponse
    {
        $this->authorize('delete', $letterType);

        $letterType->delete();

        return redirect()
            ->route('hr.letter-types.index')
            ->with('status', 'Letter template deleted.');
    }
}
