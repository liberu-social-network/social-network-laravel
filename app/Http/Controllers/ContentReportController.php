<?php

namespace App\Http\Controllers;

use App\Models\ContentReport;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentReportController extends Controller
{
    /**
     * Store a new content report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reportable_type' => 'required|in:post,comment',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Verify the reportable exists
        $reportableClass = $validated['reportable_type'] === 'post' ? Post::class : Comment::class;
        $reportable = $reportableClass::findOrFail($validated['reportable_id']);

        // Check if user already reported this content
        $existingReport = ContentReport::where('reporter_id', Auth::id())
            ->where('reportable_type', $reportableClass)
            ->where('reportable_id', $validated['reportable_id'])
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this content.',
            ], 422);
        }

        $report = ContentReport::create([
            'reporter_id' => Auth::id(),
            'reportable_type' => $reportableClass,
            'reportable_id' => $validated['reportable_id'],
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Content reported successfully.',
            'report' => $report,
        ], 201);
    }

    /**
     * Get user's reports.
     */
    public function index(Request $request)
    {
        $reports = ContentReport::where('reporter_id', Auth::id())
            ->with(['reportable', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reports);
    }

    /**
     * Cancel a report (only if still pending).
     */
    public function destroy(ContentReport $report)
    {
        if ($report->reporter_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($report->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot cancel a report that has been reviewed.',
            ], 422);
        }

        $report->delete();

        return response()->json([
            'message' => 'Report cancelled successfully.',
        ]);
    }
}
