<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== STUDENT MIDDLEWARE TEST ===\n\n";

// Test students with different roles
$users = User::whereIn('role', ['student', 'user', 'admin'])->get();

foreach ($users as $user) {
    echo "User: {$user->name}\n";
    echo "Role: {$user->role}\n";
    echo "Class ID: " . ($user->class_id ?? 'NULL') . "\n";
    echo "isStudent(): " . ($user->isStudent() ? 'YES' : 'NO') . "\n";
    echo "isAdmin(): " . ($user->isAdmin() ? 'YES' : 'NO') . "\n";
    
    // Check if user would pass the middleware
    $wouldPass = $user->isStudent() && $user->class_id;
    echo "Would pass student middleware: " . ($wouldPass ? 'YES' : 'NO') . "\n";
    
    if (!$wouldPass) {
        if (!$user->isStudent()) {
            echo "  ❌ Reason: Not a student\n";
        } elseif (!$user->class_id) {
            echo "  ❌ Reason: No class assigned\n";
        }
    } else {
        echo "  ✅ Can access student routes\n";
    }
    
    echo "\n";
}

echo "=== TEST COMPLETE ===\n";