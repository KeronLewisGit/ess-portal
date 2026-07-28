<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\IssuedLetter;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\Payslip;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\IssuedLetterPolicy;
use App\Policies\LetterRequestPolicy;
use App\Policies\LetterTypePolicy;
use App\Policies\PayslipPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(IssuedLetter::class, IssuedLetterPolicy::class);
        Gate::policy(LetterRequest::class, LetterRequestPolicy::class);
        Gate::policy(LetterType::class, LetterTypePolicy::class);
        Gate::policy(Payslip::class, PayslipPolicy::class);

        // Area-level gates used by navigation and route middleware.
        Gate::define('access-hr-area', fn (User $user) => $user->isHrStaff());
        Gate::define('manage-settings', fn (User $user) => $user->isSuperAdmin());

        $this->configureRateLimiting();
    }

    /**
     * Named limiters applied to the write endpoints. Limits are env-driven
     * via config/ess.php so they can be tuned per deployment.
     */
    private function configureRateLimiting(): void
    {
        // Caps how many letter requests one account can submit per day, so a
        // single employee can't flood the HR approval queue. Keyed by user
        // id (falling back to IP) rather than by IP alone, which would
        // penalise everyone behind a shared office NAT.
        RateLimiter::for('letter-requests', function (Request $request) {
            $perDay = (int) config('ess.rate_limits.letter_requests_per_day', 10);

            return Limit::perDay($perDay)->by($request->user()?->id ?: $request->ip());
        });

        // The public verification page is the only unauthenticated route, so
        // it is throttled per IP to blunt token guessing and scraping. Tokens
        // are 48 random characters, so this is defence in depth, not the
        // primary control.
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
