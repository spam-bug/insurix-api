<?php

declare(strict_types=1);

return [

    /*
    | What each plan allows. Null means no limit. Seats count the owner, members and pending
    | invites. Workspaces already over a limit keep what they have; only new additions are blocked.
    */

    'plans' => [
        'free' => [
            'seats' => (int) env('INSURIX_FREE_SEATS', 2),
            'forms' => (int) env('INSURIX_FREE_FORMS', 3),
            'storage_bytes' => (int) env('INSURIX_FREE_STORAGE_BYTES', 1024 * 1024 * 1024),
        ],
        'premium' => [
            'seats' => (int) env('INSURIX_PREMIUM_SEATS', 10),
            'forms' => null,
            'storage_bytes' => (int) env('INSURIX_PREMIUM_STORAGE_BYTES', 20 * 1024 * 1024 * 1024),
        ],
    ],

    /*
    | Who may use each feature: "off" (nobody), "premium" (Premium workspaces only) or "free"
    | (everyone). Features scoped to a user rather than a workspace count as Free.
    */

    'features' => [
        'workspace-onboarding' => env('INSURIX_FEATURE_WORKSPACE_ONBOARDING', 'off'),
        'workspace-switcher' => env('INSURIX_FEATURE_WORKSPACE_SWITCHER', 'off'),
        'form-premium-theme' => env('INSURIX_FEATURE_FORM_PREMIUM_THEME', 'premium'),
    ],

];
