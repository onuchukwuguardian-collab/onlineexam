<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;

echo "BANNED STUDENTS DATA CHECK\n";
echo "=========================\n\n";

// Current active bans
$activeBans = ExamBan::with(['user', 'subject'])->where('is_active', true)->get();
echo "Current active banned students: " . $activeBans->count() . "\n\n";

foreach ($activeBans as $ban) {
    echo "Ban ID: {$ban->id}\n";
    echo "Student: {$ban->user->name}\n";
    echo "Email: {$ban->user->email}\n";
    echo "Subject: {$ban->subject->name}\n";
    echo "Active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

// Search for Mium John
echo "\nSearching for 'Mium John':\n";
$miumUsers = User::where('name', 'like', '%Mium%')->get();
foreach ($miumUsers as $user) {
    echo "Found: {$user->name} (ID: {$user->id})\n";
    $bans = ExamBan::where('user_id', $user->id)->with('subject')->get();
    foreach ($bans as $ban) {
        echo "  - Subject: {$ban->subject->name}, Active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
    }
}

// Search for Biology subjects
echo "\nSearching for Biology subjects:\n";
$biologySubjects = Subject::where('name', 'like', '%Biology%')->orWhere('name', 'like', '%BIOLOGY%')->get();
foreach ($biologySubjects as $subject) {
    echo "Found: {$subject->name} (ID: {$subject->id})\n";
}