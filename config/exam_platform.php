<?php

return [
    'default_currency' => env('PLATFORM_CURRENCY', 'GBP'),
    'guest_demo_days' => (int)env('GUEST_DEMO_DAYS', 7),
    'commission_release_days' => (int)env('COMMISSION_RELEASE_DAYS', 14),
    'notification_channels' => ['email', 'push', 'in_app', 'sms'],
    'notification_types' => [
        'study_reminders', 'exam_updates', 'performance_reports',
        'subscription_updates', 'promotions', 'institute_announcements',
    ],
];
