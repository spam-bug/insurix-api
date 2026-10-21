<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Account\LeadFormBrandingController;
use App\Http\Controllers\Api\V1\Account\PasswordController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use App\Http\Controllers\Api\V1\Account\SessionController;
use App\Http\Controllers\Api\V1\Account\TwoFactorController;
use App\Http\Controllers\Api\V1\ApplicationApprovalController;
use App\Http\Controllers\Api\V1\ApplicationDeclineController;
use App\Http\Controllers\Api\V1\ApplicationRequirementController;
use App\Http\Controllers\Api\V1\ApplicationStageController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Api\V1\BulkLeadController;
use App\Http\Controllers\Api\V1\BulkLeadStatusController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientDocumentController;
use App\Http\Controllers\Api\V1\ClientHistoryController;
use App\Http\Controllers\Api\V1\ClientPolicyController;
use App\Http\Controllers\Api\V1\CommissionSummaryController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentDownloadController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\InsightController;
use App\Http\Controllers\Api\V1\LeadActivityController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\LeadFormController;
use App\Http\Controllers\Api\V1\LeadMeetingController;
use App\Http\Controllers\Api\V1\LeadNoteController;
use App\Http\Controllers\Api\V1\LeadPromotionController;
use App\Http\Controllers\Api\V1\LeadStatusController;
use App\Http\Controllers\Api\V1\LeadStatusCountController;
use App\Http\Controllers\Api\V1\PolicyAttentionController;
use App\Http\Controllers\Api\V1\PolicyController;
use App\Http\Controllers\Api\V1\PolicyRenewalController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PublicLeadFormController;
use App\Http\Controllers\Api\V1\RequirementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', RegisterController::class);
    Route::post('/auth/login', LoginController::class);

    Route::middleware('throttle:6,1')->group(function () {
        Route::post('/auth/two-factor-challenge', TwoFactorChallengeController::class);
        Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink']);
        Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);
    });

    Route::get('/public/lead-forms/{agent:lead_form_token}', [PublicLeadFormController::class, 'show']);
    Route::post('/public/lead-forms/{agent:lead_form_token}', [PublicLeadFormController::class, 'store'])
        ->middleware('throttle:lead-form');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', CurrentUserController::class);
        Route::post('/auth/logout', LogoutController::class);

        Route::prefix('account')->group(function () {
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/photo', [ProfileController::class, 'storePhoto']);
            Route::delete('/photo', [ProfileController::class, 'destroyPhoto']);

            Route::put('/lead-form', [LeadFormBrandingController::class, 'updateBio']);
            Route::post('/logo', [LeadFormBrandingController::class, 'storeLogo']);
            Route::delete('/logo', [LeadFormBrandingController::class, 'destroyLogo']);

            Route::put('/password', [PasswordController::class, 'update']);

            Route::post('/two-factor', [TwoFactorController::class, 'store']);
            Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm']);
            Route::delete('/two-factor', [TwoFactorController::class, 'destroy']);
            Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);

            Route::get('/sessions', [SessionController::class, 'index']);
            Route::delete('/sessions', [SessionController::class, 'destroyOthers']);
            Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->whereUuid('session');
        });

        Route::get('/lead-form', [LeadFormController::class, 'show']);
        Route::post('/lead-form/regenerate', [LeadFormController::class, 'regenerate']);

        Route::get('/leads/status-counts', [LeadStatusCountController::class, 'index']);
        Route::post('/leads/bulk/status', [BulkLeadStatusController::class, 'update']);
        Route::post('/leads/bulk/delete', [BulkLeadController::class, 'destroy']);
        Route::get('/leads', [LeadController::class, 'index']);
        Route::post('/leads', [LeadController::class, 'store']);
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->can('view', 'lead');
        Route::put('/leads/{lead}', [LeadController::class, 'update']);
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->can('delete', 'lead');
        Route::patch('/leads/{lead}/status', [LeadStatusController::class, 'update']);
        Route::get('/leads/{lead}/activities', [LeadActivityController::class, 'index'])->can('view', 'lead');
        Route::post('/leads/{lead}/notes', [LeadNoteController::class, 'store']);
        Route::post('/leads/{lead}/meeting', [LeadMeetingController::class, 'store']);
        Route::post('/leads/{lead}/promote', [LeadPromotionController::class, 'store']);

        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show'])->can('view', 'client');
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::get('/clients/{client}/history', [ClientHistoryController::class, 'index'])->can('view', 'client');

        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{product}', [ProductController::class, 'show'])->can('view', 'product');
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->can('delete', 'product');

        Route::get('/policies/attention', [PolicyAttentionController::class, 'index']);
        Route::post('/clients/{client}/policies', [ClientPolicyController::class, 'store']);
        Route::get('/policies', [PolicyController::class, 'index']);
        Route::get('/policies/{policy}', [PolicyController::class, 'show'])->can('view', 'policy');
        Route::put('/policies/{policy}', [PolicyController::class, 'update']);
        Route::delete('/policies/{policy}', [PolicyController::class, 'destroy'])->can('delete', 'policy');
        Route::post('/policies/{policy}/stage', [ApplicationStageController::class, 'store']);
        Route::post('/policies/{policy}/approve', [ApplicationApprovalController::class, 'store']);
        Route::post('/policies/{policy}/decline', [ApplicationDeclineController::class, 'store']);
        Route::post('/policies/{policy}/requirements', [ApplicationRequirementController::class, 'store']);
        Route::put('/requirements/{requirement}', [RequirementController::class, 'update']);
        Route::delete('/requirements/{requirement}', [RequirementController::class, 'destroy'])->can('delete', 'requirement');
        Route::post('/policies/{policy}/renewals', [PolicyRenewalController::class, 'store']);
        Route::get('/commissions/summary', [CommissionSummaryController::class, 'index']);

        Route::post('/clients/{client}/documents', [ClientDocumentController::class, 'store']);
        Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'show'])->can('view', 'document');
        Route::get('/documents', [DocumentController::class, 'index']);
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->can('view', 'document');
        Route::put('/documents/{document}', [DocumentController::class, 'update']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->can('delete', 'document');

        Route::get('/insights/reports', [InsightController::class, 'reports']);
        Route::get('/insights/next-actions', [InsightController::class, 'nextActions']);

        Route::get('/follow-ups', [FollowUpController::class, 'index']);
        Route::get('/follow-ups/summary', [FollowUpController::class, 'summary']);
        Route::post('/leads/{lead}/follow-ups', [FollowUpController::class, 'store']);
        Route::put('/follow-ups/{followUp}', [FollowUpController::class, 'update']);
        Route::delete('/follow-ups/{followUp}', [FollowUpController::class, 'destroy']);
        Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete']);
        Route::post('/follow-ups/{followUp}/reopen', [FollowUpController::class, 'reopen']);
    });
});
