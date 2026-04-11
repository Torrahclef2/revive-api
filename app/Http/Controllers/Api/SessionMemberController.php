<?php

namespace App\Http\Controllers\Api;

use App\Events\JoinRequested;
use App\Events\MemberAdmitted;
use App\Events\MemberKicked;
use App\Events\MemberRejected;
use App\Http\Controllers\ApiController;
use App\Http\Requests\ReportUserRequest;
use App\Models\PrayerSession;
use App\Models\SessionMember;
use App\Models\SessionReport;
use App\Models\User;
use App\Models\UserStrike;
use Illuminate\Http\Request;

class SessionMemberController extends ApiController
{
    /**
     * User requests to join a prayer session.
     * 
     * @param PrayerSession $session
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function request(PrayerSession $session, Request $request)
    {
        $user = $request->user();

        // Validation: cannot join ended sessions
        if ($session->status === 'ended') {
            return $this->conflict('Cannot join an ended session');
        }

        // Validation: user cannot be a member already
        if ($session->members()->where('user_id', $user->id)->exists()) {
            return $this->conflict('You are already a member of this session');
        }

        // Validation: session must not be full
        if ($session->isFullyAdmitted()) {
            return $this->conflict('This session is at full capacity');
        }

        // Create session member request
        $member = SessionMember::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        // Fire event to notify host
        JoinRequested::dispatch($session, $member, $user);

        return $this->created(
            ['member_id' => $member->id, 'status' => $member->status],
            'Join request sent to session host'
        );
    }

    /**
     * Host admits a user to the session.
     * 
     * @param PrayerSession $session
     * @param SessionMember $member
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function admit(PrayerSession $session, SessionMember $member, Request $request)
    {
        $user = $request->user();

        // Authorization: only host can admit
        if ($session->host_id !== $user->id) {
            return $this->forbidden('Only the session host can admit members');
        }

        // Validation: session not full before admitting
        if ($session->isFullyAdmitted()) {
            return $this->conflict('Session is at full capacity, cannot admit new members');
        }

        // Update member status
        $member->update([
            'status' => 'admitted',
            'joined_at' => now(),
        ]);

        // Fire event to notify member
        MemberAdmitted::dispatch($session, $member);

        return $this->success(
            ['member_id' => $member->id, 'status' => 'admitted'],
            'Member admitted to session'
        );
    }

    /**
     * Host rejects a user's join request.
     * 
     * @param PrayerSession $session
     * @param SessionMember $member
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(PrayerSession $session, SessionMember $member, Request $request)
    {
        $user = $request->user();

        // Authorization: only host can reject
        if ($session->host_id !== $user->id) {
            return $this->forbidden('Only the session host can reject members');
        }

        // Update member status
        $member->update(['status' => 'rejected']);

        // Fire event to notify member
        MemberRejected::dispatch($session, $member);

        return $this->success(
            ['member_id' => $member->id, 'status' => 'rejected'],
            'Join request rejected'
        );
    }

    /**
     * Host kicks a member from a live session.
     * 
     * @param PrayerSession $session
     * @param SessionMember $member
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function kick(PrayerSession $session, SessionMember $member, Request $request)
    {
        $user = $request->user();

        // Authorization: only host can kick
        if ($session->host_id !== $user->id) {
            return $this->forbidden('Only the session host can kick members');
        }

        // Validation: session must be live
        if ($session->status !== 'live') {
            return $this->conflict('Can only kick members during a live session');
        }

        // Update member status
        $member->update([
            'status' => 'kicked',
            'left_at' => now(),
        ]);

        // Fire event to notify member (forces Agora disconnect)
        MemberKicked::dispatch($session, $member);

        return $this->success(
            ['member_id' => $member->id, 'status' => 'kicked'],
            'Member kicked from session'
        );
    }

    /**
     * User leaves a session.
     * 
     * @param PrayerSession $session
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave(PrayerSession $session, Request $request)
    {
        $user = $request->user();

        // Authorization: cannot be the host
        if ($session->host_id === $user->id) {
            return $this->forbidden('Host cannot leave the session. End the session instead.');
        }

        // Find and update member record
        $member = $session->members()->where('user_id', $user->id)->first();

        if (!$member) {
            return $this->notFound('You are not a member of this session');
        }

        // Update left_at timestamp
        $member->update(['left_at' => now()]);

        return $this->success(
            ['member_id' => $member->id],
            'You have left the session'
        );
    }

    /**
     * Report inappropriate behavior in a session.
     * 
     * After 3 reports on the same user, auto-create a UserStrike.
     * 
     * @param PrayerSession $session
     * @param ReportUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(PrayerSession $session, ReportUserRequest $request)
    {
        $reporter = $request->user();
        $reportedUserId = $request->reported_user_id;

        // Validation: cannot report yourself
        if ($reportedUserId === $reporter->id) {
            return $this->conflict('You cannot report yourself');
        }

        // Validation: reported user must be in the session
        if (!$session->members()->where('user_id', $reportedUserId)->exists()) {
            return $this->conflict('The reported user is not in this session');
        }

        // Check for duplicate report (same reporter, session, reported user, within last 24h)
        $duplicateReport = SessionReport::where('session_id', $session->id)
            ->where('reporter_id', $reporter->id)
            ->where('reported_user_id', $reportedUserId)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($duplicateReport) {
            return $this->conflict('You have already reported this user in this session');
        }

        // Create session report
        $report = SessionReport::create([
            'session_id' => $session->id,
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reportedUserId,
            'reason' => $request->reason,
            'stage' => $request->stage,
        ]);

        // Check if this is the 3rd report on this user
        $reportCount = SessionReport::where('reported_user_id', $reportedUserId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // If 3 reports in last 30 days: auto-create strike
        if ($reportCount >= 3) {
            // Check if a strike already exists for recent reports
            $recentReports = SessionReport::where('reported_user_id', $reportedUserId)
                ->where('created_at', '>=', now()->subDays(30))
                ->where('reviewed', false)
                ->count();

            if ($recentReports >= 3 && !UserStrike::where('user_id', $reportedUserId)
                ->where('created_at', '>=', now()->subDays(7))
                ->exists()
            ) {
                // Auto-create strike from system (no reported_by user)
                UserStrike::create([
                    'user_id' => $reportedUserId,
                    'reported_by' => null,
                    'reason' => 'Auto-strike: User accumulated 3 reports within 30 days',
                ]);
            }
        }

        return $this->created(
            ['report_id' => $report->id],
            'Report submitted successfully'
        );
    }
}
