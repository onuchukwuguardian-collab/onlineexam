<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\ExamBan;

echo "=== REACTIVATE BUTTON LOCATION HELPER ===\n\n";

// 1. Check if there are admin users
echo "🔍 CHECKING ADMIN USERS:\n";
echo "════════════════════════════\n";

$admins = User::where('role', 'admin')->get();
if ($admins->count() === 0) {
    echo "❌ No admin users found! You need admin role to see reactivate buttons.\n";
    echo "   Creating a test admin user...\n\n";
    
    // Create admin user if none exists
    $admin = User::create([
        'name' => 'Test Administrator',
        'email' => 'admin@test.com',
        'password' => bcrypt('admin123'),
        'role' => 'admin',
        'registration_number' => 'ADMIN001',
        'unique_id' => 'test-admin-001'
    ]);
    
    echo "✅ Test admin created:\n";
    echo "   Email: admin@test.com\n";
    echo "   Password: admin123\n";
    echo "   Role: admin\n\n";
} else {
    echo "✅ Found {$admins->count()} admin user(s):\n";
    foreach ($admins as $admin) {
        echo "   • {$admin->name} ({$admin->email}) - Role: {$admin->role}\n";
    }
    echo "\n";
}

// 2. Check banned students
echo "🚫 CHECKING BANNED STUDENTS:\n";
echo "═══════════════════════════════\n";

$bannedStudents = ExamBan::where('is_active', true)
    ->with(['user', 'subject'])
    ->get();

if ($bannedStudents->count() === 0) {
    echo "❌ No banned students found. No reactivate buttons will appear.\n";
    echo "   You need banned students to see reactivate buttons.\n\n";
} else {
    echo "✅ Found {$bannedStudents->count()} banned student(s):\n";
    foreach ($bannedStudents as $ban) {
        echo "   • {$ban->user->name} ({$ban->user->email})\n";
        echo "     Subject: {$ban->subject->name}\n";
        echo "     Ban ID: {$ban->id}\n";
        echo "     Banned: {$ban->banned_at->format('Y-m-d H:i:s')}\n";
        echo "     Admin URL: /admin/security/bans/{$ban->id}\n";
        echo "   " . str_repeat("-", 40) . "\n";
    }
}

// 3. Exact URLs to visit
echo "\n📍 EXACT STEPS TO FIND REACTIVATE BUTTON:\n";
echo "══════════════════════════════════════════════════\n";

echo "1. 🔐 LOGIN AS ADMIN:\n";
echo "   • Go to: /login\n";
if ($admins->count() > 0) {
    $firstAdmin = $admins->first();
    echo "   • Use admin email: {$firstAdmin->email}\n";
} else {
    echo "   • Use admin email: admin@test.com\n";
    echo "   • Use password: admin123\n";
}
echo "\n";

echo "2. 🌐 GO TO ADMIN DASHBOARD:\n";
echo "   • Visit: /admin/dashboard\n";
echo "   • Look for 'Security Violations' in the left sidebar\n\n";

echo "3. 🚨 ACCESS SECURITY SECTION:\n";
echo "   • Click 'Security Violations' in sidebar\n";
echo "   • OR visit directly: /admin/security\n\n";

echo "4. 🔄 FIND BANNED STUDENTS PAGE:\n";
echo "   • Look for 'Banned Students Management' button\n";
echo "   • OR visit directly: /admin/security/banned-students\n\n";

if ($bannedStudents->count() > 0) {
    echo "5. 👀 LOOK FOR RED REACTIVATE BUTTONS:\n";
    echo "   • You should see a table with banned students\n";
    echo "   • Each row should have a RED 'Reactivate' button\n";
    echo "   • Buttons are in the 'Actions' column on the right\n\n";
    
    echo "6. 🎯 SPECIFIC STUDENTS TO LOOK FOR:\n";
    foreach ($bannedStudents as $ban) {
        echo "   • {$ban->user->name} - Subject: {$ban->subject->name}\n";
    }
    echo "\n";
} else {
    echo "5. ❌ NO REACTIVATE BUTTONS WILL APPEAR:\n";
    echo "   • No banned students = No reactivate buttons\n";
    echo "   • You need to have banned students first\n\n";
}

// 4. Browser testing URLs
echo "🌐 DIRECT BROWSER URLS TO TEST:\n";
echo "═══════════════════════════════════\n";
echo "• Login page: http://localhost/login\n";
echo "• Admin dashboard: http://localhost/admin/dashboard\n";
echo "• Security page: http://localhost/admin/security\n";
echo "• Banned students: http://localhost/admin/security/banned-students\n";

if ($bannedStudents->count() > 0) {
    echo "\n• Specific ban details:\n";
    foreach ($bannedStudents as $ban) {
        echo "  - Ban {$ban->id}: http://localhost/admin/security/bans/{$ban->id}\n";
    }
}

// 5. What to look for
echo "\n👀 WHAT THE REACTIVATE BUTTON LOOKS LIKE:\n";
echo "════════════════════════════════════════════\n";
echo "• COLOR: RED background (bg-red-600)\n";
echo "• TEXT: 'Reactivate'\n";
echo "• ICON: Unlock icon (fas fa-unlock-alt)\n";
echo "• LOCATION: In the 'Actions' column of the banned students table\n";
echo "• HOVER EFFECT: Darker red when you hover over it\n\n";

// 6. Troubleshooting
echo "🔧 TROUBLESHOOTING:\n";
echo "══════════════════════\n";
echo "IF YOU DON'T SEE THE BUTTON:\n";
echo "1. ❓ Are you logged in as ADMIN? (Check your role)\n";
echo "2. ❓ Are there banned students? (Check the count above)\n";
echo "3. ❓ Are you on the right page? (/admin/security/banned-students)\n";
echo "4. ❓ Try refreshing the page (Ctrl+F5)\n";
echo "5. ❓ Check browser console for JavaScript errors (F12)\n\n";

echo "🎯 SUMMARY:\n";
echo "═══════════════\n";
if ($bannedStudents->count() > 0) {
    echo "✅ You have {$bannedStudents->count()} banned students\n";
    echo "✅ Reactivate buttons SHOULD be visible\n";
    echo "✅ Visit: /admin/security/banned-students\n";
    echo "✅ Look for RED 'Reactivate' buttons\n\n";
} else {
    echo "❌ You have 0 banned students\n";
    echo "❌ NO reactivate buttons will appear\n";
    echo "❌ Need to create banned students first\n\n";
}

echo "🏁 Next step: Login as admin and visit /admin/security/banned-students\n";