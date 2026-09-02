<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $query = ContactMessage::query();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('kind')) $query->where('kind', $request->string('kind'));
        return response()->json($query->latest()->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:5000'],
            'kind' => ['nullable', 'in:contact,support'],
        ]);
        $message = ContactMessage::create([...$data, 'user_id' => $request->user()?->id, 'kind' => $data['kind'] ?? 'contact', 'status' => 'open']);
        return response()->json(['data' => $message, 'message' => 'Message received.'], 201);
    }

    public function update(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,resolved,closed']]);
        $contactMessage->update($data);
        return response()->json(['data' => $contactMessage->fresh()]);
    }
}
