<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ExamBoard;
use App\Models\Plan;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use App\Services\DevelopmentPaymentBypassService;

class CatalogController extends Controller
{
    public function index(): JsonResponse
    {
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
                'plans' => Plan::query()
                    ->where('is_public', true)
                    ->where('is_active', true)
                    ->with(['course', 'subjects'])
                    ->orderBy('price_minor')
                    ->get(),
                'development_payment_bypass' => app(DevelopmentPaymentBypassService::class)->enabled(),
                'mock_payments_enabled' => (bool) config('payments.mock.enabled', false) && app()->environment(['local', 'development', 'testing']),
            ],
        ]);
    }
}
