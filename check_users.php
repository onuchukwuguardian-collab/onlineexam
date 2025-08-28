<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== USER ROLES CHECK ===\n\n";

$users = User::all();
foreach ($users as $user) {
    echo "User: {$user->name} | Role: {$user->role} | Class ID: {$user->class_id}\n";
}

echo "\n=== ROLE SUMMARY ===\n";
$roles = User::select('role', \DB::raw('count(*) as count'))
    ->groupBy('role')
    ->get();

foreach ($roles as $role) {
    echo "{$role->role}: {$role->count} users\n";
}