<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionEntitlement
{
    public function handle(Request $request, Closure $next, string $feature = 'learning'): Response
    {
        $user = $request->user();
        if ($user?->hasRole('student')) {
            $allowed = app(SubscriptionEntitlementService::class)->allows(
                $user,
                $feature,
                $request->integer('subject_id') ?: null,
                $request->string('subject')->toString() ?: null,
            );
            if (! $allowed) {
                return response()->json(['message' => 'An active subscription entitlement is required.', 'code' => 'subscription_entitlement_required'], 402);
            }
        }
        return $next($request);
    }
}
