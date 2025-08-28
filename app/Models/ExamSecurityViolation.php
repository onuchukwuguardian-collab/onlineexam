<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ExamSecurityViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'exam_session_id',
        'violation_type',
        'description',
        'metadata',
        'occurred_at',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * Record a security violation with enhanced user identification
     * TRACKS STUDENTS BY REGISTRATION NUMBER AND EMAIL, NOT IP ADDRESS
     */
    public static function recordViolation($userId, $subjectId, $violationType, $description, $examSessionId = null, $metadata = [])
    {
        // Get user details for registration and email-based identification
        $user = \App\Models\User::find($userId);
        $userIdentification = [];
        
        if ($user) {
            $userIdentification = [
                'primary_tracking' => [
                    'registration_number' => $user->registration_number,
                    'email' => $user->email,
                ],
                'user_name' => $user->name,
                'user_id' => $user->id,
                'tracking_note' => 'Student tracked by registration number and email, NOT by IP address'
            ];
        }
        
        // Enhanced metadata emphasizing user credential-based tracking
        $enhancedMetadata = array_merge($metadata, [
            'user_identification' => $userIdentification,
            'violation_tracked_by' => 'registration_number_and_email', // Primary tracking method
            'tracking_method' => 'user_credentials_not_ip', // Clarify tracking approach
            'timestamp' => now()->toISOString(),
            'note' => 'Violation tracking is done by student registration number and email address for accurate identification in shared computer environments'
        ]);
        
        return self::create([
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'exam_session_id' => $examSessionId,
            'violation_type' => $violationType,
            'description' => $description,
            'metadata' => $enhancedMetadata,
            'occurred_at' => now(),
            'ip_address' => request()->ip(), // Kept only for audit trail, NOT used for student tracking
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Get violation count for a user in a subject
     */
    public static function getViolationCount($userId, $subjectId, $violationType = null)
    {
        $query = self::where('user_id', $userId)
                    ->where('subject_id', $subjectId);
        
        if ($violationType) {
            $query->where('violation_type', $violationType);
        }
        
        return $query->count();
    }

    /**
     * Check if user should be locked out (includes unexpected logouts/crashes)
     */
    public static function shouldLockUser($userId, $subjectId, $violationType = 'tab_switch', $maxViolations = 3)
    {
        return self::getViolationCount($userId, $subjectId, $violationType) >= $maxViolations;
    }

    /**
     * Get recent violations for a user
     */
    public static function getRecentViolations($userId, $subjectId, $hours = 24)
    {
        return self::where('user_id', $userId)
                  ->where('subject_id', $subjectId)
                  ->where('occurred_at', '>=', Carbon::now()->subHours($hours))
                  ->orderBy('occurred_at', 'desc')
                  ->get();
    }
}