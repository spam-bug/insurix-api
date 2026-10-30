<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Account\AccountController;
use App\Http\Controllers\Api\V1\Account\OtherSessionsController;
use App\Http\Controllers\Api\V1\Account\PasswordController;
use App\Http\Controllers\Api\V1\Account\PhotoController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use App\Http\Controllers\Api\V1\Account\RecoveryCodeController;
use App\Http\Controllers\Api\V1\Account\SessionController;
use App\Http\Controllers\Api\V1\Account\TwoFactorConfirmationController;
use App\Http\Controllers\Api\V1\Account\TwoFactorController;
use App\Http\Controllers\Api\V1\ApplicationApprovalController;
use App\Http\Controllers\Api\V1\ApplicationDeclineController;
use App\Http\Controllers\Api\V1\ApplicationRequirementController;
use App\Http\Controllers\Api\V1\ApplicationStageController;
use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Api\V1\BulkLeadController;
use App\Http\Controllers\Api\V1\BulkLeadStatusController;
use App\Http\Controllers\Api\V1\BulkPolicyFollowUpController;
use App\Http\Controllers\Api\V1\BulkPolicyStatusController;
use App\Http\Controllers\Api\V1\ClientBirthdayController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientDocumentController;
use App\Http\Controllers\Api\V1\ClientFollowUpController;
use App\Http\Controllers\Api\V1\ClientHistoryController;
use App\Http\Controllers\Api\V1\ClientPolicyController;
use App\Http\Controllers\Api\V1\ClientSummaryController;
use App\Http\Controllers\Api\V1\CommissionSummaryController;
use App\Http\Controllers\Api\V1\DefaultFormController;
use App\Http\Controllers\Api\V1\DeletedWorkspaceController;
use App\Http\Controllers\Api\V1\DocumentAssignmentController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentDownloadController;
use App\Http\Controllers\Api\V1\DocumentSummaryController;
use App\Http\Controllers\Api\V1\FollowUpCalendarController;
use App\Http\Controllers\Api\V1\FollowUpCompletionController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\FollowUpSummaryController;
use App\Http\Controllers\Api\V1\FormCatalogController;
use App\Http\Controllers\Api\V1\FormController;
use App\Http\Controllers\Api\V1\FormPreviewController;
use App\Http\Controllers\Api\V1\FormPublicationController;
use App\Http\Controllers\Api\V1\FormResponseController;
use App\Http\Controllers\Api\V1\FormResponseImportController;
use App\Http\Controllers\Api\V1\FormResponseStatusController;
use App\Http\Controllers\Api\V1\FormTokenController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\ImportFixController;
use App\Http\Controllers\Api\V1\ImportRowController;
use App\Http\Controllers\Api\V1\ImportRunController;
use App\Http\Controllers\Api\V1\InsightReportController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\LeadActivityController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\LeadFollowUpController;
use App\Http\Controllers\Api\V1\LeadMeetingController;
use App\Http\Controllers\Api\V1\LeadPromotionController;
use App\Http\Controllers\Api\V1\LeadStatusController;
use App\Http\Controllers\Api\V1\LeadStatusCountController;
use App\Http\Controllers\Api\V1\MentionSuggestionController;
use App\Http\Controllers\Api\V1\NextActionController;
use App\Http\Controllers\Api\V1\NoteController;
use App\Http\Controllers\Api\V1\NotePinController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PolicyAttentionController;
use App\Http\Controllers\Api\V1\PolicyController;
use App\Http\Controllers\Api\V1\PolicyPremiumMixController;
use App\Http\Controllers\Api\V1\PolicyRenewalController;
use App\Http\Controllers\Api\V1\PolicyStatusCountController;
use App\Http\Controllers\Api\V1\PremiumLinkClaimController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductSuggestionController;
use App\Http\Controllers\Api\V1\ProductSummaryController;
use App\Http\Controllers\Api\V1\PublicFormController;
use App\Http\Controllers\Api\V1\PublicFormPreviewController;
use App\Http\Controllers\Api\V1\PublicPremiumLinkController;
use App\Http\Controllers\Api\V1\ReadAllNotificationsController;
use App\Http\Controllers\Api\V1\RequirementController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\UnreadNotificationCountController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceInvitationController;
use App\Http\Controllers\Api\V1\WorkspaceLogoController;
use App\Http\Controllers\Api\V1\WorkspaceMemberController;
use App\Http\Controllers\Api\V1\WorkspaceMembershipController;
use App\Http\Controllers\Api\V1\WorkspaceOwnershipController;
use App\Http\Controllers\Api\V1\WorkspaceRestorationController;
use App\Http\Middleware\EnsureWorkspaceMember;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [RegisteredUserController::class, 'store']);
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store']);

    Route::middleware('throttle:6,1')->group(function () {
        Route::post('/auth/two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
        Route::post('/auth/forgot-password', [PasswordResetLinkController::class, 'store']);
        Route::post('/auth/reset-password', [PasswordResetController::class, 'store']);

        // Laravel signs URLs by route name, so this one API route has a name (the framework's own).
        Route::post('/auth/email-verification/{user}/{hash}', [EmailVerificationController::class, 'store'])
            ->name('verification.verify');
    });

    Route::get('/public/forms/{form:token}', [PublicFormController::class, 'show']);
    Route::post('/public/forms/{form:token}/responses', [PublicFormController::class, 'store'])
        ->middleware('throttle:form');
    Route::get('/public/form-previews/{token}', [PublicFormPreviewController::class, 'show'])
        ->middleware('throttle:60,1');
    Route::get('/public/premium-links/{premiumLink:token}', [PublicPremiumLinkController::class, 'show'])
        ->middleware('throttle:30,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', [CurrentUserController::class, 'show']);
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);
        Route::post('/auth/email-verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1');

        Route::prefix('account')->group(function () {
            Route::delete('/', [AccountController::class, 'destroy']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/photo', [PhotoController::class, 'store']);
            Route::delete('/photo', [PhotoController::class, 'destroy']);

            Route::put('/password', [PasswordController::class, 'update']);

            Route::post('/two-factor', [TwoFactorController::class, 'store']);
            Route::post('/two-factor/confirm', [TwoFactorConfirmationController::class, 'store']);
            Route::delete('/two-factor', [TwoFactorController::class, 'destroy']);
            Route::post('/two-factor/recovery-codes', [RecoveryCodeController::class, 'store']);

            Route::get('/sessions', [SessionController::class, 'index']);
            Route::delete('/sessions', [OtherSessionsController::class, 'destroy']);
            Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->whereAlphaNumeric('session');
        });

        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::get('/invitations/{invitation:token}', [InvitationController::class, 'show'])->can('accept', 'invitation');
        Route::post('/invitations/{invitation:token}/accept', [InvitationController::class, 'update'])->can('accept', 'invitation');
        Route::get('/form-catalog', [FormCatalogController::class, 'index']);
        Route::post('/premium-links/{premiumLink:token}/claims', [PremiumLinkClaimController::class, 'store'])
            ->middleware('throttle:6,1');

        // Deleted workspaces the owner can still restore. Outside the members-only group below,
        // which can't see deleted workspaces.
        Route::get('/deleted-workspaces', [DeletedWorkspaceController::class, 'index']);
        Route::post('/deleted-workspaces/{workspace}/restoration', [WorkspaceRestorationController::class, 'store'])->withTrashed();

        // Everything a workspace owns. Members only (others get a 404), and child records must
        // belong to the workspace in the URL.
        Route::prefix('workspaces/{workspace}')->middleware(EnsureWorkspaceMember::class)->scopeBindings()->group(function () {
            Route::get('/', [WorkspaceController::class, 'show']);
            Route::put('/', [WorkspaceController::class, 'update']);
            Route::delete('/', [WorkspaceController::class, 'destroy']);
            Route::post('/logo', [WorkspaceLogoController::class, 'store']);
            Route::delete('/logo', [WorkspaceLogoController::class, 'destroy']);
            Route::get('/members', [WorkspaceMemberController::class, 'index']);
            Route::delete('/members/{member}', [WorkspaceMemberController::class, 'destroy']);
            Route::post('/ownership', [WorkspaceOwnershipController::class, 'store']);
            Route::delete('/membership', [WorkspaceMembershipController::class, 'destroy']);
            Route::get('/invitations', [WorkspaceInvitationController::class, 'index']);
            Route::post('/invitations', [WorkspaceInvitationController::class, 'store']);
            Route::delete('/invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy']);

            Route::get('/notifications/unread-count', [UnreadNotificationCountController::class, 'index']);
            Route::post('/notifications/read-all', [ReadAllNotificationsController::class, 'store']);
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::patch('/notifications/{notification}', [NotificationController::class, 'update']);

            Route::get('/forms', [FormController::class, 'index']);
            Route::post('/forms', [FormController::class, 'store']);
            Route::get('/forms/{form}', [FormController::class, 'show']);
            Route::patch('/forms/{form}', [FormController::class, 'update']);
            Route::delete('/forms/{form}', [FormController::class, 'destroy']);
            Route::post('/forms/{form}/publication', [FormPublicationController::class, 'store']);
            Route::delete('/forms/{form}/publication', [FormPublicationController::class, 'destroy']);
            Route::post('/forms/{form}/default', [DefaultFormController::class, 'store']);
            Route::post('/forms/{form}/token', [FormTokenController::class, 'store']);
            Route::post('/forms/{form}/preview', [FormPreviewController::class, 'store']);
            Route::post('/forms/{form}/response-imports', [FormResponseImportController::class, 'store']);
            Route::get('/form-responses', [FormResponseController::class, 'index']);
            Route::post('/form-responses/status', [FormResponseStatusController::class, 'store']);
            Route::delete('/form-responses/{formResponse}', [FormResponseController::class, 'destroy']);

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
            Route::get('/leads/{lead}/mention-suggestions', [MentionSuggestionController::class, 'index'])->can('view', 'lead');
            Route::post('/leads/{lead}/meeting', [LeadMeetingController::class, 'store']);
            Route::post('/leads/{lead}/promote', [LeadPromotionController::class, 'store']);

            Route::get('/clients', [ClientController::class, 'index']);
            Route::post('/clients', [ClientController::class, 'store']);
            Route::get('/clients/summary', [ClientSummaryController::class, 'index']);
            Route::get('/clients/birthdays', [ClientBirthdayController::class, 'index']);
            Route::get('/clients/{client}', [ClientController::class, 'show'])->can('view', 'client');
            Route::put('/clients/{client}', [ClientController::class, 'update']);
            Route::get('/clients/{client}/history', [ClientHistoryController::class, 'index'])->can('view', 'client');

            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::get('/products/summary', [ProductSummaryController::class, 'index']);
            Route::get('/products/suggestions', [ProductSuggestionController::class, 'index']);
            Route::post('/products/suggestions', [ProductSuggestionController::class, 'store']);
            Route::get('/products/{product}', [ProductController::class, 'show'])->can('view', 'product');
            Route::put('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->can('delete', 'product');

            Route::get('/policies/attention', [PolicyAttentionController::class, 'index']);
            Route::get('/policies/status-counts', [PolicyStatusCountController::class, 'index']);
            Route::get('/policies/premium-mix', [PolicyPremiumMixController::class, 'index']);
            Route::post('/policies/bulk/follow-ups', [BulkPolicyFollowUpController::class, 'store']);
            Route::post('/policies/bulk/status', [BulkPolicyStatusController::class, 'update']);
            Route::post('/clients/{client}/policies', [ClientPolicyController::class, 'store']);
            Route::get('/policies', [PolicyController::class, 'index']);
            Route::get('/policies/{policy}', [PolicyController::class, 'show'])->can('view', 'policy');
            Route::put('/policies/{policy}', [PolicyController::class, 'update']);
            Route::delete('/policies/{policy}', [PolicyController::class, 'destroy'])->can('delete', 'policy');
            Route::post('/policies/{policy}/stage', [ApplicationStageController::class, 'store']);
            Route::post('/policies/{policy}/approve', [ApplicationApprovalController::class, 'store']);
            Route::post('/policies/{policy}/decline', [ApplicationDeclineController::class, 'store']);
            Route::post('/policies/{policy}/requirements', [ApplicationRequirementController::class, 'store']);
            Route::put('/requirements/{requirement}', [RequirementController::class, 'update'])->withoutScopedBindings();
            Route::delete('/requirements/{requirement}', [RequirementController::class, 'destroy'])->withoutScopedBindings()->can('delete', 'requirement');
            Route::post('/policies/{policy}/renewals', [PolicyRenewalController::class, 'store']);
            Route::get('/commissions/summary', [CommissionSummaryController::class, 'index']);

            Route::post('/clients/{client}/documents', [ClientDocumentController::class, 'store']);
            Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'show'])->can('view', 'document');
            Route::get('/documents', [DocumentController::class, 'index']);
            Route::post('/documents', [DocumentController::class, 'store']);
            Route::post('/documents/bulk/assign', [DocumentAssignmentController::class, 'store']);
            Route::get('/documents/summary', [DocumentSummaryController::class, 'index']);
            Route::get('/documents/{document}', [DocumentController::class, 'show'])->can('view', 'document');
            Route::put('/documents/{document}', [DocumentController::class, 'update']);
            Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->can('delete', 'document');

            Route::get('/insights/reports', [InsightReportController::class, 'index']);
            Route::get('/insights/next-actions', [NextActionController::class, 'index']);

            Route::get('/search', [SearchController::class, 'index']);

            Route::post('/imports', [ImportController::class, 'store']);
            Route::get('/imports/{import}', [ImportController::class, 'show'])->can('view', 'import');
            Route::put('/imports/{import}', [ImportController::class, 'update']);
            Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->can('update', 'import');
            Route::get('/imports/{import}/rows', [ImportRowController::class, 'index']);
            Route::post('/imports/{import}/fixes', [ImportFixController::class, 'store'])->can('update', 'import');
            Route::post('/imports/{import}/run', [ImportRunController::class, 'store']);

            Route::get('/notes', [NoteController::class, 'index']);
            Route::post('/notes', [NoteController::class, 'store']);
            Route::get('/notes/{note}', [NoteController::class, 'show']);
            Route::put('/notes/{note}', [NoteController::class, 'update']);
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->can('delete', 'note');
            Route::post('/notes/{note}/pin', [NotePinController::class, 'store'])->can('pin', 'note');
            Route::delete('/notes/{note}/pin', [NotePinController::class, 'destroy'])->can('pin', 'note');

            Route::get('/follow-ups', [FollowUpController::class, 'index']);
            Route::get('/follow-ups/summary', [FollowUpSummaryController::class, 'index']);
            Route::get('/follow-ups/calendar', [FollowUpCalendarController::class, 'index']);
            Route::post('/leads/{lead}/follow-ups', [LeadFollowUpController::class, 'store']);
            Route::post('/clients/{client}/follow-ups', [ClientFollowUpController::class, 'store']);
            Route::put('/follow-ups/{followUp}', [FollowUpController::class, 'update']);
            Route::delete('/follow-ups/{followUp}', [FollowUpController::class, 'destroy'])->can('delete', 'followUp');
            Route::post('/follow-ups/{followUp}/complete', [FollowUpCompletionController::class, 'store'])->can('update', 'followUp');
            Route::post('/follow-ups/{followUp}/reopen', [FollowUpCompletionController::class, 'destroy'])->can('update', 'followUp');
        });
    });
});
