<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Login</title>
    
    <!-- Direct CSS -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo h1 {
            font-size: 2rem;
            font-weight: bold;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .logo p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
            background: #f9fafb;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }
        .form-input.error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .checkbox {
            margin-right: 0.5rem;
        }
        .checkbox-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.875rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>{{ config('app.name', 'Exam Portal') }}</h1>
            <p>Sign in to your account</p>
        </div>

        @if (session('status'))
            <div class="success-message">
                {{ session('status') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="error-message" style="background: #fef2f2; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                {{ session('error') }}
            </div>
        @endif
        
        @if (request()->routeIs('security.critical-warning') || request()->get('from') === 'security_violation' || request()->get('violation') === 'tab_switch')
            <div style="background: linear-gradient(135deg, #dc2626, #991b1b); color: white; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; border: 2px solid #ffffff; box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);">
                <div style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                    <span style="font-size: 1.5rem; margin-right: 0.5rem;">🚨</span>
                    <strong style="font-size: 1.1rem;">SECURITY VIOLATION DETECTED</strong>
                </div>
                <p style="margin-bottom: 0.75rem; line-height: 1.5;">
                    You were logged out due to tab switching during an exam. This is a serious violation of exam integrity rules.
                </p>
                <div style="background: rgba(255, 255, 255, 0.1); padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem;"><strong>What happened:</strong> Tab switching was detected</p>
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem;"><strong>Action taken:</strong> Immediate ban + forced logout</p>
                    <p style="font-size: 0.9rem;"><strong>Next steps:</strong> Login and visit your dashboard to request reactivation</p>
                </div>
                @if(request()->get('subject'))
                    <p style="font-size: 0.9rem; margin-bottom: 0.75rem;">
                        <strong>Banned from:</strong> {{ request()->get('subject') }}
                    </p>
                @endif
                <div style="text-align: center; margin-top: 1rem;">
                    <p style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 1rem;">
                        <strong>Note:</strong> Only the affected subject is banned. Other subjects remain accessible.
                    </p>
                    <p style="font-size: 0.85rem; margin-bottom: 1rem; color: #fbbf24;">
                        <strong>Quick Action:</strong> After logging in, you can immediately request reactivation:
                    </p>
                    <div style="background: rgba(255, 255, 255, 0.15); padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        <p style="font-size: 0.8rem; margin-bottom: 0.5rem;">1. Complete login below</p>
                        <p style="font-size: 0.8rem; margin-bottom: 0.5rem;">2. Click the blue "Request Reactivation" button on your dashboard</p>
                        <p style="font-size: 0.8rem;">3. Admin will be notified immediately for review</p>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="identifier" class="form-label">Email, Registration Number, or School ID</label>
                <input id="identifier" 
                       class="form-input @error('identifier') error @enderror"
                       type="text" 
                       name="identifier" 
                       value="{{ old('identifier') }}" 
                       required 
                       autofocus 
                       placeholder="Enter your email or ID" />
                @error('identifier')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" 
                       class="form-input @error('password') error @enderror"
                       type="password" 
                       name="password" 
                       required 
                       placeholder="Enter your password" />
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="checkbox-group">
                <input id="remember_me" type="checkbox" class="checkbox" name="remember">
                <label for="remember_me" class="checkbox-label">Remember me</label>
            </div>

            <button type="submit" class="login-btn">
                Sign In
            </button>
        </form>

        <div class="footer-text">
            © {{ date('Y') }} {{ config('app.name') }}. Secure Login Portal.
        </div>
    </div>
</body>
</html>
