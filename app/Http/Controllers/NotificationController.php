<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Notifications
 *
 * Retrieve and manage in-app notifications for the authenticated user.
 */
class NotificationController extends Controller
{
    /**
     * List Notifications
     *
     * Return all notifications for the authenticated user, newest first.
     *
     * @response 200 scenario="Success" {"notifications":[{"id":"uuid","type":"session_scheduled","data":{"message":"John scheduled a prayer session for Mar 28, 2026 at 6:00 PM UTC","session_id":1,"scheduled_at":"2026-03-28T18:00:00Z"},"read_at":null,"created_at":"2026-03-27T10:00:00Z"}],"unread_count":2}
     */
    public function index(): JsonResponse
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? null,
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->toISOString(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark Notification as Read
     *
     * Mark a single notification as read by its ID.
     *
     * @urlParam id string required The notification UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @response 200 scenario="Marked" {"message":"Notification marked as read."}
     * @response 404 scenario="Not found" {"message":"Notification not found."}
     */
    public function markAsRead(string $id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark All as Read
     *
     * Mark all unread notifications for the authenticated user as read.
     *
     * @response 200 scenario="Success" {"message":"All notifications marked as read."}
     */
    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
