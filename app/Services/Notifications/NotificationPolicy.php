<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;

class NotificationPolicy
{
    /** @return array{category:string,mode:string,preference_key:?string} */
    public function resolve(string $type, ?string $category = null): array
    {
        $category ??= match ($type) {
            'study_reminder', 'retry_reminder' => 'study',
            'exam_countdown' => 'exam',
            'performance_summary', 'weekly_digest' => 'performance',
            'payment_success', 'payment_failed', 'invoice_created', 'refund_processed' => 'billing',
            'subscription_activated', 'subscription_changed', 'subscription_renewed', 'subscription_cancelled', 'subscription_past_due' => 'subscription',
            'security_alert', 'password_changed', 'new_login' => 'security',
            'account_updated', 'invitation_received' => 'account',
            'institute_announcement' => 'institute',
            'promotion', 'promotional_offer', 'marketing_campaign' => 'promotion',
            'announcement' => 'announcement',
            default => 'system',
        };

        return match ($category) {
            'study' => ['category' => $category, 'mode' => 'preference', 'preference_key' => $type],
            'exam' => ['category' => $category, 'mode' => 'preference', 'preference_key' => $type],
            'performance' => ['category' => $category, 'mode' => 'preference', 'preference_key' => $type],
            'promotion' => ['category' => $category, 'mode' => 'marketing_opt_in', 'preference_key' => 'marketing_promotions'],
            'billing', 'account', 'security', 'subscription', 'institute', 'announcement', 'system' => ['category' => $category, 'mode' => 'mandatory', 'preference_key' => null],
            default => ['category' => 'system', 'mode' => 'mandatory', 'preference_key' => null],
        };
    }

    public function allows(int $userId, string $type, string $channel = 'push', ?string $category = null): bool
    {
        $policy = $this->resolve($type, $category);
        if ($policy['mode'] === 'mandatory') return true;

        $preference = NotificationPreference::query()
            ->where('user_id', $userId)
            ->where('notification_type', $policy['preference_key'])
            ->where('channel', $channel)
            ->first();

        if ($policy['mode'] === 'marketing_opt_in') {
            return $preference?->is_enabled ?? false;
        }

        return $preference?->is_enabled ?? true;
    }
}
