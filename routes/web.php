<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /*
     * Employee area — inner pages are Phase 3/5 deliverables; the routes
     * exist now so the navigation shell reflects the final structure.
     */
    Route::view('/requests', 'coming-soon', [
        'title' => 'My Letter Requests',
        'phase' => 'Phase 3 — Letter requests',
    ])->name('letter-requests.index');

    Route::view('/payslips', 'coming-soon', [
        'title' => 'My Payslips',
        'phase' => 'Phase 5 — Payslips',
    ])->name('payslips.index');

    /*
     * HR area — gated at area level by role middleware; record-level
     * authorisation is (and will remain) enforced by policies.
     */
    Route::prefix('hr')->name('hr.')
        ->middleware('role:hr_officer,hr_admin,super_admin')
        ->group(function () {
            Route::view('/approvals', 'coming-soon', [
                'title' => 'Approval Queue',
                'phase' => 'Phase 3 — Letter requests',
            ])->name('approvals.index');

            Route::view('/employees', 'coming-soon', [
                'title' => 'Employee Management',
                'phase' => 'Phase 2 — Employee master',
            ])->name('employees.index');

            Route::view('/letter-types', 'coming-soon', [
                'title' => 'Letter Templates',
                'phase' => 'Phase 3 — Letter requests',
            ])->name('letter-types.index');

            Route::view('/payslips', 'coming-soon', [
                'title' => 'Payslip Management',
                'phase' => 'Phase 5 — Payslips',
            ])->name('payslips.index');

            Route::view('/reports', 'coming-soon', [
                'title' => 'Reports',
                'phase' => 'Phase 6 — Reports and dashboards',
            ])->name('reports.index');
        });

    /*
     * Admin area — super_admin only.
     */
    Route::prefix('admin')->name('admin.')
        ->middleware('can:manage-settings')
        ->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
