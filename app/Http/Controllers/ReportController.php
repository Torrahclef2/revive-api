<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Reports
 *
 * Endpoints for submitting abuse reports.
 */
class ReportController extends Controller
{
    /**
     * Submit an abuse report.
     *
     * Report a user or a session for abuse. At least one of
     * `reported_user_id` or `reported_session_id` must be provided.
     *
     * @bodyParam reported_user_id    integer optional The ID of the user being reported.
     * @bodyParam reported_session_id integer optional The ID of the session being reported.
     * @bodyParam reason string       required One of: harassment, spam, inappropriate, abuse, other.
     * @bodyParam description string  optional Additional details (max 1000 characters).
     *
     * @response 201 {"message":"Report submitted.","report_id":42}
     * @response 422 {"message":"Must report a user or a session."}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reported_user_id'    => 'nullable|integer|exists:users,id',
            'reported_session_id' => 'nullable|integer|exists:sessions,id',
            'reason'              => 'required|in:harassment,spam,inappropriate,abuse,other',
            'description'         => 'nullable|string|max:1000',
        ]);

        if (empty($data['reported_user_id']) && empty($data['reported_session_id'])) {
            return response()->json(['message' => 'Must report a user or a session.'], 422);
        }

        if (!empty($data['reported_user_id']) && (int) $data['reported_user_id'] === Auth::id()) {
            return response()->json(['message' => 'You cannot report yourself.'], 422);
        }

        $report = Report::create(array_merge($data, ['reporter_id' => Auth::id()]));

        return response()->json(['message' => 'Report submitted.', 'report_id' => $report->id], 201);
    }
}
