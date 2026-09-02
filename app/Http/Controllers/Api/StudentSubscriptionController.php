<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeStudentRequest;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Services\SubscriptionService;
use App\Services\InvoiceService;
use App\Services\DevelopmentPaymentBypassService;
use Illuminate\Http\JsonResponse;

class StudentSubscriptionController extends Controller
{
    public function store(SubscribeStudentRequest $request, StudentProfile $student): JsonResponse
    {
        $subscription = app(SubscriptionService::class)->subscribeStudent($student, Plan::findOrFail($request->integer('plan_id')), $request->user()->id, $request->integer('discount_id') ?: null);
        $developmentBypass = app(DevelopmentPaymentBypassService::class);
        if ($developmentBypass->enabled()) {
            $invoice = app(InvoiceService::class)->createForSubscription($subscription);
            $result = $developmentBypass->activate($subscription, $invoice, $request->user()->id, 'student-subscription-create-'.$subscription->id);
            return response()->json(['data' => $result['subscription'], 'invoice' => $result['invoice'], 'requires_payment' => false], 201);
        }
        return response()->json(['data' => $subscription->load('items')], 201);
    }
}
