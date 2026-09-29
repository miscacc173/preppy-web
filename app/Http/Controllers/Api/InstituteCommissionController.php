<?php

namespace App\Http\Controllers\Api;

use App\Models\Institute;
use App\Http\Controllers\Controller;
use App\Models\InstituteCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstituteCommissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('commissions.manage'), 403);
        $query = InstituteCommission::query()->with('adjustments');
        if (! $request->user()->hasSystemRole('system_admin', 'system_staff')) {
            $query->whereIn('institute_id', $request->user()->instituteContextIds());
        }
        if ($request->filled('institute_id')) $query->where('institute_id', $request->integer('institute_id'));
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('settlement_type')) $query->where('direction', $request->string('settlement_type')->toString() === 'platform_fee' ? 'platform_collects_from_institute' : 'platform_pays_institute');
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function forInstitute(Request $request, Institute $institute): JsonResponse
    {
        abort_unless($request->user()->hasSystemRole('system_admin', 'system_staff') || $request->user()->hasRole('institute_admin', 'institute', $institute->id), 403);
        $query = InstituteCommission::query()->with('adjustments')->where('institute_id', $institute->id);
        if ($request->filled('direction')) $query->where('direction', $request->string('direction'));
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function platformFees(Request $request, Institute $institute): JsonResponse
    {
        $this->authorizeInstituteLedger($request, $institute);
        return response()->json(InstituteCommission::query()->with('adjustments')->where(['institute_id' => $institute->id, 'direction' => 'platform_collects_from_institute'])->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function earnings(Request $request, Institute $institute): JsonResponse
    {
        $this->authorizeInstituteLedger($request, $institute);
        return response()->json(InstituteCommission::query()->with('adjustments')->where(['institute_id' => $institute->id, 'direction' => 'platform_pays_institute'])->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    private function authorizeInstituteLedger(Request $request, Institute $institute): void
    {
        abort_unless($request->user()->hasSystemRole('system_admin', 'system_staff') || $request->user()->hasRole('institute_admin', 'institute', $institute->id), 403);
    }
}
