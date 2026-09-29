<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function jazzCash(Request $request): RedirectResponse
    {
        try {
            app(PaymentService::class)->handleCallback('jazzcash', $request->all());
            return redirect(rtrim(config('app.url'), '/').'/pricing?payment_status=success');
        } catch (\Throwable) {
            return redirect(rtrim(config('app.url'), '/').'/pricing?payment_status=failed');
        }
    }
}
