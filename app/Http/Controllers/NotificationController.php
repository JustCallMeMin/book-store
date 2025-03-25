<?php

namespace App\Http\Controllers;

use App\Services\RedisNotificationService;
use App\Services\RedisActivityService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected RedisNotificationService $notificationService;
    protected RedisActivityService $activityService;

    public function __construct(
        RedisNotificationService $notificationService,
        RedisActivityService $activityService
    ) {
        $this->notificationService = $notificationService;
        $this->activityService = $activityService;
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
        
        // Log activity - viewing notifications
        $this->activityService->log(
            $request->user()->id,
            'view_notifications',
            'Viewed notifications list',
            ['limit' => $limit, 'offset' => $offset],
            $request->ip(),
            $request->userAgent()
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
        
        // Log activity - viewing specific notification
        $this->activityService->log(
            $request->user()->id,
            'view_notification',
            'Viewed notification details',
            ['notification_id' => $id],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $this->notificationService->markAsRead($request->user()->id, $id);
        
        // Log activity - marking notification as read
        $this->activityService->log(
            $request->user()->id,
            'mark_notification_read',
            'Marked notification as read',
            ['notification_id' => $id],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $this->notificationService->markAllAsRead($request->user()->id);
        
        // Log activity - marking all notifications as read
        $this->activityService->log(
            $request->user()->id,
            'mark_all_notifications_read',
            'Marked all notifications as read',
            [],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->notificationService->delete($request->user()->id, $id);
        
        // Log activity - deleting notification
        $this->activityService->log(
            $request->user()->id,
            'delete_notification',
            'Deleted notification',
            ['notification_id' => $id],
            $request->ip(),
            $request->userAgent()
        );

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
        
        // Log activity - sending notification
        $this->activityService->log(
            $request->user()->id,
            'send_notification',
            'Sent a notification',
            [
                'type' => $request->input('type'),
                'title' => $request->input('title')
            ],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    }
}
