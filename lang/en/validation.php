<?php

declare(strict_types=1);

return [
    'custom' => [
        'email' => [
            'required_without' => 'Enter an email or a phone number.',
        ],
        'phone' => [
            'required_without' => 'Enter a phone number or an email.',
        ],
        'scheduled_at' => [
            'after' => 'Choose a time in the future.',
        ],
    ],
];
