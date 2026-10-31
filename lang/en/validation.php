<?php

declare(strict_types=1);

return [
    'custom' => [
        'code' => [
            'required_without' => 'Enter the 6-digit code from your authenticator app.',
        ],
        'current_password' => [
            'current_password' => 'That isn’t your current password.',
        ],
        'document_id' => [
            'exists' => 'Pick one of this client’s documents.',
        ],
        'effective_date' => [
            'required_if' => 'Add the effective date so renewals can be worked out from it.',
        ],
        'email' => [
            'required_without' => 'Enter an email or a phone number.',
            'required_if' => 'Add their email to reach them by email.',
        ],
        'image' => [
            'max' => 'Pick an image under 2 MB.',
            'mimes' => 'Use a JPG, PNG or WebP image.',
            'dimensions' => 'Use an image between 64 and 4,000 pixels on each side.',
        ],
        'messenger_username' => [
            'required_if' => 'Add their Messenger username or profile link.',
        ],
        'name' => [
            'unique' => 'You already have a product with this name.',
        ],
        'file' => [
            'max' => 'Pick a file under 10 MB.',
            'mimes' => 'Use a PDF, an image (JPG, PNG, WebP, HEIC), or a Word or Excel file.',
        ],
        'next_renewal_date' => [
            'required' => 'Pick the date it renews next.',
            'after' => 'The next renewal has to come after this one.',
        ],
        'password' => [
            'current_password' => 'That isn’t your password.',
            'different' => 'Choose a password you haven’t been using.',
        ],
        'phone' => [
            'required_without' => 'Enter a phone number or an email.',
            'required_if' => 'Add their phone number to reach them this way.',
        ],
        'policy_id' => [
            'exists' => 'Pick one of this client’s policies.',
        ],
        'premium' => [
            'gt' => 'Enter the premium per payment.',
        ],
        'product_name' => [
            'required_without' => 'Pick a product or enter the product name.',
        ],
        'renewal_date' => [
            'after_or_equal' => 'The renewal date can’t be before the policy takes effect.',
            'required_if' => 'Pick the date it renews.',
        ],
        'telegram_username' => [
            'required_if' => 'Add their Telegram username or profile link.',
        ],
        'scheduled_at' => [
            'after' => 'Choose a time in the future.',
        ],
    ],
];
