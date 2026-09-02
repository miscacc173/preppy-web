<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AcademicSelectionService;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load([
                'roles',
                'studentProfile.course',
                'studentProfile.examBoard',
                'studentProfile.subjects',
            ]),
            'notification_preferences' => NotificationPreference::query()
                ->where('user_id', $request->user()->id)
                ->orderBy('notification_type')
                ->get(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:190', 'unique:users,email,'.$request->user()->id],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:12'],
            'course_id' => ['nullable', Rule::exists('courses', 'id')->where('status', 'active')],
            'exam_board_id' => ['nullable', Rule::exists('exam_boards', 'id')->where('status', 'active')],
            'school_year' => ['nullable', 'string', 'max:40'],
            'school_name' => ['nullable', 'string', 'max:180'],
            'preferences' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', Rule::exists('subjects', 'id')->where('status', 'active')],
        ]);

        if (array_intersect(['course_id', 'exam_board_id', 'subject_ids'], array_keys($data))) {
            $student = $request->user()->studentProfile;
            if ($student) {
                $selection = app(AcademicSelectionService::class)->validate([
                    'course_id' => $data['course_id'] ?? $student->course_id,
                    'exam_board_id' => $data['exam_board_id'] ?? $student->exam_board_id,
                    'subject_ids' => $data['subject_ids'] ?? $student->subjects()->pluck('subjects.id')->all(),
                ]);
                $data = [...$data, ...$selection];
            }
        }

        DB::transaction(function () use ($request, $data): void {
            $request->user()->update(Arr::only($data, ['name', 'email', 'timezone', 'locale']));

            $student = $request->user()->studentProfile;
            if (! $student) {
                return;
            }

            $profileData = Arr::only($data, [
                'course_id',
                'exam_board_id',
                'school_year',
                'school_name',
            ]);

            // Preference screens update independent groups. Merge patches so
            // saving study defaults does not erase onboarding targets (and
            // vice versa).
            if (array_key_exists('preferences', $data)) {
                $profileData['preferences'] = array_replace_recursive(
                    $student->preferences ?? [],
                    $data['preferences'] ?? [],
                );
            }
            if (array_key_exists('metadata', $data)) {
                $profileData['metadata'] = array_replace_recursive(
                    $student->metadata ?? [],
                    $data['metadata'] ?? [],
                );
            }

            $student->update($profileData);

            if (array_key_exists('subject_ids', $data)) {
                $student->subjects()->sync($data['subject_ids'] ?? []);
            }
        });

        return $this->show($request);
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.notification_type' => ['required', 'string', 'max:80'],
            'preferences.*.channel' => ['required', 'in:email,push,sms,in_app'],
            'preferences.*.is_enabled' => ['required', 'boolean'],
            'preferences.*.schedule' => ['sometimes', 'array'],
        ]);

        foreach ($data['preferences'] as $preference) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'notification_type' => $preference['notification_type'],
                    'channel' => $preference['channel'],
                ],
                [
                    'is_enabled' => $preference['is_enabled'],
                    'schedule' => $preference['schedule'] ?? [],
                ],
            );
        }

        return response()->json([
            'data' => NotificationPreference::query()
                ->where('user_id', $request->user()->id)
                ->orderBy('notification_type')
                ->get(),
        ]);
    }
}
