<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Ensure User model is imported if needed for custom logic

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $identifier = trim($request->input('identifier'));
        $password = $request->input('password');

        // Attempt to log in with email
        if (Auth::attempt(['email' => $identifier, 'password' => $password], $request->filled('remember'))) {
            $request->session()->regenerate();
            return $this->authenticated($request, Auth::user());
        }

        // Attempt to log in with registration_number
        if (Auth::attempt(['registration_number' => $identifier, 'password' => $password], $request->filled('remember'))) {
            $request->session()->regenerate();
            return $this->authenticated($request, Auth::user());
        }

        // Attempt to log in with unique_id (school passcode)
        // This requires custom logic since 'unique_id' isn't a standard credential field.
        // We must fetch all users and check the hashed passcode for each. This is inefficient
        // but necessary if we can't query by a hashed value directly in the database.
        // NOTE: This could be slow with a large number of users.
        $users = User::where('email', '!=', $identifier)
            ->where('registration_number', '!=', $identifier)
            ->get();

        foreach ($users as $user) {
            // Check if the provided identifier matches the user's hashed unique_id
            if (Hash::check($identifier, $user->unique_id)) {
                // If the unique_id matches, now check the password
                if (Hash::check($password, $user->password)) {
                    Auth::login($user, $request->filled('remember'));
                    $request->session()->regenerate();
                    return $this->authenticated($request, $user);
                }
                // If passcode matches but password doesn't, we can break early
                // as the identifier is unique.
                break;
            }
        }

        // Log failed login attempt
        \Log::warning('Failed login attempt', [
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        throw ValidationException::withMessages([
            'identifier' => __('auth.failed'),
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->isStudent() && !$user->class_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'identifier' => 'You are not assigned to a class. Please contact an administrator.',
            ]);
        }
        
        // Log successful login
        \Log::info('Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
            'timestamp' => now()
        ]);

        // Check for malformed intended URL containing template literals
        $intendedUrl = $request->session()->get('url.intended');
        if ($intendedUrl && (strpos($intendedUrl, '${') !== false || strpos($intendedUrl, '%7B') !== false)) {
            // Clear malformed intended URL that contains template literals
            $request->session()->forget('url.intended');
            \Log::warning('Cleared malformed intended URL during login', [
                'user_id' => $user->id,
                'malformed_url' => $intendedUrl,
                'ip' => $request->ip()
            ]);
        }

        // Eager load classModel here for immediate use in dashboard
        $user->load('classModel');
        return redirect()->intended(route('user.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $logoutReason = $request->input('logout_reason');
        
        if ($user) {
            // Update last activity for any active exam sessions when user logs out
            // Timer continues running - NO PAUSING EVER
            \App\Models\ExamSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'last_activity_at' => now()
                ]);
                
            \Log::info("User {$user->id} logged out, exam timer continues running", [
                'logout_reason' => $logoutReason
            ]);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Check if this is a security violation logout
        if ($logoutReason === 'security_violation_tab_switch') {
            return redirect()->route('security.critical-warning');
        }
        
        return redirect('/');
    }
}
