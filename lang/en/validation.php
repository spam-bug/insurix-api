<?php

declare(strict_types=1);

return [
    'custom' => [
        'email' => [
            'required_without' => 'Enter an email or a phone number.',
        ],
        'name' => [
            'unique' => 'You already have a product with this name.',
        ],
        'phone' => [
            'required_without' => 'Enter a phone number or an email.',
        ],
        'scheduled_at' => [
            'after' => 'Choose a time in the future.',
        ],
    ],
];
