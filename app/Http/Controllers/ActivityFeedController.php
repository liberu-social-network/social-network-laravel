<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityFeedController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->middleware('auth');
        $this->activityService = $activityService;
    }

    /**
     * Display the activity feed page
     */
    public function index()
    {
        return view('activity-feed');
    }

    /**
     * Get activities as JSON (for API usage)
     */
    public function getActivities(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $activities = $this->activityService->getActivitiesForUser(
            Auth::id(),
            $limit
        );

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }
}
