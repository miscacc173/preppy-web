<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAppState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppStateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $state = UserAppState::where('user_id', $request->user()->id)->get()->mapWithKeys(fn (UserAppState $item) => [$item->key => $item->value]);
        return response()->json(['data' => $state]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        abort_unless(str_starts_with($key, 'aistar'), 422, 'Unsupported state key.');
        $value = $request->validate(['value' => ['present']])['value'];
        $state = UserAppState::updateOrCreate(['user_id' => $request->user()->id, 'key' => $key], ['value' => $value]);
        return response()->json(['data' => $state]);
    }

    public function destroy(Request $request, string $key): JsonResponse
    {
        UserAppState::where('user_id', $request->user()->id)->where('key', $key)->delete();
        return response()->json(status: 204);
    }
}
