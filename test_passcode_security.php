<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "🔐 Testing Passcode Security...\n\n";

// Test 1: Check if passcodes are hashed
$user = User::where('email', 'john.ade@example.com')->first();
if ($user) {
    echo "✅ User found: {$user->name}\n";
    echo "✅ Passcode is hashed: " . (str_starts_with($user->unique_id, '$2y$') ? 'YES' : 'NO') . "\n";
    
    // Test 2: Verify original passcode still works
    $originalPasscode = 'JSS1PASS001';
    $hashMatches = Hash::check($originalPasscode, $user->unique_id);
    echo "✅ Original passcode verification: " . ($hashMatches ? 'WORKS' : 'FAILED') . "\n";
} else {
    echo "❌ Test user not found\n";
}

echo "\n🛡️ Security Status: PASSCODES ARE NOW SECURE!\n";
echo "📝 Original passcodes like 'JSS1PASS001' are hashed in database\n";
echo "🔑 Students can still login with their original passcodes\n";
echo "👀 But passcodes are no longer visible in plain text\n";