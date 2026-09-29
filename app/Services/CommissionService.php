<?php

namespace App\Services;

use App\Enums\CommissionType;
use App\Models\InstituteCommission;
use App\Models\InstituteCommissionRule;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\InstituteCommissionAdjustment;
use App\Models\CommissionPayout;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function accrue(Subscription $subscription, int $paymentId, int $paidAmountMinor): ?InstituteCommission
    {
        if (!$subscription->institute_id) return null;
        return DB::transaction(function () use ($subscription, $paymentId, $paidAmountMinor) {
            $rule = InstituteCommissionRule::where('institute_id', $subscription->institute_id)->where('is_active', true)->where(fn($q) => $q->whereNull('plan_id')->orWhere('plan_id', $subscription->plan_id))->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderByRaw('plan_id IS NOT NULL DESC')->latest('starts_at')->first();
            if (!$rule) return null;
            $amount = $rule->commission_type === CommissionType::Fixed ? (int)$rule->fixed_amount_minor : (int)round($paidAmountMinor * ((float)$rule->percentage / 100));
            $collects = $rule->direction === 'platform_collects_from_institute';
            return InstituteCommission::firstOrCreate(
                ['payment_id' => $paymentId, 'commission_rule_id' => $rule->id],
                ['institute_id' => $subscription->institute_id, 'student_user_id' => $subscription->subscriber_id, 'subscription_id' => $subscription->id, 'direction' => $rule->direction, 'status' => $collects ? 'collected' : 'pending', 'base_amount_minor' => $paidAmountMinor, 'commission_amount_minor' => $amount, 'currency' => $rule->currency, 'earned_at' => now(), 'available_at' => $collects ? now() : now()->addDays(config('exam_platform.commission_release_days', 14)), 'settled_at' => $collects ? now() : null, 'metadata' => ['settlement_mode' => $collects ? 'retained_from_platform_collection' : 'platform_payout']]
            );
        });
    }

    public function reverseForRefund(Payment $payment, PaymentRefund $refund): ?InstituteCommissionAdjustment
    {
        $commission = InstituteCommission::query()->where('payment_id', $payment->id)->lockForUpdate()->first();
        if (! $commission) return null;
        $proportional = (int) round($commission->commission_amount_minor * ($refund->amount_minor / max(1, $payment->amount_minor)));
        $amount = min($proportional, $commission->commission_amount_minor - $commission->reversed_amount_minor);
        if ($amount <= 0) return null;
        $adjustment = InstituteCommissionAdjustment::firstOrCreate(
            ['institute_commission_id' => $commission->id, 'payment_refund_id' => $refund->id],
            ['adjustment_type' => $commission->direction === 'platform_collects_from_institute' ? 'platform_fee_reversal' : 'institute_earning_reversal', 'amount_minor' => $amount, 'currency' => $commission->currency, 'status' => 'applied', 'applied_at' => now(), 'metadata' => ['original_status' => $commission->status]]
        );
        if ($adjustment->wasRecentlyCreated) {
            $reversed = min($commission->commission_amount_minor, $commission->reversed_amount_minor + $amount);
            $commission->update(['reversed_amount_minor' => $reversed, 'status' => $reversed >= $commission->commission_amount_minor ? 'reversed' : $commission->status]);
            $payoutIds = DB::table('commission_payout_items')->where('institute_commission_id', $commission->id)->pluck('commission_payout_id');
            CommissionPayout::query()->whereIn('id', $payoutIds)->where('status', 'pending')->get()->each(function (CommissionPayout $payout): void {
                $commissionIds = DB::table('commission_payout_items')->where('commission_payout_id', $payout->id)->pluck('institute_commission_id');
                $net = InstituteCommission::whereIn('id', $commissionIds)->get()->sum(fn ($row) => $row->net_amount_minor);
                $payout->update(['amount_minor' => $net, 'status' => $net > 0 ? 'pending' : 'canceled', 'metadata' => [...($payout->metadata ?? []), 'recalculated_after_refund' => now()->toIso8601String()]]);
            });
        }
        return $adjustment;
    }
}
