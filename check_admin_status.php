<?php
   
   require __DIR__ . '/vendor/autoload.php';
   $app = require_once __DIR__ . '/bootstrap/app.php';
   $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
   
   use Illuminate\Support\Facades\Auth;
   use App\Models\User;
   
   if (Auth::check()) {
       $user = Auth::user();
       echo "✅ You are logged in as: {$user->name} ({$user->email})\n";
       echo "🔐 Your role: {$user->role}\n";
       if ($user->role === 'admin') {
           echo "✅ You are an ADMIN - reactivate buttons should be visible\n";
       } else {
           echo "❌ You are NOT an admin - you won't see reactivate buttons\n";
       }
   } else {
       echo "❌ You are NOT logged in\n";
   }
   