<?php

namespace App\Http\Controllers\Hr;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ProvisionUserRequest;
use App\Models\Employee;
use App\Services\UserProvisioningService;
use Illuminate\Http\RedirectResponse;

class EmployeeUserController extends Controller
{
    public function __construct(private readonly UserProvisioningService $provisioning) {}

    /**
     * Create (or re-invite) a login account for an employee and email the
     * set-password invitation.
     */
    public function store(ProvisionUserRequest $request, Employee $employee): RedirectResponse
    {
        $role = Role::from($request->validated('role'));

        $this->provisioning->provision($employee, $role);

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('status', 'invitation-sent');
    }
}
