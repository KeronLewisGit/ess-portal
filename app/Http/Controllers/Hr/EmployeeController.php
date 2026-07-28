<?php

namespace App\Http\Controllers\Hr;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employees) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with('department')
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('employment_status', $request->string('status')->toString()))
            ->when($request->filled('department'), fn ($q) => $q->where('department_id', $request->integer('department')))
            ->orderBy('employee_code')
            ->paginate(15)
            ->withQueryString();

        return view('hr.employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'statuses' => EmploymentStatus::cases(),
            'filters' => $request->only(['q', 'status', 'department']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('hr.employees.create', $this->formData());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->employees->create($request->validated());

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('status', 'employee-created');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['department', 'manager', 'user']);

        return view('hr.employees.show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('hr.employees.edit', array_merge($this->formData(), [
            'employee' => $employee,
        ]));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employees->update($employee, $request->validated());

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('status', 'employee-updated');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()
            ->route('hr.employees.index')
            ->with('status', 'employee-deleted');
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $this->authorize('manage', Employee::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $count = $this->employees->bulkDeactivate($validated['ids']);

        return redirect()
            ->route('hr.employees.index')
            ->with('status', "Deactivated {$count} employee(s).");
    }

    /**
     * Shared create/edit form select options.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'managers' => Employee::orderBy('employee_code')->get(['id', 'employee_code', 'first_name', 'last_name']),
            'employmentTypes' => EmploymentType::cases(),
            'employmentStatuses' => EmploymentStatus::cases(),
            'payFrequencies' => PayFrequency::cases(),
        ];
    }
}
