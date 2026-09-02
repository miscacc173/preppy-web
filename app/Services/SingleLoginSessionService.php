<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SingleLoginSessionService
{
    public function issueApiToken(User $user, string $name): string
    {
        return DB::transaction(function () use ($user, $name): string {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->revokeExistingSessions($lockedUser);

            return $lockedUser->createToken($name)->plainTextToken;
        });
    }

    public function prepareWebLogin(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->revokeExistingSessions($lockedUser);
        });
    }

    private function revokeExistingSessions(User $user): void
    {
        $user->tokens()->delete();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
