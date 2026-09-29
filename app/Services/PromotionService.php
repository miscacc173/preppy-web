<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\InstitutePromoCode;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PromotionRedemption;
use App\Models\StudentProfile;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function preview(StudentProfile $student, Plan $plan, string $rawCode, ?int $subtotalMinor = null): array
    {
        $code = strtoupper(trim($rawCode));
        if ($code === '') throw ValidationException::withMessages(['promo_code' => 'Enter a promo or discount code.']);
        $subtotalMinor ??= app(PricingService::class)->priceFor($plan, $student->institute_id)['price_minor'];

        if ($promo = InstitutePromoCode::query()->whereRaw('UPPER(code) = ?', [$code])->first()) {
            return $this->previewInstitutePromo($promo, $student, $plan, $subtotalMinor);
        }

        if ($discount = Discount::query()->whereRaw('UPPER(code) = ?', [$code])->first()) {
            return $this->previewDiscount($discount, $student, $plan, $subtotalMinor);
        }

        throw ValidationException::withMessages(['promo_code' => 'This promo or discount code is not valid.']);
    }

    public function reserve(array $promotion, int $userId, Subscription $subscription, Invoice $invoice): PromotionRedemption
    {
        return DB::transaction(function () use ($promotion, $userId, $subscription, $invoice): PromotionRedemption {
            $existing = PromotionRedemption::query()->where('invoice_id', $invoice->id)->lockForUpdate()->first();
            if ($existing) return $existing;

            $this->assertAvailability($promotion['type'], $promotion['id'], $userId, $promotion['max_redemptions'] ?? null, $promotion['per_user_limit'] ?? 1);
            return PromotionRedemption::create([
                'promotion_type' => $promotion['type'],
                'promotion_id' => $promotion['id'],
                'code' => $promotion['code'],
                'user_id' => $userId,
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'discount_minor' => $promotion['discount_minor'],
                'status' => 'pending',
                'expires_at' => $invoice->collection_ends_at,
                'metadata' => ['name' => $promotion['name'] ?? null],
            ]);
        });
    }

    public function consumeForPayment(Invoice $invoice, int $paymentId): void
    {
        DB::transaction(function () use ($invoice, $paymentId): void {
            $redemption = PromotionRedemption::query()->where('invoice_id', $invoice->id)->lockForUpdate()->first();
            if (! $redemption || $redemption->status === 'consumed') return;
            if ($redemption->status !== 'pending') return;

            $this->assertAvailability($redemption->promotion_type, $redemption->promotion_id, $redemption->user_id, $this->maxRedemptions($redemption), $this->perUserLimit($redemption), $redemption->id);
            $redemption->update(['status' => 'consumed', 'payment_id' => $paymentId, 'consumed_at' => now()]);

            if ($redemption->promotion_type === 'institute_promo') {
                InstitutePromoCode::query()->whereKey($redemption->promotion_id)->increment('redemptions_count');
            } else {
                Discount::query()->whereKey($redemption->promotion_id)->increment('redemptions_count');
            }
        });
    }

    private function previewInstitutePromo(InstitutePromoCode $promo, StudentProfile $student, Plan $plan, int $subtotal): array
    {
        if (! $promo->is_active) throw ValidationException::withMessages(['promo_code' => 'This institute promo code is inactive.']);
        if ($promo->starts_at && $promo->starts_at->isFuture()) throw ValidationException::withMessages(['promo_code' => 'This institute promo code is not active yet.']);
        if ($promo->ends_at && $promo->ends_at->isPast()) throw ValidationException::withMessages(['promo_code' => 'This institute promo code has expired.']);
        if (! $student->institute_id || (int) $student->institute_id !== (int) $promo->institute_id) throw ValidationException::withMessages(['promo_code' => 'This code is only available to students of the issuing institute.']);
        if ($promo->plans()->exists() && ! $promo->plans()->where('plans.id', $plan->id)->exists()) throw ValidationException::withMessages(['promo_code' => 'This code is not eligible for the selected plan.']);
        $this->assertAvailability('institute_promo', $promo->id, $student->user_id, $promo->max_redemptions, $promo->per_user_limit);
        return $this->format('institute_promo', $promo->id, $promo->name, $promo->code, $promo->discount_type, $promo->amount_minor, $promo->percentage, $subtotal, $promo->max_redemptions, $promo->per_user_limit);
    }

    private function previewDiscount(Discount $discount, StudentProfile $student, Plan $plan, int $subtotal): array
    {
        if (! $discount->is_active) throw ValidationException::withMessages(['promo_code' => 'This discount code is inactive.']);
        if ($discount->starts_at && $discount->starts_at->isFuture()) throw ValidationException::withMessages(['promo_code' => 'This discount code is not active yet.']);
        if ($discount->ends_at && $discount->ends_at->isPast()) throw ValidationException::withMessages(['promo_code' => 'This discount code has expired.']);
        if ($discount->institute_id && (int) $discount->institute_id !== (int) $student->institute_id) throw ValidationException::withMessages(['promo_code' => 'This discount code is not available for this student.']);
        if ($discount->plan_id && (int) $discount->plan_id !== (int) $plan->id) throw ValidationException::withMessages(['promo_code' => 'This discount code is not eligible for the selected plan.']);
        $owner = data_get($discount->rules, 'owner_user_id');
        if ($owner && (int) $owner !== (int) $student->user_id) throw ValidationException::withMessages(['promo_code' => 'This reward code belongs to another account.']);
        $perUserLimit = (int) (data_get($discount->rules, 'per_user_limit', 1));
        $this->assertAvailability('discount', $discount->id, $student->user_id, $discount->max_redemptions, $perUserLimit);
        return $this->format('discount', $discount->id, $discount->name, $discount->code, $discount->discount_type, $discount->amount_minor, $discount->percentage, $subtotal, $discount->max_redemptions, $perUserLimit);
    }

    private function format(string $type, int $id, string $name, string $code, string $discountType, ?int $amountMinor, $percentage, int $subtotal, ?int $max, int $perUser): array
    {
        $discountMinor = $discountType === 'percentage' ? (int) round($subtotal * ((float) $percentage / 100)) : (int) $amountMinor;
        $discountMinor = max(0, min($subtotal, $discountMinor));
        if ($subtotal > 0 && $discountMinor >= $subtotal) throw ValidationException::withMessages(['promo_code' => 'This code would reduce the payment to zero. A successful paid transaction is required before a promo can be consumed.']);
        return ['type' => $type, 'id' => $id, 'name' => $name, 'code' => strtoupper($code), 'discount_type' => $discountType, 'discount_minor' => $discountMinor, 'subtotal_minor' => $subtotal, 'total_minor' => max(0, $subtotal - $discountMinor), 'max_redemptions' => $max, 'per_user_limit' => $perUser];
    }

    private function assertAvailability(string $type, int $id, int $userId, ?int $max, int $perUser, ?int $ignoreId = null): void
    {
        $query = PromotionRedemption::query()->where(['promotion_type' => $type, 'promotion_id' => $id])->where(function ($q) { $q->where('status', 'consumed')->orWhere(function ($x) { $x->where('status', 'pending')->where(fn ($y) => $y->whereNull('expires_at')->orWhere('expires_at', '>', now())); }); });
        if ($ignoreId) $query->where('id', '!=', $ignoreId);
        if ($max !== null && $query->count() >= $max) throw ValidationException::withMessages(['promo_code' => 'This promo code has reached its usage limit.']);
        if ($perUser > 0 && (clone $query)->where('user_id', $userId)->count() >= $perUser) throw ValidationException::withMessages(['promo_code' => 'You have already used this promo code the maximum number of times.']);
    }

    private function maxRedemptions(PromotionRedemption $redemption): ?int
    {
        return $redemption->promotion_type === 'institute_promo' ? InstitutePromoCode::find($redemption->promotion_id)?->max_redemptions : Discount::find($redemption->promotion_id)?->max_redemptions;
    }

    private function perUserLimit(PromotionRedemption $redemption): int
    {
        if ($redemption->promotion_type === 'institute_promo') return (int) (InstitutePromoCode::find($redemption->promotion_id)?->per_user_limit ?? 1);
        return (int) data_get(Discount::find($redemption->promotion_id)?->rules, 'per_user_limit', 1);
    }
}
