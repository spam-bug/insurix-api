<?php

declare(strict_types=1);

return [
    'custom' => [
        'document_id' => [
            'exists' => 'Pick one of this client’s documents.',
        ],
        'effective_date' => [
            'required_if' => 'Add the effective date so renewals can be worked out from it.',
        ],
        'email' => [
            'required_without' => 'Enter an email or a phone number.',
        ],
        'name' => [
            'unique' => 'You already have a product with this name.',
        ],
        'next_renewal_date' => [
            'required' => 'Pick the date it renews next.',
            'after' => 'The next renewal has to come after this one.',
        ],
        'phone' => [
            'required_without' => 'Enter a phone number or an email.',
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
        'scheduled_at' => [
            'after' => 'Choose a time in the future.',
        ],
    ],
];
