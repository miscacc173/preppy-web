<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => app(ReferralService::class)->summary($request->user())]);
    }
}
