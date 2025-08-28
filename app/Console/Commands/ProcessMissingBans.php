<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMissingBans extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bans:process-missing {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process violations that should have created bans but haven\'t been processed yet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 PROCESSING MISSING BANS');
        $this->info('=' . str_repeat('=', 50));
        
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }
        
        // Get current statistics
        $totalViolations = ExamSecurityViolation::count();
        $totalBans = ExamBan::count();
        $activeBans = ExamBan::where('is_active', true)->count();
        
        $this->info("📊 Current Statistics:");
        $this->info("   • Total Violations: {$totalViolations}");
        $this->info("   • Total Bans: {$totalBans}");
        $this->info("   • Active Bans: {$activeBans}");
        $this->newLine();
        
        // Process tab switch violations (immediate ban)
        $this->info('🚨 Processing Tab Switch Violations (Immediate Ban)');
        $this->info('-' . str_repeat('-', 55));
        
        $tabSwitchViolations = DB::table('exam_security_violations as v')
            ->select('v.user_id', 'v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
            ->where('v.violation_type', 'tab_switch')
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('exam_bans')
                      ->whereColumn('exam_bans.user_id', 'v.user_id')
                      ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                      ->where('exam_bans.is_active', true);
            })
            ->groupBy('v.user_id', 'v.subject_id')
            ->get();
            
        $this->info("Found {$tabSwitchViolations->count()} tab switch violations needing bans:");
        
        $tabSwitchBansCreated = 0;
        foreach ($tabSwitchViolations as $violation) {
            $user = User::find($violation->user_id);
            $subject = Subject::find($violation->subject_id);
            
            if ($user && $subject) {
                $this->line("   • {$user->name} ({$user->email}) - {$subject->name}: {$violation->violation_count} violations");
                
                if (!$isDryRun) {
                    $banCreated = $this->createBanRecord($violation->user_id, $violation->subject_id, 'tab_switch', $violation->violation_count, $violation->latest_violation);
                    if ($banCreated) {
                        $tabSwitchBansCreated++;
                        $this->info("     ✅ Ban created successfully");
                    } else {
                        $this->error("     ❌ Failed to create ban");
                    }
                }
            } else {
                $this->error("   • Missing user or subject for violation: User {$violation->user_id}, Subject {$violation->subject_id}");
            }
        }
        
        $this->newLine();
        
        // Process right-click violations (15+ strikes)
        $this->info('🖱️  Processing Right-Click Violations (15+ Strikes)');
        $this->info('-' . str_repeat('-', 55));
        
        $rightClickViolations = DB::table('exam_security_violations as v')
            ->select('v.user_id', 'v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
            ->where('v.violation_type', 'right_click')
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('exam_bans')
                      ->whereColumn('exam_bans.user_id', 'v.user_id')
                      ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                      ->where('exam_bans.is_active', true);
            })
            ->groupBy('v.user_id', 'v.subject_id')
            ->havingRaw('COUNT(*) >= 15')
            ->get();
            
        $this->info("Found {$rightClickViolations->count()} right-click violations needing bans:");
        
        $rightClickBansCreated = 0;
        foreach ($rightClickViolations as $violation) {
            $user = User::find($violation->user_id);
            $subject = Subject::find($violation->subject_id);
            
            if ($user && $subject) {
                $this->line("   • {$user->name} ({$user->email}) - {$subject->name}: {$violation->violation_count} violations");
                
                if (!$isDryRun) {
                    $banCreated = $this->createBanRecord($violation->user_id, $violation->subject_id, 'right_click', $violation->violation_count, $violation->latest_violation);
                    if ($banCreated) {
                        $rightClickBansCreated++;
                        $this->info("     ✅ Ban created successfully");
                    } else {
                        $this->error("     ❌ Failed to create ban");
                    }
                }
            } else {
                $this->error("   • Missing user or subject for violation: User {$violation->user_id}, Subject {$violation->subject_id}");
            }
        }
        
        $this->newLine();
        
        // Final statistics
        if (!$isDryRun) {
            $this->info('📈 RESULTS SUMMARY');
            $this->info('=' . str_repeat('=', 50));
            $this->info("✅ Tab Switch Bans Created: {$tabSwitchBansCreated}");
            $this->info("✅ Right-Click Bans Created: {$rightClickBansCreated}");
            $this->info("🎯 Total Bans Created: " . ($tabSwitchBansCreated + $rightClickBansCreated));
            
            // Get updated statistics
            $newTotalBans = ExamBan::count();
            $newActiveBans = ExamBan::where('is_active', true)->count();
            
            $this->newLine();
            $this->info('📊 Updated Statistics:');
            $this->info("   • Total Violations: {$totalViolations} (unchanged)");
            $this->info("   • Total Bans: {$newTotalBans} (was {$totalBans})");
            $this->info("   • Active Bans: {$newActiveBans} (was {$activeBans})");
            
            $this->newLine();
            $this->info('🔄 Now students who were banned will see their actual ban status on the reactivation page!');
        } else {
            $this->info('📋 DRY RUN COMPLETE - No changes made');
            $this->info("Would create {$tabSwitchViolations->count()} tab switch bans and {$rightClickViolations->count()} right-click bans");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Create a ban record for a specific violation
     */
    private function createBanRecord($userId, $subjectId, $violationType, $violationCount, $latestViolation)
    {
        try {
            $user = User::find($userId);
            $subject = Subject::find($subjectId);
            
            if (!$user || !$subject) {
                return false;
            }
            
            // Get all violations for this user and subject
            $violations = ExamSecurityViolation::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('violation_type', $violationType)
                ->orderBy('occurred_at', 'desc')
                ->get();
                
            // Check for previous bans to determine ban count
            $previousBans = ExamBan::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->count();
                
            $banCount = $previousBans + 1;
            
            // Create ban reason
            $banReason = '';
            if ($violationType === 'tab_switch') {
                $banReason = "🚫 IMMEDIATE BAN: Tab switching detected during exam. This violates exam integrity rules. Contact administrator for reactivation.";
            } elseif ($violationType === 'right_click') {
                $banReason = "🚫 15-STRIKE BAN: {$violationCount} right-click violations detected. This violates exam security policy. Contact administrator for reactivation.";
            }
            
            // Create the ban record
            $ban = ExamBan::create([
                'user_id' => $userId,
                'subject_id' => $subjectId,
                'ban_reason' => $banReason,
                'violation_details' => json_encode($violations->map(function($v) {
                    return [
                        'type' => $v->violation_type,
                        'description' => $v->description,
                        'occurred_at' => $v->occurred_at->toISOString(),
                        'user_agent' => $v->user_agent,
                        'ip_address' => $v->ip_address
                    ];
                })->toArray()),
                'violation_type' => $violationType,
                'total_violations' => $violationCount,
                'banned_at' => $latestViolation,
                'is_permanent' => true,
                'is_active' => true,
                'ban_count' => $banCount,
                'admin_notes' => "AUTO-CREATED BAN (via command): Student {$user->name} (Reg: {$user->registration_number}) - Banned after {$violationCount} {$violationType} violation(s) for {$subject->name} only. Ban #{$banCount} for this subject."
            ]);
            
            Log::info("Auto-created missing ban record via command", [
                'ban_id' => $ban->id,
                'user_id' => $userId,
                'subject_id' => $subjectId,
                'violation_type' => $violationType,
                'violation_count' => $violationCount,
                'ban_count' => $banCount
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to create ban record via command: ' . $e->getMessage());
            return false;
        }
    }
}