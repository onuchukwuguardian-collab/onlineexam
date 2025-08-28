<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING BAN ISSUE ===\n\n";

try {
    // Check Ban ID 1
    $ban = App\Models\ExamBan::find(1);
    
    if ($ban) {
        echo "📋 BAN DETAILS FOR ID 1:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👤 Student: " . $ban->user->name . "\n";
        echo "📧 Email: " . $ban->user->email . "\n"; 
        echo "📚 Subject: " . $ban->subject->name . "\n";
        echo "🚫 Total Violations in Ban Record: " . $ban->total_violations . "\n";
        echo "📅 Banned At: " . $ban->banned_at . "\n";
        echo "📝 Ban Reason: " . $ban->ban_reason . "\n";
        echo "🔴 Is Active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
        
        // Check actual violations in database
        $actualViolations = App\Models\ExamSecurityViolation::where('user_id', $ban->user_id)
            ->where('subject_id', $ban->subject_id)
            ->where('violation_type', 'tab_switch')
            ->get();
            
        echo "\n📊 ACTUAL VIOLATIONS IN DATABASE:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔢 Total Tab Switch Violations: " . $actualViolations->count() . "\n\n";
        
        if ($actualViolations->count() > 0) {
            echo "📝 VIOLATION DETAILS:\n";
            foreach ($actualViolations as $i => $violation) {
                echo "   " . ($i + 1) . ". " . $violation->description . "\n";
                echo "      ⏰ Time: " . $violation->occurred_at . "\n";
                echo "      🌐 IP: " . $violation->ip_address . "\n\n";
            }
        }
        
        // Check if the issue is in the ExamController logic
        echo "🔍 DIAGNOSIS:\n";
        echo "━━━━━━━━━━━━━━━━━━\n";
        if ($actualViolations->count() < 3) {
            echo "❌ PROBLEM FOUND: Student was banned with only " . $actualViolations->count() . " violation(s)\n";
            echo "✅ SHOULD BE: Student should only be banned after 3 violations\n";
            echo "🔧 ACTION NEEDED: Fix ExamController logic or reactivate student\n";
        } else {
            echo "✅ CORRECT: Student had 3+ violations and was properly banned\n";
        }
        
    } else {
        echo "❌ Ban ID 1 not found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}