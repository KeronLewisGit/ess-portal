<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LetterRequest;
use App\Models\Payslip;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LetterRequestPolicy;
use App\Policies\PayslipPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registered explicitly (rather than relying on auto-discovery) so
        // the authorisation surface is visible in one place.
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(LetterRequest::class, LetterRequestPolicy::class);
        Gate::policy(Payslip::class, PayslipPolicy::class);

        // Area-level gates used by navigation and route middleware.
        Gate::define('access-hr-area', fn (User $user) => $user->isHrStaff());
        Gate::define('manage-settings', fn (User $user) => $user->isSuperAdmin());
    }
}
