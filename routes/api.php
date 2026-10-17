<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
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

    Route::get('/public/lead-forms/{token}', [PublicLeadFormController::class, 'show'])->name('public.lead-forms.show');
    Route::post('/public/lead-forms/{token}', [PublicLeadFormController::class, 'submit'])
        ->middleware('throttle:lead-form')
        ->name('public.lead-forms.submit');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', CurrentUserController::class)->name('auth.user');
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

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
