<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\BillingDunningService;

Artisan::command('about-platform', function () {
    $this->info('AI Exam Prep Platform');
});

Artisan::command('billing:renew-subscriptions', function () {
    Subscription::query()->with(['plan', 'items'])
        ->whereIn('status', ['active', 'trialing'])
        ->where(function ($query): void {
            $query->where('current_period_ends_at', '<=', now())
                ->orWhere(fn ($trial) => $trial->where('status', 'trialing')->where('trial_ends_at', '<=', now()));
        })
        ->orderBy('id')->chunkById(100, function ($subscriptions): void {
            foreach ($subscriptions as $subscription) {
                if ($subscription->cancel_at_period_end) {
                    $subscription->update(['status' => 'canceled', 'ended_at' => now()]);
                    continue;
                }
                $start = $subscription->status->value === 'trialing' ? now() : $subscription->current_period_ends_at;
                $subscription = app(\App\Services\SubscriptionService::class)->applyScheduledPlanChangeAtRenewal($subscription);
                $end = app(\App\Services\SubscriptionService::class)->periodEnd($subscription->plan, $start);
                $subscription->update(['status' => 'past_due', 'current_period_starts_at' => $start, 'current_period_ends_at' => $end, 'trial_ends_at' => null, 'grace_ends_at' => now()->addDays(config('billing.grace_days', 7))]);
                app(InvoiceService::class)->createForSubscription($subscription->fresh(['plan', 'items']), $start, $end, ['generated_by' => 'renewal']);
            }
        });
    $this->info('Subscription renewals processed.');
})->purpose('Generate renewal invoices and move unpaid renewals to past due');

Schedule::command('billing:renew-subscriptions')->hourly()->withoutOverlapping();

Artisan::command('billing:process-dunning', function () {
    $result = app(BillingDunningService::class)->process();
    $this->info("Dunning processed: {$result['expired_payments']} attempts expired, {$result['retries']} retries scheduled, {$result['canceled']} subscriptions canceled.");
})->purpose('Expire abandoned payment attempts and enforce retry, grace, and cancellation rules');

Schedule::command('billing:process-dunning')->hourly()->withoutOverlapping();
