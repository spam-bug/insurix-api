<?php

declare(strict_types=1);

return [

    /*
    | How much file storage each workspace gets for documents, in bytes. Plans can raise it later.
    */

    'storage_limit_bytes' => (int) env('INSURIX_STORAGE_LIMIT_BYTES', 1024 * 1024 * 1024),

    /*
    | Who may use each feature: "off" (nobody), "premium" (Premium workspaces only) or "free"
    | (everyone). Features scoped to a user rather than a workspace count as Free.
    */

    'features' => [
        'workspace-onboarding' => env('INSURIX_FEATURE_WORKSPACE_ONBOARDING', 'off'),
        'workspace-switcher' => env('INSURIX_FEATURE_WORKSPACE_SWITCHER', 'off'),
    ],

];
