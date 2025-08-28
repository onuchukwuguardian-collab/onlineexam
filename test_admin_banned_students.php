<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\SecurityViolationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

echo "🔍 TESTING ADMIN BANNED STUDENTS CONTROLLER\n";
echo "===========================================\n\n";

try {
    // Create a test request
    $request = new Request();
    
    // Create controller instance
    $controller = new SecurityViolationController();
    
    echo "📊 Calling bannedStudents() method...\n";
    
    // Call the bannedStudents method directly
    $response = $controller->bannedStudents($request);
    
    // Get the view data
    if (method_exists($response, 'getData')) {
        $data = $response->getData();
        
        echo "\n📋 Response Data:\n";
        foreach ($data as $key => $value) {
            if ($key === 'bannedStudents') {
                if (method_exists($value, 'count')) {
                    echo "  - {$key}: {$value->count()} items\n";
                    if ($value->count() > 0) {
                        echo "    Sample items:\n";
                        foreach ($value->take(3) as $item) {
                            echo "      - ID: {$item->id}, User: " . ($item->user->name ?? 'Unknown') . "\n";
                        }
                    }
                } else {
                    echo "  - {$key}: " . gettype($value) . "\n";
                }
            } elseif ($key === 'stats') {
                echo "  - {$key}:\n";
                foreach ($value as $statKey => $statValue) {
                    echo "    - {$statKey}: {$statValue}\n";
                }
            } else {
                echo "  - {$key}: " . (is_scalar($value) ? $value : gettype($value)) . "\n";
            }
        }
    } else {
        echo "Response type: " . get_class($response) . "\n";
    }
    
    echo "\n✅ Controller method executed successfully!\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error calling controller method:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🔍 DIRECT DATABASE QUERY TEST\n";
echo "=============================\n";

try {
    // Test the exact query from the controller
    $activeBansQuery = DB::table('exam_bans as b')
        ->join('users as u', 'b.user_id', '=', 'u.id')
        ->join('subjects as s', 'b.subject_id', '=', 's.id')
        ->select(
            'b.id as ban_id',
            'u.id as user_id',
            'u.name as user_name',
            'u.email as user_email', 
            'u.registration_number',
            's.id as subject_id',
            's.name as subject_name',
            'b.ban_reason',
            'b.created_at as banned_at'
        )
        ->where('b.is_active', true)
        ->whereNull('b.reactivated_at')
        ->get();

    echo "Active bans query result: " . $activeBansQuery->count() . " records\n";

    $violationQuery = DB::table('exam_security_violations as v')
        ->join('users as u', 'v.user_id', '=', 'u.id')
        ->join('subjects as s', 'v.subject_id', '=', 's.id')
        ->select('u.name as user_name', 's.name as subject_name', 'v.violation_type', 'v.description')
        ->where(function($query) {
            $query->where('v.description', 'like', '%NO MERCY%')
                  ->orWhere('v.description', 'like', '%banned%')
                  ->orWhere('v.description', 'like', '%15-STRIKE BAN%')
                  ->orWhere('v.description', 'like', '%15-strike%')
                  ->orWhere('v.description', 'like', '%IMMEDIATE BAN%')
                  ->orWhere('v.description', 'like', '%immediate ban%')
                  ->orWhere('v.description', 'like', '%IMMEDIATELY BANNED%')
                  ->orWhere('v.description', 'like', '%immediately banned%')
                  ->orWhere('v.description', 'like', '%permanently banned%')
                  ->orWhere('v.description', 'like', '%PERMANENTLY BANNED%')
                  ->orWhere('v.description', 'like', '%FINAL WARNING%')
                  ->orWhere('v.violation_type', 'tab_switch')
                  ->orWhere('v.violation_type', 'tab_switch_attempt')
                  ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type = "right_click") >= 15');
        })
        ->get();

    echo "Violation-based query result: " . $violationQuery->count() . " records\n";
    echo "Combined total: " . ($activeBansQuery->count() + $violationQuery->count()) . " records\n";

} catch (\Exception $e) {
    echo "❌ Database query error: " . $e->getMessage() . "\n";
}