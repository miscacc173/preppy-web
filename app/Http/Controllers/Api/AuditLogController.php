<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        abort_unless($r->user()->hasPermission('audit.view'), 403);
        $q = AuditLog::query();
        foreach (['actor_user_id', 'action', 'auditable_type', 'auditable_id'] as $f) if ($r->filled($f)) $q->where($f, $r->input($f));
        return response()->json($q->latest('created_at')->paginate(min($r->integer('per_page', 50), 100)));
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('audit.view'), 403);
        return response()->json(['data' => $auditLog]);
    }
}
