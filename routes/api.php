<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\BulkLeadController;
use App\Http\Controllers\Api\V1\LeadActivityController;
use App\Http\Controllers\Api\V1\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/register', RegisterController::class)->name('auth.register');
    Route::post('/auth/login', LoginController::class)->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', CurrentUserController::class)->name('auth.user');
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

        Route::post('/leads/bulk/status', [BulkLeadController::class, 'updateStatus'])->name('leads.bulk.status');
        Route::post('/leads/bulk/delete', [BulkLeadController::class, 'destroy'])->name('leads.bulk.delete');
        Route::apiResource('leads', LeadController::class);
        Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
        Route::get('/leads/{lead}/activities', [LeadActivityController::class, 'index'])->name('leads.activities.index');
        Route::post('/leads/{lead}/notes', [LeadActivityController::class, 'storeNote'])->name('leads.notes.store');
        Route::post('/leads/{lead}/meeting', [LeadActivityController::class, 'scheduleMeeting'])->name('leads.meeting.store');
    });
});
