<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    public function codeFor(User $user): ReferralCode
    {
        return ReferralCode::firstOrCreate(['user_id' => $user->id], ['code' => $this->uniqueReferralCode(), 'is_active' => true]);
    }

    public function validateCode(User $referred, string $rawCode): ReferralCode
    {
        $referralCode = $this->validateCode($referred, $rawCode);
        $existing = Referral::query()->where('referred_user_id', $referred->id)->first();
        if ($existing && (int) $existing->referrer_user_id !== (int) $referralCode->user_id) throw ValidationException::withMessages(['referral_code' => 'A referral has already been registered for this account.']);
        return $referralCode;
    }

    public function attribute(User $referred, string $rawCode, Subscription $subscription, Invoice $invoice): Referral
    {
        $referralCode = $this->validateCode($referred, $rawCode);

        return DB::transaction(function () use ($referred, $referralCode, $subscription, $invoice): Referral {
            $existing = Referral::query()->where('referred_user_id', $referred->id)->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->referrer_user_id !== (int) $referralCode->user_id) throw ValidationException::withMessages(['referral_code' => 'A referral has already been registered for this account.']);
                $existing->update(['subscription_id' => $subscription->id, 'invoice_id' => $invoice->id]);
                return $existing;
            }
            return Referral::create(['referrer_user_id' => $referralCode->user_id, 'referred_user_id' => $referred->id, 'referral_code_id' => $referralCode->id, 'subscription_id' => $subscription->id, 'invoice_id' => $invoice->id, 'status' => 'pending']);
        });
    }

    public function rewardForPayment(Payment $payment): ?Referral
    {
        $invoice = $payment->invoice;
        if (! $invoice?->subscription_id) return null;
        return DB::transaction(function () use ($payment, $invoice): ?Referral {
            $referral = Referral::query()->where('referred_user_id', $invoice->billable_id)->lockForUpdate()->first();
            if (! $referral || $referral->status === 'rewarded') return $referral;
            if ($referral->invoice_id && (int) $referral->invoice_id !== (int) $invoice->id) return $referral;

            $referrer = User::find($referral->referrer_user_id);
            if (! $referrer) return null;
            $discount = $this->createRewardDiscount($referrer, $payment->id);
            $referral->update(['qualifying_payment_id' => $payment->id, 'reward_discount_id' => $discount->id, 'status' => 'rewarded', 'qualified_at' => now(), 'rewarded_at' => now()]);
            return $referral->fresh();
        });
    }

    public function summary(User $user): array
    {
        $code = $this->codeFor($user);
        $referrals = Referral::query()->where('referrer_user_id', $user->id)->latest()->get(['id', 'referred_user_id', 'status', 'reward_discount_id', 'qualified_at', 'rewarded_at', 'created_at']);
        $rewardIds = $referrals->pluck('reward_discount_id')->filter()->values();
        $rewards = Discount::query()->whereIn('id', $rewardIds)->get(['id', 'name', 'code', 'discount_type', 'amount_minor', 'percentage', 'starts_at', 'ends_at', 'redemptions_count', 'max_redemptions', 'is_active']);
        return ['referral_code' => $code->code, 'referrals' => $referrals, 'rewards' => $rewards];
    }

    private function createRewardDiscount(User $user, int $paymentId): Discount
    {
        $cfg = config('promotions.referral_reward');
        $type = in_array($cfg['discount_type'] ?? null, ['fixed', 'percentage'], true) ? $cfg['discount_type'] : 'percentage';
        $percentage = $type === 'percentage' ? max(0, min(100, (float) ($cfg['percentage'] ?? 10))) : null;
        $amount = $type === 'fixed' ? max(0, (int) ($cfg['amount_minor'] ?? 0)) : null;
        return Discount::create([
            'name' => 'Refer-a-friend reward', 'code' => $this->uniqueRewardCode(), 'discount_type' => $type,
            'amount_minor' => $amount, 'percentage' => $percentage, 'starts_at' => now(),
            'ends_at' => now()->addDays(max(1, (int) ($cfg['valid_days'] ?? 30))), 'max_redemptions' => 1,
            'redemptions_count' => 0, 'is_active' => true,
            'rules' => ['owner_user_id' => $user->id, 'per_user_limit' => 1, 'source' => 'referral_reward', 'qualifying_payment_id' => $paymentId],
        ]);
    }

    private function uniqueReferralCode(): string
    {
        do { $code = 'REF'.strtoupper(Str::random(8)); } while (ReferralCode::where('code', $code)->exists());
        return $code;
    }

    private function uniqueRewardCode(): string
    {
        do { $code = 'FRIEND'.strtoupper(Str::random(8)); } while (Discount::where('code', $code)->exists());
        return $code;
    }
}
