<?php

namespace App\Http\Controllers;

use App\Services\RedisNotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected RedisNotificationService $notificationService;

    public function __construct(RedisNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);
        
        $notifications = $this->notificationService->getAll(
            $request->user()->id,
            $limit,
            $offset
        );

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function show(Request $request, $id)
    {
        $notifications = $this->notificationService->getAll($request->user()->id);
        $notification = collect($notifications)->firstWhere('id', $id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $this->notificationService->markAsRead($request->user()->id, $id);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->notificationService->delete($request->user()->id, $id);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    public function send(Request $request)
    {
        $this->notificationService->send(
            $request->user()->id,
            $request->input('type'),
            $request->input('title'),
            $request->input('message'),
            $request->input('data', [])
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    }
}
