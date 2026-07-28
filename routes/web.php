<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\ForcedPasswordController;
use App\Http\Controllers\Hr\ApprovalController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\EmployeeImportController;
use App\Http\Controllers\Hr\EmployeeUserController;
use App\Http\Controllers\Hr\LetterTypeController;
use App\Http\Controllers\LetterRequestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
 * Forced password change (HR-provisioned accounts). Auth-only, deliberately
 * NOT behind the password.changed guard so it stays reachable while the flag
 * is set.
 */
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [ForcedPasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [ForcedPasswordController::class, 'update'])->name('password.change.update');
});

Route::middleware(['auth', 'verified', 'password.changed'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /*
     * Employee letter requests (Phase 3). Creating and submitting is rate
     * limited per config('ess.rate_limits.letter_requests_per_day') so a
     * single account can't flood the HR approval queue.
     */
    Route::get('/requests', [LetterRequestController::class, 'index'])->name('letter-requests.index');
    Route::get('/requests/create', [LetterRequestController::class, 'create'])->name('letter-requests.create');
    Route::get('/requests/{letter_request}', [LetterRequestController::class, 'show'])->name('letter-requests.show');
    Route::get('/requests/{letter_request}/edit', [LetterRequestController::class, 'edit'])->name('letter-requests.edit');
    Route::delete('/requests/{letter_request}', [LetterRequestController::class, 'cancel'])->name('letter-requests.cancel');

    Route::middleware('throttle:letter-requests')->group(function () {
        Route::post('/requests', [LetterRequestController::class, 'store'])->name('letter-requests.store');
        Route::put('/requests/{letter_request}', [LetterRequestController::class, 'update'])->name('letter-requests.update');
        Route::post('/requests/{letter_request}/submit', [LetterRequestController::class, 'submit'])->name('letter-requests.submit');
    });

    Route::view('/payslips', 'coming-soon', [
        'title' => 'My Payslips',
        'phase' => 'Phase 5 — Payslips',
    ])->name('payslips.index');

    /*
     * HR area — gated at area level by role middleware; record-level
     * authorisation is additionally enforced by policies.
     */
    Route::prefix('hr')->name('hr.')
        ->middleware('role:hr_officer,hr_admin,super_admin')
        ->group(function () {
            // Letter approval queue (Phase 3).
            Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
            Route::get('/approvals/{letter_request}', [ApprovalController::class, 'show'])->name('approvals.show');
            Route::post('/approvals/{letter_request}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{letter_request}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

            // Employee master (Phase 2).
            Route::post('/employees/bulk-deactivate', [EmployeeController::class, 'bulkDeactivate'])
                ->name('employees.bulk-deactivate');

            Route::get('/employees/import', [EmployeeImportController::class, 'create'])->name('employees.import.create');
            Route::post('/employees/import/preview', [EmployeeImportController::class, 'preview'])->name('employees.import.preview');
            Route::post('/employees/import', [EmployeeImportController::class, 'store'])->name('employees.import.store');
            Route::get('/employees/import-template', [EmployeeImportController::class, 'template'])->name('employees.import.template');

            Route::post('/employees/{employee}/provision-user', [EmployeeUserController::class, 'store'])
                ->name('employees.provision-user');

            Route::resource('employees', EmployeeController::class);
            Route::resource('departments', DepartmentController::class)->except(['show']);

            // Letter templates (Phase 3). Browsable by all HR staff; only HR
            // admins can write (enforced by LetterTypePolicy).
            Route::resource('letter-types', LetterTypeController::class)
                ->parameters(['letter-types' => 'letter_type'])
                ->except(['show']);

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

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
