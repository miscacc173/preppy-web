<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function entitlements(Request $request): JsonResponse
    {
        $feature = $request->validate(['feature' => 'nullable|string|max:60'])['feature'] ?? 'learning';
        return response()->json(['data' => app(SubscriptionEntitlementService::class)->summary($request->user(), $feature)]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $query = Invoice::with(['items', 'payments']);
        if ($request->user()->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($request->user()->hasPermission('subscriptions.manage'), 403);
        } elseif ($request->user()->hasPermission('subscriptions.manage') && $request->user()->instituteContextIds()) {
            $query->whereHas('subscription', fn($q) => $q->whereIn('institute_id', $request->user()->instituteContextIds()));
        } else {
            $query->where(['billable_type' => 'student', 'billable_id' => $request->user()->id]);
        }
        return response()->json($query->latest()->paginate());
    }

    public function createInvoice(Request $r): JsonResponse
    {
        $d = $r->validate(['subscription_id' => 'required|exists:subscriptions,id', 'due_at' => 'nullable|date', 'tax_minor' => 'nullable|integer|min:0', 'discount_minor' => 'nullable|integer|min:0']);
        $s = Subscription::with('items')->findOrFail($d['subscription_id']);
        $this->authorizeSubscription($r, $s);
        $i = app(InvoiceService::class)->createForSubscription($s, adjustments: ['discount_minor' => $d['discount_minor'] ?? 0, 'tax_minor' => $d['tax_minor'] ?? 0, 'due_at' => $d['due_at'] ?? now()->addDays(7), 'generated_by' => 'administrator']);
        return response()->json(['data' => $i->load('items')], 201);
    }

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['invoice.subscription', 'refunds']);
        if ($request->user()->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($request->user()->hasPermission('subscriptions.manage'), 403);
        } elseif ($request->user()->hasPermission('subscriptions.manage') && $request->user()->instituteContextIds()) {
            $query->whereHas('invoice.subscription', fn ($q) => $q->whereIn('institute_id', $request->user()->instituteContextIds()));
        } else {
            $query->where(['payer_type' => 'student', 'payer_id' => $request->user()->id]);
        }
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function recordPayment(Request $r, Invoice $invoice): JsonResponse
    {
        abort_unless($r->user()->hasSystemRole('system_admin'), 403);
        $subscription = $invoice->subscription_id ? Subscription::findOrFail($invoice->subscription_id) : null;
        abort_unless($subscription, 422, 'Invoice is not connected to a subscription.');
        $this->authorizeSubscription($r, $subscription);
        $d = $r->validate(['provider' => 'nullable|string|max:40', 'provider_payment_id' => 'nullable|string|max:190', 'amount_minor' => 'required|integer|min:1', 'currency' => 'required|string|size:3']);
        abort_if(strtoupper($d['currency']) !== strtoupper($invoice->currency), 422, 'Payment currency must match the invoice currency.');
        abort_if($d['amount_minor'] > $invoice->total_minor - $invoice->paid_minor, 422, 'Payment exceeds the outstanding invoice balance.');
        $p = Payment::create([...$d, 'invoice_id' => $invoice->id, 'payer_type' => $invoice->billable_type, 'payer_id' => $invoice->billable_id, 'status' => 'processing', 'checkout_reference' => 'MANUAL-'.strtoupper(Str::random(18))]);
        $p = app(PaymentService::class)->complete($p, $d['provider_payment_id'] ?? $p->checkout_reference, ['recorded_by' => $r->user()->id]);
        return response()->json(['data' => $p], 201);
    }

    private function authorizeSubscription(Request $request, Subscription $subscription): void
    {
        abort_unless($request->user()->hasPermission('subscriptions.manage'), 403);
        if (! $request->user()->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($subscription->institute_id && in_array($subscription->institute_id, $request->user()->instituteContextIds(), true), 403);
        }
    }
}
