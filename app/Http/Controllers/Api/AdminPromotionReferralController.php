<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstitutePromoCode;
use App\Models\PromotionRedemption;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPromotionReferralController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $this->filters($request);

        $promoQuery = InstitutePromoCode::query()
            ->leftJoin('institutes', 'institutes.id', '=', 'institute_promo_codes.institute_id')
            ->select('institute_promo_codes.*', 'institutes.name as institute_name')
            ->with('plans:id,name,code');
        $this->applyPromoFilters($promoQuery, $filters);
        $promos = $promoQuery->latest('institute_promo_codes.created_at')->get();

        $promoIds = $promos->pluck('id')->all();
        $promoStats = PromotionRedemption::query()
            ->where('promotion_type', 'institute_promo')
            ->when($promoIds, fn ($q) => $q->whereIn('promotion_id', $promoIds))
            ->selectRaw("promotion_id, COUNT(*) FILTER (WHERE status='consumed') as successful_redemptions, COUNT(*) FILTER (WHERE status='pending') as pending_redemptions, COALESCE(SUM(discount_minor) FILTER (WHERE status='consumed'),0) as total_discount_minor, COUNT(DISTINCT user_id) FILTER (WHERE status='consumed') as unique_users")
            ->groupBy('promotion_id')
            ->get()->keyBy('promotion_id');

        $promoRows = $promos->map(function ($promo) use ($promoStats) {
            $stats = $promoStats->get($promo->id);
            return [
                'id' => $promo->id,
                'institute_id' => $promo->institute_id,
                'institute_name' => $promo->institute_name,
                'name' => $promo->name,
                'code' => $promo->code,
                'discount_type' => $promo->discount_type,
                'amount_minor' => $promo->amount_minor,
                'percentage' => $promo->percentage !== null ? (float) $promo->percentage : null,
                'starts_at' => optional($promo->starts_at)->toISOString(),
                'ends_at' => optional($promo->ends_at)->toISOString(),
                'max_redemptions' => $promo->max_redemptions,
                'per_user_limit' => $promo->per_user_limit,
                'redemptions_count' => $promo->redemptions_count,
                'is_active' => (bool) $promo->is_active,
                'plans' => $promo->plans->map(fn ($plan) => ['id' => $plan->id, 'name' => $plan->name, 'code' => $plan->code])->values(),
                'successful_redemptions' => (int) ($stats->successful_redemptions ?? 0),
                'pending_redemptions' => (int) ($stats->pending_redemptions ?? 0),
                'unique_users' => (int) ($stats->unique_users ?? 0),
                'total_discount_minor' => (int) ($stats->total_discount_minor ?? 0),
                'created_at' => optional($promo->created_at)->toISOString(),
            ];
        })->values();

        $referralQuery = DB::table('referrals')
            ->join('users as referrer', 'referrer.id', '=', 'referrals.referrer_user_id')
            ->join('users as referred', 'referred.id', '=', 'referrals.referred_user_id')
            ->join('referral_codes', 'referral_codes.id', '=', 'referrals.referral_code_id')
            ->leftJoin('discounts as reward', 'reward.id', '=', 'referrals.reward_discount_id')
            ->leftJoin('payments', 'payments.id', '=', 'referrals.qualifying_payment_id')
            ->leftJoin('student_profiles as referred_profile', 'referred_profile.user_id', '=', 'referred.id')
            ->leftJoin('institutes', 'institutes.id', '=', 'referred_profile.institute_id')
            ->select([
                'referrals.id', 'referrals.status', 'referrals.subscription_id', 'referrals.invoice_id', 'referrals.qualifying_payment_id',
                'referrals.qualified_at', 'referrals.rewarded_at', 'referrals.created_at',
                'referrer.id as referrer_user_id', 'referrer.name as referrer_name', 'referrer.email as referrer_email',
                'referred.id as referred_user_id', 'referred.name as referred_name', 'referred.email as referred_email',
                'referral_codes.code as referral_code', 'reward.id as reward_discount_id', 'reward.code as reward_code',
                'reward.discount_type as reward_discount_type', 'reward.amount_minor as reward_amount_minor', 'reward.percentage as reward_percentage',
                'reward.ends_at as reward_ends_at', 'reward.redemptions_count as reward_redemptions_count', 'reward.is_active as reward_is_active',
                'payments.status as payment_status', 'payments.amount_minor as payment_amount_minor', 'payments.currency as payment_currency', 'payments.provider as payment_provider',
                'institutes.id as institute_id', 'institutes.name as institute_name',
            ]);
        $this->applyReferralFilters($referralQuery, $filters);
        $referrals = $referralQuery->orderByDesc('referrals.created_at')->get()->map(fn ($row) => (array) $row)->values();

        $redemptionQuery = DB::table('promotion_redemptions')
            ->join('users', 'users.id', '=', 'promotion_redemptions.user_id')
            ->leftJoin('institute_promo_codes', function ($join) {
                $join->on('institute_promo_codes.id', '=', 'promotion_redemptions.promotion_id')
                    ->where('promotion_redemptions.promotion_type', '=', 'institute_promo');
            })
            ->leftJoin('institutes', 'institutes.id', '=', 'institute_promo_codes.institute_id')
            ->select([
                'promotion_redemptions.id', 'promotion_redemptions.promotion_type', 'promotion_redemptions.promotion_id', 'promotion_redemptions.code',
                'promotion_redemptions.user_id', 'users.name as user_name', 'users.email as user_email',
                'promotion_redemptions.subscription_id', 'promotion_redemptions.invoice_id', 'promotion_redemptions.payment_id',
                'promotion_redemptions.discount_minor', 'promotion_redemptions.status', 'promotion_redemptions.expires_at', 'promotion_redemptions.consumed_at', 'promotion_redemptions.created_at',
                'institutes.id as institute_id', 'institutes.name as institute_name',
            ]);
        $this->applyRedemptionFilters($redemptionQuery, $filters);
        $redemptions = $redemptionQuery->orderByDesc('promotion_redemptions.created_at')->limit(500)->get()->map(fn ($row) => (array) $row)->values();

        $summary = [
            'institute_promos' => $promoRows->count(),
            'active_institute_promos' => $promoRows->where('is_active', true)->count(),
            'successful_promo_redemptions' => $redemptions->where('status', 'consumed')->count(),
            'promo_discount_minor' => (int) $redemptions->where('status', 'consumed')->sum('discount_minor'),
            'referrals' => $referrals->count(),
            'qualified_referrals' => $referrals->whereIn('status', ['qualified', 'rewarded'])->count(),
            'referral_rewards_issued' => $referrals->where('status', 'rewarded')->count(),
            'referral_rewards_used' => $referrals->filter(fn ($row) => (int) ($row['reward_redemptions_count'] ?? 0) > 0)->count(),
        ];

        return response()->json(['data' => compact('summary', 'promoRows', 'referrals', 'redemptions')]);
    }

    public function updatePromoStatus(Request $request, InstitutePromoCode $promoCode): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $promoCode->update(['is_active' => $data['is_active']]);

        return response()->json(['data' => ['id' => $promoCode->id, 'is_active' => (bool) $promoCode->is_active]]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin($request);
        $payload = $this->index($request)->getData(true)['data'];
        $filename = 'preppy-promotions-referrals-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($payload): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SECTION', 'TYPE', 'CODE', 'INSTITUTE', 'PRIMARY USER', 'SECONDARY USER', 'STATUS', 'VALUE', 'VALID UNTIL', 'SUCCESSFUL USES', 'PENDING USES', 'CREATED']);
            foreach ($payload['promoRows'] as $row) {
                $value = $row['discount_type'] === 'percentage' ? $row['percentage'] . '%' : (string) $row['amount_minor'];
                fputcsv($out, ['Institute promo', $row['discount_type'], $row['code'], $row['institute_name'], '', '', $row['is_active'] ? 'active' : 'inactive', $value, $row['ends_at'], $row['successful_redemptions'], $row['pending_redemptions'], $row['created_at']]);
            }
            foreach ($payload['referrals'] as $row) {
                $value = ($row['reward_discount_type'] ?? '') === 'percentage' ? ($row['reward_percentage'] ?? '') . '%' : (string) ($row['reward_amount_minor'] ?? '');
                fputcsv($out, ['Referral', 'referral', $row['referral_code'], $row['institute_name'] ?? '', $row['referrer_email'], $row['referred_email'], $row['status'], $value, $row['reward_ends_at'] ?? '', (int) ($row['reward_redemptions_count'] ?? 0), '', $row['created_at']]);
            }
            foreach ($payload['redemptions'] as $row) {
                fputcsv($out, ['Redemption', $row['promotion_type'], $row['code'], $row['institute_name'] ?? '', $row['user_email'], '', $row['status'], $row['discount_minor'], $row['expires_at'], $row['status'] === 'consumed' ? 1 : 0, $row['status'] === 'pending' ? 1 : 0, $row['created_at']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasSystemRole('system_admin'), 403);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'institute_id' => ['nullable', 'integer', 'exists:institutes,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:80'],
            'student' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }

    private function applyPromoFilters(Builder $query, array $filters): void
    {
        $query->when($filters['institute_id'] ?? null, fn ($q, $id) => $q->where('institute_promo_codes.institute_id', $id))
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where('institute_promo_codes.code', 'ilike', '%' . $code . '%'))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('institute_promo_codes.is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($q) => $q->where('institute_promo_codes.is_active', false))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('institute_promo_codes.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('institute_promo_codes.created_at', '<=', $date));
    }

    private function applyReferralFilters($query, array $filters): void
    {
        $query->when($filters['institute_id'] ?? null, fn ($q, $id) => $q->where('institutes.id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => in_array($status, ['pending', 'qualified', 'rewarded', 'cancelled'], true) ? $q->where('referrals.status', $status) : $q)
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where(function ($nested) use ($code) { $nested->where('referral_codes.code', 'ilike', '%' . $code . '%')->orWhere('reward.code', 'ilike', '%' . $code . '%'); }))
            ->when($filters['student'] ?? null, fn ($q, $student) => $q->where(function ($nested) use ($student) { $needle = '%' . $student . '%'; $nested->where('referrer.name', 'ilike', $needle)->orWhere('referrer.email', 'ilike', $needle)->orWhere('referred.name', 'ilike', $needle)->orWhere('referred.email', 'ilike', $needle); }))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('referrals.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('referrals.created_at', '<=', $date));
    }

    private function applyRedemptionFilters($query, array $filters): void
    {
        $query->when($filters['institute_id'] ?? null, fn ($q, $id) => $q->where('institutes.id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => in_array($status, ['pending', 'consumed', 'cancelled'], true) ? $q->where('promotion_redemptions.status', $status) : $q)
            ->when($filters['code'] ?? null, fn ($q, $code) => $q->where('promotion_redemptions.code', 'ilike', '%' . $code . '%'))
            ->when($filters['student'] ?? null, fn ($q, $student) => $q->where(function ($nested) use ($student) { $needle = '%' . $student . '%'; $nested->where('users.name', 'ilike', $needle)->orWhere('users.email', 'ilike', $needle); }))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('promotion_redemptions.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('promotion_redemptions.created_at', '<=', $date));
    }
}
