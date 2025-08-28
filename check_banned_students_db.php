<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 CHECKING DATABASE FOR BANNED STUDENTS\n";
echo "========================================\n\n";

// Check 1: Count total violations
echo "1. Checking exam_security_violations table:\n";
try {
    $totalViolations = DB::table('exam_security_violations')->count();
    echo "   Total violations: {$totalViolations}\n";
    
    if ($totalViolations > 0) {
        $recentViolations = DB::table('exam_security_violations')
            ->select('violation_type', 'description', 'user_id', 'subject_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        echo "   Recent violations:\n";
        foreach ($recentViolations as $v) {
            echo "     - User {$v->user_id}, Subject {$v->subject_id}: {$v->violation_type} - {$v->description}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error checking violations: {$e->getMessage()}\n";
}

// Check 2: Count exam bans
echo "\n2. Checking exam_bans table:\n";
try {
    $totalBans = DB::table('exam_bans')->count();
    echo "   Total bans: {$totalBans}\n";
    
    $activeBans = DB::table('exam_bans')->where('is_active', true)->count();
    echo "   Active bans: {$activeBans}\n";
    
    if ($activeBans > 0) {
        $recentBans = DB::table('exam_bans')
            ->join('users', 'exam_bans.user_id', '=', 'users.id')
            ->join('subjects', 'exam_bans.subject_id', '=', 'subjects.id')
            ->select('users.name as user_name', 'subjects.name as subject_name', 'exam_bans.ban_reason', 'exam_bans.created_at')
            ->where('exam_bans.is_active', true)
            ->orderBy('exam_bans.created_at', 'desc')
            ->limit(5)
            ->get();
        
        echo "   Recent active bans:\n";
        foreach ($recentBans as $ban) {
            echo "     - {$ban->user_name} banned from {$ban->subject_name}: {$ban->ban_reason}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error checking bans: {$e->getMessage()}\n";
}

// Check 3: Test the admin query that should find banned students
echo "\n3. Testing admin banned students query:\n";
try {
    $violationBasedBans = DB::table('exam_security_violations as v')
        ->join('users as u', 'v.user_id', '=', 'u.id')
        ->join('subjects as s', 'v.subject_id', '=', 's.id')
        ->select(
            'u.name as user_name',
            'u.email as user_email',
            's.name as subject_name',
            'v.violation_type',
            'v.description',
            'v.created_at'
        )
        ->where(function($query) {
            $query->where('v.description', 'like', '%NO MERCY%')
                  ->orWhere('v.description', 'like', '%banned%')
                  ->orWhere('v.description', 'like', '%3-strike%')
                  ->orWhere('v.description', 'like', '%IMMEDIATE BAN%')
                  ->orWhere('v.description', 'like', '%immediate ban%')
                  ->orWhere('v.description', 'like', '%IMMEDIATELY BANNED%')
                  ->orWhere('v.description', 'like', '%immediately banned%')
                  ->orWhere('v.description', 'like', '%permanently banned%')
                  ->orWhere('v.description', 'like', '%PERMANENTLY BANNED%')
                  ->orWhere('v.description', 'like', '%FINAL WARNING%')
                  ->orWhere('v.violation_type', 'tab_switch')
                  ->orWhere('v.violation_type', 'tab_switch_attempt');
        })
        ->orderBy('v.created_at', 'desc')
        ->get();
    
    echo "   Violation-based bans found: {$violationBasedBans->count()}\n";
    
    if ($violationBasedBans->count() > 0) {
        echo "   Found banned students:\n";
        foreach ($violationBasedBans as $ban) {
            echo "     - {$ban->user_name} ({$ban->user_email}) banned from {$ban->subject_name}\n";
            echo "       Type: {$ban->violation_type}, Description: {$ban->description}\n";
        }
    } else {
        echo "   ❌ No violation-based bans found\n";
        echo "   This suggests either:\n";
        echo "     a) No violations recorded yet\n";
        echo "     b) Violations recorded but descriptions don't match search patterns\n";
        echo "     c) Tab switching detection isn't working\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error testing admin query: {$e->getMessage()}\n";
}

// Check 4: Look for any tab switch violations specifically
echo "\n4. Checking specifically for tab switch violations:\n";
try {
    $tabSwitchViolations = DB::table('exam_security_violations')
        ->where('violation_type', 'tab_switch')
        ->orWhere('violation_type', 'tab_switch_attempt')
        ->count();
    
    echo "   Tab switch violations: {$tabSwitchViolations}\n";
    
    if ($tabSwitchViolations > 0) {
        $details = DB::table('exam_security_violations')
            ->join('users', 'exam_security_violations.user_id', '=', 'users.id')
            ->join('subjects', 'exam_security_violations.subject_id', '=', 'subjects.id')
            ->select('users.name', 'subjects.name as subject', 'exam_security_violations.violation_type', 'exam_security_violations.description')
            ->where('exam_security_violations.violation_type', 'tab_switch')
            ->orWhere('exam_security_violations.violation_type', 'tab_switch_attempt')
            ->get();
        
        echo "   Details:\n";
        foreach ($details as $detail) {
            echo "     - {$detail->name} in {$detail->subject}: {$detail->violation_type}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error checking tab switch violations: {$e->getMessage()}\n";
}

echo "\n✅ Database check complete!\n";