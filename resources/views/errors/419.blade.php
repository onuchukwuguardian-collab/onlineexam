@extends('layouts.student_app')

@section('title', 'Session Expired')

@section('content')
<div class="error-container">
    <div class="error-content">
        <div class="error-icon">
            <i class="fas fa-clock"></i>
        </div>
        <h1 class="error-title">Session Expired</h1>
        <p class="error-message">
            Your session has expired for security reasons. This can happen if you've been inactive for too long.
        </p>
        
        <div class="error-actions">
            <a href="{{ route('login') }}" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login Again
            </a>
            <a href="{{ route('user.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
        </div>
        
        <div class="error-help">
            <h3>What happened?</h3>
            <ul>
                <li>Your session expired due to inactivity</li>
                <li>The security token became invalid</li>
                <li>You may have been logged out automatically</li>
            </ul>
            
            <h3>What to do next?</h3>
            <ul>
                <li>Click "Login Again" to sign back in</li>
                <li>Return to your dashboard to restart your exam</li>
                <li>Contact support if this keeps happening</li>
            </ul>
        </div>
    </div>
</div>

<style>
.error-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.error-content {
    max-width: 600px;
    text-align: center;
    background: white;
    border-radius: 16px;
    padding: 3rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
}

.error-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    font-size: 2.5rem;
    color: white;
}

.error-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: #1e293b;
    margin-bottom: 1rem;
}

.error-message {
    font-size: 1.125rem;
    color: #64748b;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 3rem;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.error-help {
    text-align: left;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
}

.error-help h3 {
    color: #1e293b;
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.error-help ul {
    margin-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.error-help li {
    margin-bottom: 0.5rem;
    color: #64748b;
}

@media (max-width: 768px) {
    .error-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .error-content {
        padding: 2rem;
    }
}
</style>
@endsection