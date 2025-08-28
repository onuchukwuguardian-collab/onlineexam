<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReactivationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'exam_ban_id',
        'request_message',
        'status',
        'reviewed_by_admin_id',
        'admin_response',
        'requested_at',
        'reviewed_at',
        'violation_history',
        'ban_count'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'violation_history' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examBan()
    {
        return $this->belongsTo(ExamBan::class);
    }

    public function ban()
    {
        return $this->belongsTo(ExamBan::class, 'exam_ban_id');
    }

    public function reviewedByAdmin()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    /**
     * Create a new reactivation request
     */
    public static function createRequest($userId, $subjectId, $examBanId, $message)
    {
        // Check if there's already a pending request
        $existingRequest = self::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return ['success' => false, 'message' => 'You already have a pending reactivation request for this subject.'];
        }

        // Get violation history
        $violations = ExamSecurityViolation::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->orderBy('occurred_at', 'desc')
            ->get();

        // Get ban count (how many times this user has been banned for this subject)
        $banCount = ExamBan::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->count();

        $request = self::create([
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'exam_ban_id' => $examBanId,
            'request_message' => $message,
            'status' => 'pending',
            'requested_at' => now(),
            'violation_history' => $violations->map(function ($v) {
                return [
                    'type' => $v->violation_type,
                    'description' => $v->description,
                    'occurred_at' => $v->occurred_at->toISOString(),
                    'ip_address' => $v->ip_address
                ];
            })->toArray(),
            'ban_count' => $banCount
        ]);

        return ['success' => true, 'request' => $request];
    }

    /**
     * Get pending requests for admin dashboard
     */
    public static function getPendingRequests()
    {
        return self::with(['user', 'subject', 'examBan'])
            ->where('status', 'pending')
            ->orderBy('requested_at', 'asc')
            ->get();
    }

    /**
     * Get all requests with filters
     */
    public static function getFilteredRequests($status = null, $subjectId = null, $userId = null)
    {
        $query = self::with(['user', 'subject', 'examBan', 'reviewedByAdmin'])
            ->orderBy('requested_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->paginate(20);
    }

    /**
     * Approve the reactivation request
     */
    public function approve($adminId, $adminResponse = null)
    {
        try {
            \DB::beginTransaction();

            // Update request status
            $this->update([
                'status' => 'approved',
                'reviewed_by_admin_id' => $adminId,
                'admin_response' => $adminResponse ?? 'Request approved. You can now retake the exam.',
                'reviewed_at' => now()
            ]);

            // Remove the ban
            $this->examBan->delete();

            // Clear violation count for this subject (fresh start)
            ExamSecurityViolation::where('user_id', $this->user_id)
                ->where('subject_id', $this->subject_id)
                ->delete();

            \DB::commit();

            // Log the approval
            \Log::info("Reactivation request approved", [
                'request_id' => $this->id,
                'user_id' => $this->user_id,
                'subject_id' => $this->subject_id,
                'admin_id' => $adminId,
                'ban_count' => $this->ban_count
            ]);

            return ['success' => true, 'message' => 'Student has been reactivated successfully.'];

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Failed to approve reactivation request: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve request. Please try again.'];
        }
    }

    /**
     * Reject the reactivation request
     */
    public function reject($adminId, $adminResponse)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by_admin_id' => $adminId,
            'admin_response' => $adminResponse,
            'reviewed_at' => now()
        ]);

        // Log the rejection
        \Log::info("Reactivation request rejected", [
            'request_id' => $this->id,
            'user_id' => $this->user_id,
            'subject_id' => $this->subject_id,
            'admin_id' => $adminId,
            'reason' => $adminResponse
        ]);

        return ['success' => true, 'message' => 'Request has been rejected.'];
    }

    /**
     * Check if user can request reactivation
     */
    public static function canRequestReactivation($userId, $subjectId)
    {
        // Check if there's an active ban
        if (!ExamBan::isBanned($userId, $subjectId)) {
            return ['can_request' => false, 'reason' => 'You are not banned from this subject.'];
        }

        // Check if there's already a pending request
        $pendingRequest = self::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return ['can_request' => false, 'reason' => 'You already have a pending reactivation request for this subject.'];
        }

        // Check if they were recently rejected (within 24 hours)
        $recentRejection = self::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('status', 'rejected')
            ->where('reviewed_at', '>=', Carbon::now()->subHours(24))
            ->first();

        if ($recentRejection) {
            return ['can_request' => false, 'reason' => 'You must wait 24 hours after a rejection before requesting again.'];
        }

        return ['can_request' => true];
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'approved':
                return 'bg-green-100 text-green-800';
            case 'rejected':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get formatted request date
     */
    public function getFormattedRequestDateAttribute()
    {
        return $this->requested_at->format('M d, Y H:i');
    }

    /**
     * Get violation summary
     */
    public function getViolationSummaryAttribute()
    {
        if (!$this->violation_history) {
            return 'No violations recorded';
        }

        $types = collect($this->violation_history)->pluck('type')->countBy();
        $summary = [];

        foreach ($types as $type => $count) {
            $summary[] = ucfirst(str_replace('_', ' ', $type)) . ": {$count}";
        }

        return implode(', ', $summary);
    }
}