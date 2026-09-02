<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PaymentRefund::query()->with(['payment.invoice.subscription']);
        if ($request->user()->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($request->user()->hasPermission('subscriptions.manage'), 403);
        } elseif ($request->user()->instituteContextIds()) {
            $query->whereHas('invoice.subscription', fn ($q) => $q->whereIn('institute_id', $request->user()->instituteContextIds()));
        } else {
            $query->whereHas('payment', fn ($q) => $q->where(['payer_type' => 'student', 'payer_id' => $request->user()->id]));
        }
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function store(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($request->user()->hasSystemRole('system_admin'), 403);
        abort_unless(in_array($payment->provider, ['jazzcash', 'google_pay', 'mock'], true), 422, 'This payment provider does not support automated refunds.');
        $data = $request->validate(['amount_minor' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:500'], 'revoke_entitlement' => ['sometimes', 'boolean']]);
        $key = trim((string) $request->header('Idempotency-Key'));
        abort_if($key === '' || strlen($key) > 100, 422, 'The Idempotency-Key header is required and must be at most 100 characters.');
        $refund = app(RefundService::class)->refund($payment, $data['amount_minor'], $data['reason'], $request->user(), $key, $data['revoke_entitlement'] ?? false);
        return response()->json(['data' => $refund], $refund->wasRecentlyCreated ? 201 : 200);
    }
}
