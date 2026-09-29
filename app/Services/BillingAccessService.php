<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;

class BillingAccessService
{
    public function canViewInvoice(User $user, Invoice $invoice): bool
    {
        if ($invoice->billable_type === 'student' && $invoice->billable_id === $user->id) return true;
        if ($user->hasSystemRole('system_admin', 'system_staff')) return $user->hasPermission('subscriptions.manage');
        $instituteId = $invoice->subscription?->institute_id;
        return $instituteId && $user->hasRole('institute_admin', 'institute', $instituteId);
    }

    public function canViewPayment(User $user, Payment $payment): bool
    {
        return $payment->invoice && $this->canViewInvoice($user, $payment->invoice);
    }

    public function canViewRefund(User $user, PaymentRefund $refund): bool
    {
        return $refund->payment && $this->canViewPayment($user, $refund->payment);
    }
}
