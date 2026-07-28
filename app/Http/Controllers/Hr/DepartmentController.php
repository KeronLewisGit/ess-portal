<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        return view('hr.departments.index', [
            'departments' => Department::query()
                ->withCount('employees')
                ->with('head')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('hr.departments.create', [
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()
            ->route('hr.departments.index')
            ->with('status', 'department-created');
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('hr.departments.edit', [
            'department' => $department,
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()
            ->route('hr.departments.index')
            ->with('status', 'department-updated');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return redirect()
            ->route('hr.departments.index')
            ->with('status', 'department-deleted');
    }

    private function employeeOptions()
    {
        return Employee::orderBy('employee_code')->get(['id', 'employee_code', 'first_name', 'last_name']);
    }
}
