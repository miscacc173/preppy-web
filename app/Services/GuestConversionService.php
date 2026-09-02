<?php

namespace App\Services;

use App\Models\GuestDemoSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestConversionService
{
    public function convert(GuestDemoSession $session, User $user, array $profile = [])
    {
        return DB::transaction(function () use ($session, $user, $profile) {
            $locked = GuestDemoSession::lockForUpdate()->findOrFail($session->id);
            if ($locked->converted_at) throw ValidationException::withMessages(['session' => 'Guest session has already been converted.']);
            if ($locked->expires_at->isPast()) throw ValidationException::withMessages(['session' => 'Guest session has expired.']);
            $student = app(StudentOwnershipService::class)->createPlatformStudent($user, [...$profile, 'metadata' => ['guest_progress' => $locked->progress_snapshot]]);
            $student->subjects()->sync($profile['subject_ids'] ?? []);
            $locked->update(['status' => 'converted', 'converted_at' => now(), 'converted_user_id' => $user->id]);
            return $student;
        });
    }
}
