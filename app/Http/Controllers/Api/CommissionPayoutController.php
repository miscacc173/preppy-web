<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionPayout;
use App\Models\InstituteCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommissionPayoutController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('commissions.manage'), 403);
        $query = CommissionPayout::query();
        if (! request()->user()->hasSystemRole('system_admin', 'system_staff')) {
            $query->whereIn('institute_id', request()->user()->instituteContextIds());
        }
        return response()->json($query->latest()->paginate());
    }

    public function store(Request $r): JsonResponse
    {
        abort_unless($r->user()->hasSystemRole('system_admin') && $r->user()->hasPermission('commissions.manage'), 403);
        $d = $r->validate(['institute_id' => 'required|exists:institutes,id', 'commission_ids' => 'required|array|min:1', 'commission_ids.*' => 'exists:institute_commissions,id']);
        $p = DB::transaction(function () use ($d) {
            $rows = InstituteCommission::where('institute_id', $d['institute_id'])->where('direction', 'platform_pays_institute')->whereIn('id', $d['commission_ids'])->where('status', 'pending')->lockForUpdate()->get();
            abort_if($rows->isEmpty(), 422, 'No payable commissions');
            abort_if($rows->count() !== count(array_unique($d['commission_ids'])), 422, 'Every selected commission must be pending and belong to the payout institute.');
            abort_if($rows->pluck('currency')->unique()->count() !== 1, 422, 'A payout can only contain one currency.');
            $p = CommissionPayout::create(['institute_id' => $d['institute_id'], 'reference' => 'PAY-' . strtoupper(Str::random(10)), 'status' => 'pending', 'amount_minor' => $rows->sum(fn ($row) => $row->net_amount_minor), 'currency' => $rows->first()->currency]);
            DB::table('commission_payout_items')->insert($rows->map(fn($x) => ['commission_payout_id' => $p->id, 'institute_commission_id' => $x->id])->all());
            $rows->each->update(['status' => 'processing']);
            return $p;
        });
        return response()->json(['data' => $p], 201);
    }

    public function markPaid(CommissionPayout $payout): JsonResponse
    {
        abort_unless(request()->user()->hasSystemRole('system_admin') && request()->user()->hasPermission('commissions.manage'), 403);
        DB::transaction(function () use ($payout) {
            $ids = DB::table('commission_payout_items')->where('commission_payout_id', $payout->id)->pluck('institute_commission_id');
            $rows = InstituteCommission::whereIn('id', $ids)->lockForUpdate()->get();
            $amount = $rows->sum(fn ($row) => $row->net_amount_minor);
            abort_if($amount <= 0, 422, 'This payout has no remaining institute earnings after reversals.');
            $payout->update(['status' => 'paid', 'paid_at' => now(), 'amount_minor' => $amount]);
            InstituteCommission::whereIn('id', $rows->filter(fn ($row) => $row->net_amount_minor > 0)->pluck('id'))->update(['status' => 'paid', 'paid_at' => now()]);
        });
        return response()->json(['data' => $payout->fresh()]);
    }
}
