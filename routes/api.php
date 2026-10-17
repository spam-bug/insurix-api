<?php

use App\Http\Controllers\Api\V1\Account\LeadFormBrandingController;
use App\Http\Controllers\Api\V1\Account\PasswordController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use App\Http\Controllers\Api\V1\Account\SessionController;
use App\Http\Controllers\Api\V1\Account\TwoFactorController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Api\V1\BulkLeadController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CommissionSummaryController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\LeadActivityController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\LeadFormController;
use App\Http\Controllers\Api\V1\LeadStatusCountController;
use App\Http\Controllers\Api\V1\PolicyController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PromoteLeadController;
use App\Http\Controllers\Api\V1\PublicLeadFormController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/register', RegisterController::class)->name('auth.register');
    Route::post('/auth/login', LoginController::class)->name('auth.login');

    Route::middleware('throttle:6,1')->group(function () {
        Route::post('/auth/two-factor-challenge', TwoFactorChallengeController::class)->name('auth.two-factor-challenge');
        Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink'])->name('auth.password.email');
        Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])->name('auth.password.reset');
    });

    Route::get('/public/lead-forms/{token}', [PublicLeadFormController::class, 'show'])->name('public.lead-forms.show');
    Route::post('/public/lead-forms/{token}', [PublicLeadFormController::class, 'submit'])
        ->middleware('throttle:lead-form')
        ->name('public.lead-forms.submit');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', CurrentUserController::class)->name('auth.user');
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

        Route::prefix('account')->name('account.')->group(function () {
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/photo', [ProfileController::class, 'storePhoto'])->name('photo.store');
            Route::delete('/photo', [ProfileController::class, 'destroyPhoto'])->name('photo.destroy');

            Route::put('/lead-form', [LeadFormBrandingController::class, 'updateBio'])->name('lead-form.update');
            Route::post('/logo', [LeadFormBrandingController::class, 'storeLogo'])->name('logo.store');
            Route::delete('/logo', [LeadFormBrandingController::class, 'destroyLogo'])->name('logo.destroy');

            Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

            Route::post('/two-factor', [TwoFactorController::class, 'store'])->name('two-factor.store');
            Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
            Route::delete('/two-factor', [TwoFactorController::class, 'destroy'])->name('two-factor.destroy');
            Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');

            Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
            Route::delete('/sessions', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
            Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->whereNumber('session')->name('sessions.destroy');
        });

        Route::get('/lead-form', [LeadFormController::class, 'show'])->name('lead-form.show');
        Route::post('/lead-form/regenerate', [LeadFormController::class, 'regenerate'])->name('lead-form.regenerate');

        Route::get('/leads/status-counts', LeadStatusCountController::class)->name('leads.status-counts');
        Route::post('/leads/bulk/status', [BulkLeadController::class, 'updateStatus'])->name('leads.bulk.status');
        Route::post('/leads/bulk/delete', [BulkLeadController::class, 'destroy'])->name('leads.bulk.delete');
        Route::apiResource('leads', LeadController::class);
        Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
        Route::get('/leads/{lead}/activities', [LeadActivityController::class, 'index'])->name('leads.activities.index');
        Route::post('/leads/{lead}/notes', [LeadActivityController::class, 'storeNote'])->name('leads.notes.store');
        Route::post('/leads/{lead}/meeting', [LeadActivityController::class, 'scheduleMeeting'])->name('leads.meeting.store');
        Route::post('/leads/{lead}/promote', PromoteLeadController::class)->name('leads.promote');

        Route::apiResource('clients', ClientController::class)->only(['index', 'show', 'update']);

        Route::apiResource('products', ProductController::class);

        Route::get('/policies/attention', [PolicyController::class, 'attention'])->name('policies.attention');
        Route::post('/clients/{client}/policies', [PolicyController::class, 'store'])->name('clients.policies.store');
        Route::apiResource('policies', PolicyController::class)->except(['store']);
        Route::get('/commissions/summary', CommissionSummaryController::class)->name('commissions.summary');

        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
        Route::get('/follow-ups/summary', [FollowUpController::class, 'summary'])->name('follow-ups.summary');
        Route::post('/leads/{lead}/follow-ups', [FollowUpController::class, 'store'])->name('leads.follow-ups.store');
        Route::put('/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('follow-ups.update');
        Route::delete('/follow-ups/{followUp}', [FollowUpController::class, 'destroy'])->name('follow-ups.destroy');
        Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
        Route::post('/follow-ups/{followUp}/reopen', [FollowUpController::class, 'reopen'])->name('follow-ups.reopen');
    });
});
