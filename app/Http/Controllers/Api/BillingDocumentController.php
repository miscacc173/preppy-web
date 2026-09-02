<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Services\BillingAccessService;
use App\Services\BillingDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingDocumentController extends Controller
{
    public function invoice(Request $request, Invoice $invoice): Response
    {
        $invoice->loadMissing('subscription');
        abort_unless(app(BillingAccessService::class)->canViewInvoice($request->user(), $invoice), 403);
        return $this->download(app(BillingDocumentService::class)->invoice($invoice), $invoice->invoice_number.'.pdf');
    }

    public function receipt(Request $request, Payment $payment): Response
    {
        $payment->loadMissing('invoice.subscription');
        abort_unless(in_array($payment->status, ['succeeded', 'partially_refunded', 'refunded'], true) && $payment->receipt_number && app(BillingAccessService::class)->canViewPayment($request->user(), $payment), 403);
        return $this->download(app(BillingDocumentService::class)->receipt($payment), ($payment->receipt_number ?? 'receipt-'.$payment->id).'.pdf');
    }

    public function refundReceipt(Request $request, PaymentRefund $refund): Response
    {
        $refund->loadMissing('payment.invoice.subscription');
        abort_unless($refund->status === 'succeeded' && app(BillingAccessService::class)->canViewRefund($request->user(), $refund), 403);
        return $this->download(app(BillingDocumentService::class)->refundReceipt($refund), 'refund-'.$refund->id.'.pdf');
    }

    private function download(string $contents, string $filename): Response
    {
        return response($contents, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$filename.'"', 'Content-Length' => (string) strlen($contents)]);
    }
}
