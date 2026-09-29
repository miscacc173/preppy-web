<?php

namespace App\Services;

use App\Models\AdminInvitationToken;
use Illuminate\Support\Facades\Crypt;

class AdminInvitationTokenService
{
    public function remember(string $type, int $id, string $token): void
    {
        AdminInvitationToken::updateOrCreate(
            ['invitation_type' => $type, 'invitation_id' => $id],
            ['encrypted_token' => Crypt::encryptString($token)],
        );
    }

    public function retrieve(string $type, int $id): ?string
    {
        $record = AdminInvitationToken::query()
            ->where(['invitation_type' => $type, 'invitation_id' => $id])
            ->first();

        if (! $record) return null;

        try {
            return Crypt::decryptString($record->encrypted_token);
        } catch (\Throwable) {
            return null;
        }
    }

    public function forget(string $type, int $id): void
    {
        AdminInvitationToken::query()
            ->where(['invitation_type' => $type, 'invitation_id' => $id])
            ->delete();
    }
}
