<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Notifications\FcmClient;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushNotificationController extends Controller
{
    public function config(FcmClient $fcm): JsonResponse
    {
        return response()->json(['data' => $fcm->publicWebConfig()]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:web,ios,android'],
            'device_id' => ['required', 'string', 'max:190'],
            'push_token' => ['required', 'string', 'max:4096'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        $device = DB::transaction(function () use ($request, $data): UserDevice {
            UserDevice::query()
                ->where('push_token', $data['push_token'])
                ->where('user_id', '!=', $request->user()->id)
                ->update(['is_active' => false, 'push_token' => null, 'updated_at' => now()]);

            return UserDevice::updateOrCreate(
                ['user_id' => $request->user()->id, 'device_id' => $data['device_id']],
                [
                    'platform' => $data['platform'],
                    'push_token' => $data['push_token'],
                    'timezone' => $data['timezone'] ?? null,
                    'last_seen_at' => now(),
                    'is_active' => true,
                    'metadata' => $data['metadata'] ?? [],
                ],
            );
        });

        return response()->json(['data' => $device]);
    }

    public function unregister(Request $request, string $deviceId): JsonResponse
    {
        UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->update(['is_active' => false, 'push_token' => null, 'last_seen_at' => now()]);

        return response()->json(['message' => 'Push device unregistered.']);
    }

    public function broadcast(Request $request, FcmClient $fcm, PushNotificationService $push): JsonResponse
    {
        abort_unless($fcm->configured(), 503, 'FCM server credentials are not configured.');

        $data = $request->validate([
            'category' => ['required', 'in:promotion,announcement,system'],
            'audience' => ['required', 'in:all,role,user'],
            'role_slug' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'string', 'max:500'],
        ]);

        $query = User::query()->where('status', 'active');
        if ($data['audience'] === 'role') {
            abort_if(empty($data['role_slug']), 422, 'Select a role for this audience.');
            $query->whereHas('roles', fn ($q) => $q->where('roles.slug', $data['role_slug']));
        } elseif ($data['audience'] === 'user') {
            abort_if(empty($data['user_id']), 422, 'Select a user for this audience.');
            $query->whereKey($data['user_id']);
        }

        $type = match ($data['category']) {
            'promotion' => 'marketing_campaign',
            'announcement' => 'announcement',
            default => 'system_message',
        };

        $result = $push->sendToUsers(
            $query->get(),
            $type,
            $data['title'],
            $data['body'],
            ['source' => 'admin_campaign', 'category' => $data['category']],
            $data['url'] ?? '/notifications',
            true,
            $data['category'],
        );

        return response()->json(['data' => $result]);
    }

    public function test(Request $request, FcmClient $fcm, PushNotificationService $push): JsonResponse
    {
        abort_unless($fcm->configured(), 503, 'FCM server credentials are not configured.');
        $result = $push->sendToUser(
            $request->user(),
            'test_notification',
            'Preppy notifications are working',
            'This is a test push notification from Preppy.',
            ['source' => 'settings_test'],
            '/settings',
            false,
        );
        abort_if($result['sent'] === 0, 422, 'No active push-enabled device is registered for this account.');
        return response()->json(['data' => $result]);
    }
}
