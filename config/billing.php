<?php

return [
    'payment_attempt_expires_minutes' => (int) env('BILLING_PAYMENT_ATTEMPT_EXPIRES_MINUTES', 30),
    'retry_intervals_hours' => array_values(array_filter(array_map('intval', explode(',', env('BILLING_RETRY_INTERVALS_HOURS', '24,72,120'))))),
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 7),
    'collection_days' => (int) env('BILLING_COLLECTION_DAYS', 14),
    'allow_past_due_during_grace' => (bool) env('BILLING_ALLOW_PAST_DUE_DURING_GRACE', true),
];
