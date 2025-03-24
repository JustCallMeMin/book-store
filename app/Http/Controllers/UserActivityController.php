<?php

namespace App\Http\Controllers;

use App\Services\RedisActivityService;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    protected RedisActivityService $activityService;

    public function __construct(RedisActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);
        
        $activities = $this->activityService->getRecent(
            $request->user()->id,
            $limit,
            $offset
        );

        return response()->json([
            'success' => true,
            'data' => $activities,
            'total' => $this->activityService->count($request->user()->id)
        ]);
    }

    public function show(Request $request, $id)
    {
        $activities = $this->activityService->getRecent($request->user()->id);
        $activity = collect($activities)->firstWhere('id', $id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    public function clear(Request $request)
    {
        $this->activityService->clear($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Activities cleared successfully'
        ]);
    }

    public function log(Request $request)
    {
        $this->activityService->log(
            $request->user()->id,
            $request->input('type'),
            $request->input('description'),
            $request->input('metadata', []),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity logged successfully'
        ]);
    }
}
