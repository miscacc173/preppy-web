<?php

return [
    'referral_reward' => [
        'discount_type' => env('REFERRAL_REWARD_TYPE', 'percentage'),
        'percentage' => (float) env('REFERRAL_REWARD_PERCENTAGE', 10),
        'amount_minor' => (int) env('REFERRAL_REWARD_AMOUNT_MINOR', 0),
        'valid_days' => (int) env('REFERRAL_REWARD_VALID_DAYS', 30),
    ],
];
