<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\InstitutePromoCode;
use App\Models\PromotionRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstitutePromoCodeController extends Controller
{
    public function index(Request $request, Institute $institute): JsonResponse
    {
        $this->authorizeInstitute($request, $institute->id);
        $rows = InstitutePromoCode::query()->where('institute_id', $institute->id)->with('plans:id,name,code')->latest()->get();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request, Institute $institute): JsonResponse
    {
        $this->authorizeInstituteAdmin($request, $institute->id);
        $data = $this->validated($request);
        $data['code'] = strtoupper(trim($data['code']));
        $data['institute_id'] = $institute->id;
        $planIds = $data['plan_ids'] ?? [];
        unset($data['plan_ids']);
        $promo = InstitutePromoCode::create($data);
        $promo->plans()->sync($planIds);
        return response()->json(['data' => $promo->load('plans:id,name,code')], 201);
    }

    public function update(Request $request, Institute $institute, InstitutePromoCode $promoCode): JsonResponse
    {
        $this->authorizeInstituteAdmin($request, $institute->id);
        abort_unless((int) $promoCode->institute_id === (int) $institute->id, 404);
        $data = $this->validated($request, $promoCode->id);
        $data['code'] = strtoupper(trim($data['code']));
        $planIds = $data['plan_ids'] ?? [];
        unset($data['plan_ids']);
        $promoCode->update($data);
        $promoCode->plans()->sync($planIds);
        return response()->json(['data' => $promoCode->fresh()->load('plans:id,name,code')]);
    }

    public function destroy(Request $request, Institute $institute, InstitutePromoCode $promoCode): JsonResponse
    {
        $this->authorizeInstituteAdmin($request, $institute->id);
        abort_unless((int) $promoCode->institute_id === (int) $institute->id, 404);
        if ($promoCode->redemptions_count > 0) {
            $promoCode->update(['is_active' => false]);
            return response()->json(['data' => ['id' => $promoCode->id, 'is_active' => false]]);
        }
        $promoCode->plans()->detach();
        $promoCode->delete();
        return response()->json([], 204);
    }

    public function report(Request $request, Institute $institute, InstitutePromoCode $promoCode): JsonResponse
    {
        $this->authorizeInstitute($request, $institute->id);
        abort_unless((int) $promoCode->institute_id === (int) $institute->id, 404);
        $redemptions = PromotionRedemption::query()->where(['promotion_type' => 'institute_promo', 'promotion_id' => $promoCode->id])->latest()->get();
        return response()->json(['data' => [
            'promo' => $promoCode->load('plans:id,name,code'),
            'summary' => [
                'successful_redemptions' => $redemptions->where('status', 'consumed')->count(),
                'pending_redemptions' => $redemptions->where('status', 'pending')->count(),
                'total_discount_minor' => (int) $redemptions->where('status', 'consumed')->sum('discount_minor'),
                'unique_users' => $redemptions->where('status', 'consumed')->pluck('user_id')->unique()->count(),
            ],
            'redemptions' => $redemptions,
        ]]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:80', Rule::unique('institute_promo_codes', 'code')->ignore($id)],
            'discount_type' => ['required', 'in:fixed,percentage'],
            'amount_minor' => ['nullable', 'integer', 'min:1', 'required_if:discount_type,fixed'],
            'percentage' => ['nullable', 'numeric', 'min:0.01', 'max:99.99', 'required_if:discount_type,percentage'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'plan_ids' => ['array'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
        ]);
    }

    private function authorizeInstitute(Request $request, int $instituteId): void
    {
        abort_unless($request->user()->canAccessInstitute($instituteId), 403);
    }

    private function authorizeInstituteAdmin(Request $request, int $instituteId): void
    {
        abort_unless($request->user()->hasSystemRole('system_admin') || $request->user()->hasRole('institute_admin', 'institute', $instituteId), 403);
    }
}
