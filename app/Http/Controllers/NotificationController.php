<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()->latest()->limit(8)->get()->map(function ($notification) {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => (string) $notification->id,
                    'title' => $data['title'] ?? 'Workspace update',
                    'body' => $data['body'] ?? 'There is new activity in your workspace.',
                    'type' => $data['type'] ?? 'blue',
                    'url' => $data['url'] ?? route('planner.bookings'),
                    'read' => (bool) $notification->read_at,
                    'time' => $notification->created_at?->diffForHumans() ?? 'Just now',
                ];
            })->values(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->filled('id')) {
            $user->unreadNotifications()->where('id', (string) $request->input('id'))->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true, 'unread_count' => $user->unreadNotifications()->count()]);
    }
}
