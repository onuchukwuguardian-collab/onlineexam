@extends('layouts.student_app')

@section('title', 'Exam Result: ' . ($displaySubjectName ?? 'Subject'))

@section('header')
<div class="dashboard-header">
    <div class="header-content">
        <div class="welcome-section">
            <h1 class="dashboard-title">Exam Results</h1>
            <p class="dashboard-subtitle">{{ $displaySubjectName ?? 'Subject' }} - Performance Summary</p>
        </div>
        <div class="class-info">
            <div class="class-card">
                <i class="fas fa-chart-line class-icon"></i>
                <div class="class-details">
                    <span class="class-label">Status</span>
                    <span class="class-name">Completed</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
    <div class="results-container">
        <div class="results-content">
            @if (session('error'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="alert-content">
                        <strong>Error!</strong>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @elseif (session('auto_submitted'))
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    <div class="alert-content">
                        <strong>Time Expired!</strong>
                        <span>Your exam was automatically submitted when the time limit was reached.</span>
                    </div>
                </div>
            @elseif (session('exam_just_submitted'))
                <div class="alert alert-success submission-success">
                    <i class="fas fa-check-circle"></i>
                    <div class="alert-content">
                        <strong>Exam Submitted Successfully!</strong>
                        <span>Your answers have been recorded and your score has been calculated.</span>
                    </div>
                </div>
            @endif
            
            @if (isset($displayScore) && isset($displayTotal))
                <!-- Results Summary Card -->
                <div class="results-summary-card">
                    <div class="results-header">
                        <div class="results-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="results-title">
                            <h2>Exam Completed!</h2>
                            <p>{{ $displaySubjectName }}</p>
                        </div>
                    </div>
                    
                    @php
                        $percentage = $displayTotal > 0 ? round(($displayScore / $displayTotal) * 100, 1) : 0;
                        $isPass = $percentage >= 50;
                    @endphp
                    
                    <div class="score-display-large {{ $isPass ? 'score-pass' : 'score-fail' }}">
                        <div class="score-circle">
                            <div class="score-percentage">{{ $percentage }}%</div>
                            <div class="score-label">Score</div>
                        </div>
                        <div class="score-details">
                            <div class="score-item">
                                <span class="score-number">{{ $displayScore }}</span>
                                <span class="score-text">Correct</span>
                            </div>
                            <div class="score-item">
                                <span class="score-number">{{ $displayTotal - $displayScore }}</span>
                                <span class="score-text">Incorrect</span>
                            </div>
                            <div class="score-item">
                                <span class="score-number">{{ $displayTotal }}</span>
                                <span class="score-text">Total</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="performance-indicator {{ $isPass ? 'performance-pass' : 'performance-fail' }}">
                        <i class="fas {{ $isPass ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                        <span>{{ $isPass ? 'Congratulations! You passed!' : 'Keep studying and try again!' }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="results-actions">
                    @if(isset($userScore))
                        <a href="{{ route('user.exam.review', $userScore->id) }}" class="btn btn-primary btn-full">
                            <i class="fas fa-clipboard-check"></i> Review Your Answers
                        </a>
                    @endif
                    <a href="{{ route('user.dashboard') }}" class="btn btn-secondary btn-full">
                        <i class="fas fa-tachometer-alt"></i> View My Dashboard
                    </a>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="empty-title">Score Not Available</h3>
                    <p class="empty-description">Score data is not available at the moment. Please try again later.</p>
                    <a href="{{ route('user.dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Confetti effect for passing grades
    document.addEventListener('DOMContentLoaded', function() {
        @if(isset($displayScore) && isset($displayTotal))
            @php
                $percentage = $displayTotal > 0 ? round(($displayScore / $displayTotal) * 100, 1) : 0;
                $isPass = $percentage >= 50;
            @endphp
            
            @if($isPass && session('exam_just_submitted'))
                // Trigger confetti for passing grade
                setTimeout(function() {
                    createConfetti();
                }, 800);
            @endif
        @endif
    });

    function createConfetti() {
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        const confettiCount = 50;
        
        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }, i * 50);
        }
    }
</script>
@endpush

@push('styles')
<style>
    /* Results Page Styling */
    .results-container {
        padding: 1rem 0;
    }
    
    /* Success Alert Animation */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        font-weight: 500;
        animation: slideInDown 0.5s ease;
    }
    
    .alert-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #065f46;
    }
    
    .alert-success i {
        color: #10b981;
        font-size: 1.5rem;
    }
    
    .alert-warning {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.1));
        border: 1px solid rgba(251, 191, 36, 0.3);
        color: #92400e;
    }
    
    .alert-warning i {
        color: #f59e0b;
        font-size: 1.5rem;
    }
    
    .alert-error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #991b1b;
    }
    
    .alert-error i {
        color: #ef4444;
        font-size: 1.5rem;
    }
    
    .alert-content {
        flex: 1;
    }
    
    .alert-content strong {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 1.1rem;
    }
    
    .submission-success {
        animation: successPulse 0.6s ease;
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes successPulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
        }
        50% {
            transform: scale(1.02);
            box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }
    
    .results-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    .results-summary-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 1.5rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.6s ease 0.2s both;
    }
    
    .results-summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 1.5rem 1.5rem 0 0;
    }
    
    .results-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
        justify-content: center;
    }
    
    .results-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
    }
    
    .results-title h2 {
        font-size: 2rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .results-title p {
        font-size: 1.125rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .score-display-large {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border-radius: 1.25rem;
        padding: 2rem;
        margin-bottom: 2rem;
        gap: 2rem;
    }
    
    .score-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
    }
    
    .score-pass .score-circle {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
    
    .score-fail .score-circle {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    }
    
    .score-percentage {
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
    }
    
    .score-label {
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
    }
    
    .score-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        flex: 1;
    }
    
    .score-item {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .score-number {
        display: block;
        font-size: 2rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .score-text {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .performance-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1.5rem;
        border-radius: 1rem;
        font-size: 1.125rem;
        font-weight: 600;
    }
    
    .performance-pass {
        background: rgba(16, 185, 129, 0.1);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    
    .performance-fail {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .results-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        animation: fadeInUp 0.6s ease 0.4s both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Confetti Animation */
    .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        top: -10px;
        z-index: 1000;
        animation: confettiFall 3s linear forwards;
        pointer-events: none;
    }
    
    @keyframes confettiFall {
        0% {
            transform: translateY(-100vh) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
    
    .btn-full {
        width: 100%;
        justify-content: center;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .results-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .score-display-large {
            flex-direction: column;
            text-align: center;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
        }
        
        .score-percentage {
            font-size: 2rem;
        }
        
        .score-details {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .results-summary-card {
            padding: 1.5rem;
        }
    }
</style>
@endpush
