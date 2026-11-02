<?php

declare(strict_types=1);

use App\Http\Controllers\Staff\PremiumLinkController;
use App\Http\Controllers\Staff\StaffSessionController;
use App\Http\Controllers\Staff\StaffTwoFactorChallengeController;
use App\Http\Controllers\Staff\StaffTwoFactorController;
use App\Http\Controllers\Staff\UserController;
use App\Http\Controllers\Staff\WorkspaceController;
use App\Http\Controllers\Staff\WorkspacePremiumController;
use App\Http\Controllers\Staff\WorkspaceRestorationController;
use App\Http\Middleware\EnsureStaffTwoFactor;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// The staff console: the Insurix team's own pages, signed in through the `staff` guard.
Route::prefix('staff')->name('staff.')->middleware(HandleInertiaRequests::class)->group(function () {
    Route::middleware('guest:staff')->group(function () {
        Route::get('login', [StaffSessionController::class, 'create'])->name('login');
        Route::post('login', [StaffSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
        Route::get('two-factor-challenge', [StaffTwoFactorChallengeController::class, 'create'])->name('two-factor-challenge.create');
        Route::post('two-factor-challenge', [StaffTwoFactorChallengeController::class, 'store'])->middleware('throttle:5,1')->name('two-factor-challenge.store');
    });

    Route::middleware('auth:staff')->group(function () {
        Route::post('logout', [StaffSessionController::class, 'destroy'])->name('logout');
        Route::get('two-factor', [StaffTwoFactorController::class, 'create'])->name('two-factor.create');
        Route::post('two-factor', [StaffTwoFactorController::class, 'store'])->middleware('throttle:5,1')->name('two-factor.store');

        Route::middleware(EnsureStaffTwoFactor::class)->group(function () {
            Route::redirect('/', '/staff/workspaces')->name('home');
            Route::get('workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
            Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])->withTrashed()->name('workspaces.show');
            Route::post('workspaces/{workspace}/premium', [WorkspacePremiumController::class, 'store'])->name('workspaces.premium.store');
            Route::delete('workspaces/{workspace}/premium', [WorkspacePremiumController::class, 'destroy'])->name('workspaces.premium.destroy');
            Route::post('workspaces/{workspace}/restoration', [WorkspaceRestorationController::class, 'store'])->withTrashed()->name('workspaces.restoration.store');
            Route::get('premium-links', [PremiumLinkController::class, 'index'])->name('premium-links.index');
            Route::post('premium-links', [PremiumLinkController::class, 'store'])->name('premium-links.store');
            Route::get('users', [UserController::class, 'index'])->name('users.index');
        });
    });
});
