<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property string $ban_reason
 * @property array $violation_details
 * @property int $total_violations
 * @property \Carbon\Carbon $banned_at
 * @property int|null $banned_by_admin_id
 * @property bool $is_permanent
 * @property \Carbon\Carbon|null $ban_expires_at
 * @property string|null $admin_notes
 * @property bool $is_active
 * @property \Carbon\Carbon|null $reactivated_at
 * @property int|null $reactivated_by
 * @property string|null $reactivation_reason
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Subject $subject
 * @property-read \App\Models\User|null $bannedByAdmin
 * @property-read \App\Models\User|null $reactivatedBy
 * @property-read string $ban_duration
 */
class ExamBan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'ban_reason',
        'violation_details',
        'violation_type',
        'total_violations',
        'banned_at',
        'banned_by_admin_id',
        'is_permanent',
        'ban_expires_at',
        'admin_notes',
        'is_active',
        'reactivated_at',
        'reactivated_by',
        'reactivation_reason',
        'ban_count',
        'subject_specific_data'
    ];

    protected $casts = [
        'violation_details' => 'array',
        'banned_at' => 'datetime',
        'ban_expires_at' => 'datetime',
        'reactivated_at' => 'datetime',
        'is_permanent' => 'boolean',
        'is_active' => 'boolean',
        'subject_specific_data' => 'array'
    ];

    // Violation type constants
    const VIOLATION_TAB_SWITCH = 'tab_switch';
    const VIOLATION_RIGHT_CLICK = 'right_click';
    const VIOLATION_COPY_PASTE = 'copy_paste';
    const VIOLATION_BROWSER_NAVIGATION = 'browser_navigation';
    
    // Ban thresholds
    const TAB_SWITCH_THRESHOLD = 1; // Immediate ban
    const RIGHT_CLICK_THRESHOLD = 15; // 15 strikes

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function bannedByAdmin()
    {
        return $this->belongsTo(User::class, 'banned_by_admin_id');
    }

    public function reactivatedBy()
    {
        return $this->belongsTo(User::class, 'reactivated_by');
    }
    
    /**
     * Reactivation requests for this ban
     */
    public function reactivationRequests()
    {
        return $this->hasMany(\App\Models\ReactivationRequest::class, 'ban_id');
    }
    
    /**
     * Get pending reactivation request for this ban
     */
    public function pendingReactivationRequest()
    {
        return $this->hasOne(\App\Models\ReactivationRequest::class, 'ban_id')
                    ->where('status', \App\Models\ReactivationRequest::STATUS_PENDING);
    }

    /**
     * Create a subject-specific ban after violations
     * TRACKS STUDENTS BY REGISTRATION NUMBER AND EMAIL, NOT IP ADDRESS
     * ENHANCED: Comprehensive subject isolation and advanced repeat offender tracking
     */
    public static function createSubjectBan($userId, $subjectId, $violationType, $violations, $reason = null)
    {
        // Get user details for registration-based tracking
        $user = \App\Models\User::find($userId);
        $subject = \App\Models\Subject::find($subjectId);
        
        // ENHANCED: Comprehensive repeat offender analysis
        $previousBansInSubject = self::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->count();
            
        $bansInOtherSubjects = self::where('user_id', $userId)
            ->where('subject_id', '!=', $subjectId)
            ->count();
            
        $totalBansAcrossAllSubjects = $previousBansInSubject + $bansInOtherSubjects;
        
        $banCount = $previousBansInSubject + 1;
        
        // Determine offender classification
        $offenderType = 'first_time';
        if ($previousBansInSubject > 0) {
            $offenderType = 'repeat_subject_offender';
        } elseif ($bansInOtherSubjects > 0) {
            $offenderType = 'cross_subject_offender';
        }
        
        if ($totalBansAcrossAllSubjects >= 3) {
            $offenderType = 'chronic_offender';
        }
        
        // Create ban reason based on violation type
        $defaultReason = self::generateBanReason($violationType, $violations->count(), $banCount);
        $banReason = $reason ?? $defaultReason;
        
        // Collect violation details emphasizing user credentials over IP tracking
        $violationDetails = $violations->map(function ($violation) use ($user) {
            return [
                'type' => $violation->violation_type,
                'description' => $violation->description,
                'occurred_at' => $violation->occurred_at->toISOString(),
                'student_identification' => [
                    'registration_number' => $user->registration_number ?? 'N/A',
                    'email' => $user->email,
                    'name' => $user->name,
                    'user_id' => $user->id
                ],
                'tracking_method' => 'registration_and_email_based',
                'user_agent' => $violation->user_agent,
                'metadata' => $violation->metadata,
                'note' => 'Student tracked by registration number and email, NOT IP address'
            ];
        })->toArray();
        
        // ENHANCED: Comprehensive subject-specific data with advanced offender tracking
        $subjectSpecificData = [
            'subject_tracking' => [
                'subject_name' => $subject->name ?? 'Unknown',
                'subject_code' => $subject->code ?? 'N/A',
                'subject_id' => $subjectId,
                'isolation_confirmed' => true
            ],
            'violation_analysis' => [
                'violation_type' => $violationType,
                'threshold_reached' => self::getThresholdForViolation($violationType),
                'total_violations_in_subject' => $violations->count(),
                'violation_pattern' => $violations->pluck('violation_type')->toArray()
            ],
            'offender_classification' => [
                'offender_type' => $offenderType,
                'ban_count_in_this_subject' => $banCount,
                'previous_bans_in_subject' => $previousBansInSubject,
                'bans_in_other_subjects' => $bansInOtherSubjects,
                'total_bans_across_all_subjects' => $totalBansAcrossAllSubjects + 1,
                'is_repeat_offender' => $banCount > 1,
                'is_cross_subject_offender' => $bansInOtherSubjects > 0,
                'is_chronic_offender' => $totalBansAcrossAllSubjects >= 2
            ],
            'isolation_policy' => [
                'ban_scope' => 'subject_specific_only',
                'affected_subject' => $subject->name ?? 'this subject',
                'unaffected_subjects' => 'all_other_subjects',
                'isolation_note' => 'This ban only applies to ' . ($subject->name ?? 'this subject') . ' - other subjects remain fully accessible'
            ],
            'tracking_metadata' => [
                'ban_date' => now()->toISOString(),
                'tracking_method' => 'registration_and_email_based',
                'ip_tracking_disabled' => true,
                'primary_identifiers' => ['user_id', 'registration_number', 'email']
            ]
        ];

        return self::create([
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'ban_reason' => $banReason,
            'violation_details' => $violationDetails,
            'violation_type' => $violationType,
            'total_violations' => $violations->count(),
            'banned_at' => now(),
            'is_permanent' => true,
            'is_active' => true,
            'ban_count' => $banCount,
            'subject_specific_data' => $subjectSpecificData,
            'admin_notes' => "ENHANCED SUBJECT-SPECIFIC BAN: Student {$user->name} (Reg: {$user->registration_number} | Email: {$user->email}) - {$offenderType} - Banned after {$violations->count()} {$violationType} violation(s) for {$subject->name} ONLY. Ban #{$banCount} for this subject. Total bans across all subjects: " . ($totalBansAcrossAllSubjects + 1) . ". Other subjects remain accessible. Tracked by registration credentials, NOT IP address."
        ]);
    }

    /**
     * Check if a user is banned from a subject
     * ENHANCED: Subject-specific isolation
     */
    public static function isBannedFromSubject($userId, $subjectId)
    {
        $ban = self::where('user_id', $userId)
                  ->where('subject_id', $subjectId)
                  ->where('is_active', true)
                  ->first();

        return $ban ? $ban : false;
    }

    /**
     * Reactivate a ban and record a new violation.
     */
    public function reactivateAndRecordViolation($violationType, $violations, $reason = null)
    {
        $this->load('user', 'subject');

        $this->ban_count += 1;
        $this->is_active = true;
        $this->reactivated_at = null;
        $this->reactivated_by = null;
        $this->reactivation_reason = null;
        $this->banned_at = now();
        $this->violation_type = $violationType;
        $this->total_violations = $violations->count();

        $this->ban_reason = $reason ?? self::generateBanReason($violationType, $violations->count(), $this->ban_count);

        $newViolationDetails = $violations->map(function ($violation) {
            return [
                'type' => $violation->violation_type,
                'description' => $violation->description,
                'occurred_at' => $violation->occurred_at->toISOString(),
                'student_identification' => [
                    'registration_number' => $this->user->registration_number ?? 'N/A',
                    'email' => $this->user->email,
                    'name' => $this->user->name,
                    'user_id' => $this->user->id
                ],
                'tracking_method' => 'registration_and_email_based',
                'user_agent' => $violation->user_agent,
                'metadata' => $violation->metadata,
                'note' => 'Student tracked by registration number and email, NOT IP address'
            ];
        })->toArray();

        $this->violation_details = array_merge($this->violation_details ?? [], $newViolationDetails);

        // Here we can reuse the offender classification logic.
        $previousBansInSubject = $this->ban_count - 1;
        $bansInOtherSubjects = self::where('user_id', $this->user_id)
            ->where('subject_id', '!=', $this->subject_id)
            ->count();
        $totalBansAcrossAllSubjects = $previousBansInSubject + $bansInOtherSubjects;

        $offenderType = 'repeat_subject_offender';
        if ($bansInOtherSubjects > 0) {
            $offenderType = 'cross_subject_offender';
        }
        if ($totalBansAcrossAllSubjects >= 3) {
            $offenderType = 'chronic_offender';
        }

        $this->admin_notes = "RE-BANNED: Student {$this->user->name} (Reg: {$this->user->registration_number} | Email: {$this->user->email}) - {$offenderType} - Banned again after {$violations->count()} {$violationType} violation(s) for {$this->subject->name} ONLY. This is ban #{$this->ban_count} for this subject. Total bans across all subjects: " . ($totalBansAcrossAllSubjects + 1) . ".";

        $this->save();
        return $this;
    }
    
    /**
     * Backward compatibility alias for isBannedFromSubject
     * @deprecated Use isBannedFromSubject() instead
     */
    public static function isBanned($userId, $subjectId)
    {
        $ban = self::isBannedFromSubject($userId, $subjectId);
        return $ban ? true : false;
    }
    
    /**
     * Check if violations should trigger a ban for specific violation type
     */
    public static function shouldTriggerBan($userId, $subjectId, $violationType)
    {
        $violationCount = \App\Models\ExamSecurityViolation::getViolationCount($userId, $subjectId, $violationType);
        $threshold = self::getThresholdForViolation($violationType);
        
        return $violationCount >= $threshold;
    }
    
    /**
     * Get violation threshold for different violation types
     */
    public static function getThresholdForViolation($violationType)
    {
        switch ($violationType) {
            case self::VIOLATION_TAB_SWITCH:
                return self::TAB_SWITCH_THRESHOLD; // 1 = immediate ban
            case self::VIOLATION_RIGHT_CLICK:
                return self::RIGHT_CLICK_THRESHOLD; // 15 strikes
            case self::VIOLATION_COPY_PASTE:
                return 1; // Immediate ban
            case self::VIOLATION_BROWSER_NAVIGATION:
                return 1; // Immediate ban
            default:
                return 3; // Default 3 strikes
        }
    }
    
    /**
     * Generate appropriate ban reason based on violation type
     */
    public static function generateBanReason($violationType, $violationCount, $banCount = 1)
    {
        $repeatOffenderText = $banCount > 1 ? " (REPEAT OFFENDER - Ban #{$banCount})" : "";
        
        switch ($violationType) {
            case self::VIOLATION_TAB_SWITCH:
                return "🚫 IMMEDIATE BAN: Tab switching detected during exam. This violates exam integrity rules.{$repeatOffenderText} Contact administrator for reactivation.";
            case self::VIOLATION_RIGHT_CLICK:
                return "🚫 15-STRIKE BAN: {$violationCount} right-click violations detected. This violates exam security policy.{$repeatOffenderText} Contact administrator for reactivation.";
            case self::VIOLATION_COPY_PASTE:
                return "🚫 COPY/PASTE BAN: Unauthorized copy-paste activity detected during exam.{$repeatOffenderText} Contact administrator for reactivation.";
            case self::VIOLATION_BROWSER_NAVIGATION:
                return "🚫 NAVIGATION BAN: Unauthorized browser navigation detected during exam.{$repeatOffenderText} Contact administrator for reactivation.";
            default:
                return "🚫 SECURITY VIOLATION BAN: Multiple exam security violations detected.{$repeatOffenderText} Contact administrator for reactivation.";
        }
    }

    /**
     * Get ban details for a user and subject
     */
    public static function getBanDetails($userId, $subjectId)
    {
        return self::where('user_id', $userId)
                  ->where('subject_id', $subjectId)
                  ->with(['user', 'subject', 'bannedByAdmin'])
                  ->first();
    }

    /**
     * Get all active bans for admin dashboard
     */
    public static function getActiveBans()
    {
        return self::with(['user', 'subject', 'bannedByAdmin'])
                  ->where(function ($query) {
                      $query->where('is_permanent', true)
                            ->orWhere('ban_expires_at', '>', now());
                  })
                  ->orderBy('banned_at', 'desc')
                  ->get();
    }

    /**
     * Get recent bans for admin dashboard
     */
    public static function getRecentBans($days = 7)
    {
        return self::with(['user', 'subject', 'bannedByAdmin'])
                  ->where('banned_at', '>=', Carbon::now()->subDays($days))
                  ->orderBy('banned_at', 'desc')
                  ->get();
    }

    /**
     * Check if ban is still active
     * ADMIN REQUIREMENT: Bans stay active until admin reactivation only
     */
    public function isActive()
    {
        // All active bans remain active until admin reactivation
        // No automatic expiration - only admin can reactivate
        return $this->is_active;
    }

    /**
     * Get formatted ban duration
     */
    public function getBanDurationAttribute()
    {
        if ($this->is_permanent) {
            return 'Permanent';
        }

        if ($this->ban_expires_at) {
            return $this->banned_at->diffForHumans($this->ban_expires_at, true);
        }

        return 'Unknown';
    }
    
    /**
     * Get comprehensive offender statistics for a user
     * ENHANCED: Advanced offender pattern analysis
     */
    public static function getOffenderStatistics($userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return null;
        }
        
        $bans = self::where('user_id', $userId)->with('subject')->get();
        $violations = \App\Models\ExamSecurityViolation::where('user_id', $userId)->with('subject')->get();
        
        $subjectStats = [];
        foreach ($bans->groupBy('subject_id') as $subjectId => $subjectBans) {
            $subject = $subjectBans->first()->subject;
            $subjectViolations = $violations->where('subject_id', $subjectId);
            
            $subjectStats[$subjectId] = [
                'subject_name' => $subject->name ?? 'Unknown',
                'total_bans' => $subjectBans->count(),
                'total_violations' => $subjectViolations->count(),
                'violation_types' => $subjectViolations->pluck('violation_type')->unique()->values()->toArray(),
                'first_violation' => $subjectViolations->min('occurred_at'),
                'last_violation' => $subjectViolations->max('occurred_at'),
                'is_currently_banned' => $subjectBans->where('is_active', true)->count() > 0
            ];
        }
        
        return [
            'user_info' => [
                'user_id' => $userId,
                'name' => $user->name,
                'registration_number' => $user->registration_number,
                'email' => $user->email
            ],
            'overall_statistics' => [
                'total_bans_across_all_subjects' => $bans->count(),
                'total_violations_across_all_subjects' => $violations->count(),
                'subjects_with_violations' => count($subjectStats),
                'currently_banned_subjects' => $bans->where('is_active', true)->count(),
                'offender_classification' => self::classifyOffender($bans->count(), count($subjectStats))
            ],
            'subject_breakdown' => $subjectStats,
            'violation_timeline' => $violations->sortBy('occurred_at')->map(function($v) {
                return [
                    'date' => $v->occurred_at,
                    'subject' => $v->subject->name ?? 'Unknown',
                    'type' => $v->violation_type,
                    'description' => $v->description
                ];
            })->values()->toArray()
        ];
    }
    
    /**
     * Classify offender based on ban patterns
     */
    private static function classifyOffender($totalBans, $subjectsAffected)
    {
        if ($totalBans === 0) {
            return 'clean_record';
        } elseif ($totalBans === 1 && $subjectsAffected === 1) {
            return 'first_time_offender';
        } elseif ($totalBans > 1 && $subjectsAffected === 1) {
            return 'repeat_subject_offender';
        } elseif ($subjectsAffected > 1 && $totalBans <= 3) {
            return 'cross_subject_offender';
        } elseif ($totalBans > 3 || $subjectsAffected > 2) {
            return 'chronic_offender';
        } else {
            return 'moderate_offender';
        }
    }
}