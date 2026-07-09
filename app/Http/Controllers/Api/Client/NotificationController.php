<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recipients = NotificationRecipient::forUser($request->user())
            ->with('notification')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $recipients,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success'       => true,
            'unread_count'  => NotificationRecipient::forUser($request->user())->unread()->count(),
        ]);
    }

    public function read(Request $request, int $notification): JsonResponse
    {
        $recipient = NotificationRecipient::forUser($request->user())
            ->whereKey($notification)
            ->firstOrFail();

        $recipient->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        NotificationRecipient::forUser($request->user())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
