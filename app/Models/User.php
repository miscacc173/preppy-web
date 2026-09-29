<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'status', 'timezone', 'locale', 'email_verified_at', 'last_login_at', 'metadata'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'status' => UserStatus::class, 'email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'metadata' => 'array'];
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot(['context_type', 'context_id', 'starts_at', 'ends_at'])->withTimestamps();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles()->whereHas('permissions', fn($q) => $q->where('permissions.slug', $slug))->exists();
    }

    public function hasRole(string $slug, ?string $contextType = null, ?int $contextId = null): bool
    {
        $query = $this->roles()->where('roles.slug', $slug);

        if ($contextType !== null) {
            $query->where('user_roles.context_type', $contextType);
        }

        if ($contextId !== null) {
            $query->where('user_roles.context_id', $contextId);
        }

        if (! $query->exists()) return false;
        if ($contextType === 'institute' && $contextId !== null) {
            return DB::table('institute_users')->where(['user_id' => $this->id, 'institute_id' => $contextId, 'status' => 'active'])->exists();
        }
        return true;
    }

    public function hasSystemRole(string ...$slugs): bool
    {
        return $this->roles()
            ->whereIn('roles.slug', $slugs)
            ->wherePivot('context_type', 'system')
            ->exists();
    }

    /** @return array<int> */
    public function instituteContextIds(): array
    {
        return DB::table('user_roles')
            ->join('institute_users', function ($join): void {
                $join->on('institute_users.institute_id', '=', 'user_roles.context_id')
                    ->on('institute_users.user_id', '=', 'user_roles.user_id');
            })
            ->where('user_roles.user_id', $this->id)
            ->where('user_roles.context_type', 'institute')
            ->where('institute_users.status', 'active')
            ->whereNotNull('user_roles.context_id')
            ->pluck('user_roles.context_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canAccessInstitute(int $instituteId): bool
    {
        return $this->hasSystemRole('system_admin', 'system_staff')
            || in_array($instituteId, $this->instituteContextIds(), true);
    }

    /** @return array<int> */
    public function activeInstituteMembershipIds(): array
    {
        return DB::table('institute_users')
            ->where(['user_id' => $this->id, 'status' => 'active'])
            ->pluck('institute_id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function canReviewStudent(int $studentUserId): bool
    {
        if ($studentUserId === $this->id) {
            return true;
        }
        if ($this->hasSystemRole('system_admin', 'system_staff')) {
            return true;
        }
        if (! $this->hasRole('teacher')) {
            return false;
        }

        return app(\App\Services\EducatorAccessService::class)->canViewStudent($this, $studentUserId);
    }

    public function loadAccessContext(): static
    {
        $this->load(['roles', 'studentProfile.subjects']);
        $activeInstitutes = $this->instituteContextIds();
        $this->setRelation('roles', $this->roles->filter(function ($role) use ($activeInstitutes): bool {
            if ($role->pivot?->context_type !== 'institute') {
                return true;
            }

            return in_array((int) $role->pivot?->context_id, $activeInstitutes, true);
        })->values());

        return $this;
    }
}
