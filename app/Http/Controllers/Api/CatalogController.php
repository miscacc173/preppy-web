<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ExamBoard;
use App\Models\Plan;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\DevelopmentPaymentBypassService;

class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $student = $user?->studentProfile;
        $planQuery = Plan::query()->where('is_active', true);
        if ($student?->institute_id) {
            $planQuery->where(function ($query) use ($student): void {
                $query->where('is_public', true)->orWhereExists(function ($sub) use ($student): void {
                    $sub->selectRaw('1')->from('institute_plan_prices')
                        ->whereColumn('institute_plan_prices.plan_id', 'plans.id')
                        ->where('institute_plan_prices.institute_id', $student->institute_id)
                        ->where('institute_plan_prices.is_active', true)
                        ->where(fn ($dates) => $dates->whereNull('institute_plan_prices.starts_at')->orWhere('institute_plan_prices.starts_at', '<=', now()))
                        ->where(fn ($dates) => $dates->whereNull('institute_plan_prices.ends_at')->orWhere('institute_plan_prices.ends_at', '>=', now()));
                });
            });
        } else {
            $planQuery->where('is_public', true);
        }

        return response()->json([
            'data' => [
                'exam_boards' => ExamBoard::query()->whereIn('status', ['active', 'coming_soon', 'enabled'])->orderBy('name')->get(),
                'courses' => Course::query()
                    ->whereIn('status', ['active', 'coming_soon', 'enabled'])
                    ->whereHas('examBoards', fn ($board) => $board->whereIn('status', ['active', 'coming_soon', 'enabled']))
                    ->with([
                        'examBoards' => fn ($query) => $query->whereIn('status', ['active', 'coming_soon', 'enabled']),
                        'subjects' => fn ($query) => $query->whereIn('status', ['active', 'coming_soon', 'enabled']),
                    ])
                    ->orderBy('name')->get(),
                'subjects' => Subject::query()->whereIn('status', ['active', 'coming_soon', 'enabled'])->with(['topics' => fn ($query) => $query->whereIn('status', ['active', 'coming_soon', 'enabled'])])->orderBy('name')->get(),
                'topics' => Topic::query()->whereIn('status', ['active', 'coming_soon', 'enabled'])
                    ->whereHas('subject', fn ($query) => $query->whereIn('status', ['active', 'coming_soon', 'enabled']))
                    ->with('subject')->orderBy('display_order')->orderBy('name')->get(),
                'plans' => $planQuery
                    ->with(['course', 'subjects', 'courseSubjectSelections.course', 'courseSubjectSelections.subject'])
                    ->orderBy('price_minor')
                    ->get(),
                'development_payment_bypass' => app(DevelopmentPaymentBypassService::class)->enabled(),
                'mock_payments_enabled' => (bool) config('payments.mock.enabled', false) && app()->environment(['local', 'development', 'testing']),
            ],
        ]);
    }
}
