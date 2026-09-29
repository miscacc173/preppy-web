<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Collection;
use Throwable;

class PushNotificationService
{
    public function __construct(private FcmClient $fcm, private NotificationPolicy $policy) {}

    /** @return array{sent:int,failed:int,skipped:int} */
    public function sendToUser(User|int $user, string $type, string $title, string $body, array $data = [], ?string $url = null, bool $respectPreference = true, ?string $category = null): array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $resolved = $this->policy->resolve($type, $category ?? ($data['category'] ?? null));
        if ($respectPreference && ! $this->policy->allows($userId, $type, 'push', $resolved['category'])) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 1];
        }

        $devices = UserDevice::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('push_token')
            ->get();

        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($devices as $device) {
            try {
                $delivery = $this->fcm->send((string) $device->push_token, [
                    'type' => $type,
                    'category' => $resolved['category'],
                    'title' => $title,
                    'body' => $body,
                    'url' => $url ?: '/notifications',
                    'platform' => $device->platform,
                    ...$data,
                ]);
                if ($delivery['success']) {
                    $device->forceFill(['last_seen_at' => now()])->save();
                    $result['sent']++;
                } else {
                    if ($delivery['unregistered']) {
                        $device->forceFill(['is_active' => false, 'push_token' => null])->save();
                    }
                    $result['failed']++;
                }
            } catch (Throwable) {
                $result['failed']++;
            }
        }

        return $result;
    }

    /** @param Collection<int,User>|iterable<User> $users @return array{users:int,sent:int,failed:int,skipped:int} */
    public function sendToUsers(iterable $users, string $type, string $title, string $body, array $data = [], ?string $url = null, bool $respectPreference = true, ?string $category = null): array
    {
        $total = ['users' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($users as $user) {
            $total['users']++;
            $result = $this->sendToUser($user, $type, $title, $body, $data, $url, $respectPreference, $category);
            $total['sent'] += $result['sent'];
            $total['failed'] += $result['failed'];
            $total['skipped'] += $result['skipped'];
        }
        return $total;
    }
}
