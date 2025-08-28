@extends('layouts.student_app')

@section('title', 'Student Dashboard')

@section('header')
<div class="dashboard-header">
    <div class="header-content">
        <div class="welcome-section">
            <div class="welcome-animation">
                <h1 class="dashboard-title animate-fade-in">Welcome back, {{ $user->name }}!</h1>
                <p class="dashboard-subtitle animate-slide-up">Ready to excel in your exams? Let's continue your learning journey.</p>
                <div class="motivation-quote animate-fade-in-delay">
                    <i class="fas fa-quote-left"></i>
                    <span>"Success is where preparation and opportunity meet."</span>
                </div>
            </div>
        </div>
        <div class="class-info">
            <div class="class-card animate-bounce-in">
                <div class="class-icon-wrapper">
                    <i class="fas fa-users class-icon"></i>
                    <div class="class-badge">{{ $availableSubjects->count() }} Subjects</div>
                </div>
                <div class="class-details">
                    <span class="class-label">Your Class</span>
                    <span class="class-name">{{ $user->classModel->name ?? 'Not Assigned' }}</span>
                    <span class="class-level">{{ $user->classModel->level_group ?? '' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
    <!-- Statistics Overview -->
    <div class="stats-section animate-slide-up">
        <div class="section-header-enhanced">
            <h2 class="section-title">Your Progress Overview</h2>
            <div class="progress-indicator">
                @php
                    $completionPercentage = $availableSubjects->count() > 0 ? round(($countTakenSubjects / $availableSubjects->count()) * 100) : 0;
                @endphp
                <div class="progress-circle" data-percentage="{{ $completionPercentage }}">
                    <span class="progress-text">{{ $completionPercentage }}%</span>
                </div>
                <span class="progress-label">Complete</span>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card stat-primary animate-scale-in" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                    <div class="stat-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number counter" data-target="{{ $availableSubjects->count() }}">0</div>
                    <div class="stat-label">Total Subjects</div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>Ready to start</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card stat-success animate-scale-in" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number counter" data-target="{{ $countTakenSubjects }}">0</div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-trend {{ $countTakenSubjects > 0 ? 'positive' : 'neutral' }}">
                        <i class="fas {{ $countTakenSubjects > 0 ? 'fa-trophy' : 'fa-hourglass-start' }}"></i>
                        <span>{{ $countTakenSubjects > 0 ? 'Great progress!' : 'Get started' }}</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card stat-warning animate-scale-in" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                    <div class="stat-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number counter" data-target="{{ $availableSubjects->count() - $countTakenSubjects }}">0</div>
                    <div class="stat-label">Remaining</div>
                    <div class="stat-trend">
                        <i class="fas fa-rocket"></i>
                        <span>Keep going!</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card stat-info animate-scale-in" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                    <div class="stat-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number">
                        @if($countTakenSubjects > 0)
                            <span class="counter" data-target="{{ number_format(($takenScoresArray->sum() / $countTakenSubjects), 1) }}">0</span> pts
                        @else
                            <span>0 pts</span>
                        @endif
                    </div>
                    <div class="stat-label">Average Points</div>
                    <div class="stat-trend {{ $countTakenSubjects > 0 ? 'positive' : 'neutral' }}">
                        <i class="fas {{ $countTakenSubjects > 0 ? 'fa-trending-up' : 'fa-compass' }}"></i>
                        <span>{{ $countTakenSubjects > 0 ? 'Trending up' : 'Start your journey' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
    <!-- Alert Messages -->
    @if (session('error'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="alert-content">
                <strong>Error!</strong>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif
    
    @if (session('message'))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div class="alert-content">
                <strong>Information</strong>
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif

    <!-- Ban Notifications & Reactivation Button -->
    @if($hasActiveBans)
        <div class="alert alert-warning ban-notification animate-bounce-in">
            <i class="fas fa-exclamation-triangle ban-icon"></i>
            <div class="alert-content">
                <strong>🚫 Account Restriction Notice</strong>
                <div class="ban-details">
                    <p>You have been banned from the following subjects due to security violations:</p>
                    <div class="banned-subjects-list">
                        @foreach($activeBans as $ban)
                            <div class="banned-subject-item">
                                <i class="fas fa-book"></i>
                                <span class="subject-name">{{ $ban->subject->name ?? 'Unknown Subject' }}</span>
                                <span class="ban-reason">{{ $ban->ban_reason ?? 'Formal Ban' }}</span>
                                <span class="ban-date">{{ $ban->created_at ? $ban->created_at->format('M d, Y') : 'N/A' }}</span>
                            </div>
                        @endforeach
                        @foreach($violationBasedBans as $vBan)
                            <div class="banned-subject-item">
                                <i class="fas fa-book"></i>
                                <span class="subject-name">{{ $vBan['subject_name'] }}</span>
                                <span class="ban-reason">{{ $vBan['reason'] }}</span>
                                <span class="ban-date">{{ \Carbon\Carbon::parse($vBan['banned_at'])->format('M d, Y') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="reactivation-info">
                        <p class="reactivation-text">
                            <i class="fas fa-info-circle"></i>
                            Don't worry! You can request reactivation by explaining what happened and promising to follow exam rules.
                        </p>
                        <a href="{{ route('user.student.reactivation.index') }}" class="btn btn-warning btn-enhanced reactivation-btn">
                            <i class="fas fa-paper-plane"></i>
                            <span>Request Reactivation</span>
                            <div class="btn-ripple"></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Subject Limit Status -->
    <div class="limit-status {{ $limitReached && $currentSubjectLimit > 0 ? 'limit-reached' : 'limit-ok' }}">
        <div class="limit-icon">
            <i class="fas {{ $limitReached && $currentSubjectLimit > 0 ? 'fa-exclamation-triangle' : 'fa-info-circle' }}"></i>
        </div>
        <div class="limit-content">
            <h3 class="limit-title">Subject Status</h3>
            <div class="limit-details">
                @if ($currentSubjectLimit > 0)
                    <span class="limit-item">Max: <strong>{{ $currentSubjectLimit }}</strong></span>
                    <span class="limit-item">Taken: <strong>{{ $countTakenSubjects }}</strong></span>
                    <span class="limit-item">Remaining: <strong>{{ max(0, $currentSubjectLimit - $countTakenSubjects) }}</strong></span>
                @else
                    <span class="limit-item">No limit set • Taken: <strong>{{ $countTakenSubjects }}</strong> subject(s)</span>
                @endif
            </div>
            @if ($limitReached && $currentSubjectLimit > 0)
                <div class="limit-warning">Subject limit has been reached</div>
            @endif
        </div>
    </div>

    <!-- Available Exams Section -->
    <div class="exams-section animate-fade-in">
        <div class="section-header">
            <div class="section-header-content">
                <h2 class="section-title">Available Exams</h2>
                <div class="section-subtitle">Choose a subject to begin your examination</div>
            </div>
            <div class="section-actions">
                <button type="button" id="myScoresModalTriggerDashboard" class="btn btn-secondary btn-enhanced">
                    <i class="fas fa-chart-bar"></i>
                    <span>View All Scores</span>
                    <div class="btn-ripple"></div>
                </button>
                <button type="button" class="btn btn-info btn-enhanced" onclick="toggleViewMode()">
                    <i class="fas fa-th-large" id="viewModeIcon"></i>
                    <span id="viewModeText">Grid View</span>
                    <div class="btn-ripple"></div>
                </button>
            </div>
        </div>
        
        @if($availableSubjects->isEmpty())
            <div class="empty-state animate-pulse">
                <div class="empty-icon">
                    <i class="fas fa-graduation-cap"></i>
                    <div class="empty-icon-glow"></div>
                </div>
                <h3 class="empty-title">No Exams Available</h3>
                <p class="empty-description">There are currently no exams available for your class. Please check back later or contact your instructor.</p>
                <div class="empty-action">
                    <button onclick="location.reload()" class="btn btn-primary">
                        <i class="fas fa-refresh"></i>
                        Refresh Page
                    </button>
                </div>
            </div>
        @else
            <div class="exam-filter-tabs">
                <button class="filter-tab active" data-filter="all">
                    <i class="fas fa-list"></i>
                    All ({{ $availableSubjects->count() }})
                </button>
                <button class="filter-tab" data-filter="completed">
                    <i class="fas fa-check-circle"></i>
                    Completed ({{ $countTakenSubjects }})
                </button>
                <button class="filter-tab" data-filter="pending">
                    <i class="fas fa-clock"></i>
                    Pending ({{ $availableSubjects->count() - $countTakenSubjects }})
                </button>
            </div>
            
            <div class="exams-container" id="examsContainer">
                <div class="exams-grid" id="examsGrid">
                    @foreach($availableSubjects as $index => $subject)
                        @php
                            $hasTaken = $takenScoresArray->has($subject->id); 
                            $score = $hasTaken ? $takenScoresArray->get($subject->id) : null;
                            $disableStartButton = $hasTaken || ($limitReached && $currentSubjectLimit > 0);
                            $resetCountForSubject = $resetCounts->get($subject->id, 0);
                            
                            // Check if there's an active exam session for this subject
                            $hasActiveSession = \App\Models\ExamSession::where('user_id', auth()->id())
                                ->where('subject_id', $subject->id)
                                ->where('is_active', true)
                                ->exists();
                        @endphp
                        <div class="exam-card {{ $hasTaken ? 'exam-completed' : 'exam-pending' }} animate-scale-in" 
                             data-aos="fade-up" 
                             data-aos-delay="{{ ($index % 4) * 100 }}"
                             data-status="{{ $hasTaken ? 'completed' : 'pending' }}">
                            
                            <div class="exam-card-inner">
                                <div class="exam-header">
                                    <div class="exam-title">
                                        <div class="exam-icon">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="exam-title-content">
                                            <h3>{{ $subject->name }}</h3>
                                            <span class="exam-code">{{ $subject->code ?? 'SUB-' . str_pad($subject->id, 3, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                    </div>
                                    <div class="exam-status">
                                        @if($hasTaken)
                                            <span class="status-badge status-completed">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Completed</span>
                                            </span>
                                        @elseif($hasActiveSession)
                                            <span class="status-badge status-active">
                                                <i class="fas fa-play-circle"></i>
                                                <span>In Progress</span>
                                            </span>
                                        @else
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i>
                                                <span>Pending</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="exam-meta">
                                    <div class="meta-grid">
                                        <div class="meta-item">
                                            <i class="fas fa-stopwatch"></i>
                                            <div class="meta-content">
                                                <span class="meta-label">Duration</span>
                                                <span class="meta-value">{{ $subject->exam_duration_minutes }} min</span>
                                            </div>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-question-circle"></i>
                                            <div class="meta-content">
                                                <span class="meta-label">Questions</span>
                                                <span class="meta-value">{{ $subject->questions_count ?? 'TBD' }}</span>
                                            </div>
                                        </div>
                                        @if($resetCountForSubject > 0 && !$hasTaken)
                                            <div class="meta-item reset-info">
                                                <i class="fas fa-history"></i>
                                                <div class="meta-content">
                                                    <span class="meta-label">Resets</span>
                                                    <span class="meta-value">{{ $resetCountForSubject }}x</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($hasTaken)
                                    <div class="score-display">
                                        <div class="score-content">
                                            <span class="score-label">Your Score</span>
                                            <span class="score-value">{{ $score }} <small>points</small></span>
                                        </div>
                                        <div class="score-visual">
                                            @php
                                                $percentage = $subject->questions_count ? round(($score / $subject->questions_count) * 100) : 0;
                                            @endphp
                                            <div class="score-circle" data-percentage="{{ $percentage }}">
                                                <span>{{ $percentage }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="exam-actions">
                                    @if($hasTaken)
                                        <a href="{{ route('user.score.display', $subject->id) }}" class="btn btn-primary btn-full btn-enhanced">
                                            <i class="fas fa-chart-line"></i>
                                            <span>View Results</span>
                                            <div class="btn-ripple"></div>
                                        </a>
                                    @elseif($hasActiveSession)
                                        <a href="{{ route('user.exam.start', $subject->id) }}" class="btn btn-warning btn-full btn-enhanced btn-pulse">
                                            <i class="fas fa-play-circle"></i>
                                            <span>Resume Exam</span>
                                            <div class="btn-ripple"></div>
                                        </a>
                                    @else
                                        @if($disableStartButton)
                                            <button class="btn btn-disabled btn-full" disabled>
                                                <i class="fas fa-lock"></i>
                                                <span>{{ $limitReached && $currentSubjectLimit > 0 ? 'Limit Reached' : 'Unavailable' }}</span>
                                            </button>
                                        @else
                                            <a href="{{ route('user.exam.start', $subject->id) }}" class="btn btn-success btn-full btn-enhanced btn-hover-lift">
                                                <i class="fas fa-play"></i>
                                                <span>Start Exam</span>
                                                <div class="btn-ripple"></div>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            
                            <div class="exam-card-glow"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>


@endsection

@push('styles')
<style>
    /* Enhanced Animation Styles */
    .animate-fade-in {
        opacity: 0;
        animation: fadeIn 1s ease-out forwards;
    }
    
    .animate-slide-up {
        opacity: 0;
        transform: translateY(30px);
        animation: slideUp 1s ease-out 0.3s forwards;
    }
    
    .animate-fade-in-delay {
        opacity: 0;
        animation: fadeIn 1s ease-out 0.6s forwards;
    }
    
    .animate-bounce-in {
        opacity: 0;
        transform: scale(0.8);
        animation: bounceIn 1s ease-out 0.4s forwards;
    }
    
    .animate-scale-in {
        opacity: 0;
        transform: scale(0.9);
        animation: scaleIn 0.6s ease-out forwards;
    }
    
    @keyframes fadeIn {
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes scaleIn {
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    /* Enhanced Dashboard Header */
    .welcome-animation {
        animation: fadeIn 1s ease-out;
    }
    
    .motivation-quote {
        background: rgba(255, 255, 255, 0.1);
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        margin-top: 1.5rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .motivation-quote i {
        opacity: 0.7;
        margin-right: 0.5rem;
    }
    
    /* Enhanced Class Card */
    .class-icon-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .class-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #10b981;
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 1rem;
        font-weight: 600;
    }
    
    .class-level {
        font-size: 0.875rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Enhanced Section Headers */
    .section-header-enhanced {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2rem;
    }
    
    .progress-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .progress-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: conic-gradient(#4f46e5 var(--percentage, 0%), #e2e8f0 0%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .progress-circle::before {
        content: '';
        position: absolute;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: white;
    }
    
    .progress-text {
        position: relative;
        z-index: 1;
        font-weight: 700;
        color: #4f46e5;
        font-size: 0.875rem;
    }
    
    .progress-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }
    
    /* Enhanced Stat Cards */
    .stat-glow {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 1rem;
        background: inherit;
        opacity: 0;
        transition: opacity 0.3s ease;
        filter: blur(4px);
    }
    
    .stat-card:hover .stat-glow {
        opacity: 0.3;
    }
    
    .stat-trend {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        font-size: 0.75rem;
        opacity: 0.8;
    }
    
    .stat-trend.positive {
        color: #10b981;
    }
    
    .stat-trend.neutral {
        color: #64748b;
    }
    
    /* Enhanced Section Actions */
    .section-header-content {
        flex: 1;
    }
    
    .section-subtitle {
        color: #64748b;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .section-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    /* Enhanced Buttons */
    .btn-enhanced {
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .btn-ripple {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
        transform: scale(0) translateX(-50%) translateY(-50%);
        transition: transform 0.5s ease;
        pointer-events: none;
    }
    
    .btn-enhanced:hover .btn-ripple {
        transform: scale(4) translateX(-50%) translateY(-50%);
    }
    
    .btn-pulse {
        animation: pulse 2s infinite;
    }
    
    .btn-hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
    
    /* Exam Filter Tabs */
    .exam-filter-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        background: rgba(255, 255, 255, 0.9);
        padding: 0.5rem;
        border-radius: 1rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .filter-tab {
        padding: 0.75rem 1.5rem;
        border: none;
        background: transparent;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #64748b;
        font-weight: 500;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        overflow: hidden;
    }
    
    .filter-tab.active {
        background: #4f46e5;
        color: white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        transform: translateY(-1px);
    }
    
    .filter-tab:hover:not(.active) {
        background: rgba(79, 70, 229, 0.1);
        color: #4f46e5;
        transform: translateY(-1px);
    }
    
    .filter-tab[data-filter="completed"].active {
        background: #10b981;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .filter-tab[data-filter="completed"]:hover:not(.active) {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    
    .filter-tab[data-filter="pending"].active {
        background: #f59e0b;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }
    
    .filter-tab[data-filter="pending"]:hover:not(.active) {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }
    
    /* Enhanced Exam Cards */
    .exam-card-inner {
        position: relative;
        z-index: 1;
    }
    
    .exam-card-glow {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 1.25rem;
        background: inherit;
        opacity: 0;
        transition: opacity 0.3s ease;
        filter: blur(8px);
        z-index: 0;
    }
    
    .exam-card:hover .exam-card-glow {
        opacity: 0.1;
    }
    
    .exam-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .exam-completed .exam-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .exam-title-content {
        flex: 1;
    }
    
    .exam-code {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }
    
    .meta-content {
        display: flex;
        flex-direction: column;
    }
    
    .meta-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .meta-value {
        font-size: 0.875rem;
        color: #1e293b;
        font-weight: 600;
    }
    
    .score-visual {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .score-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: conic-gradient(#10b981 var(--percentage, 0%), #e2e8f0 0%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .score-circle::before {
        content: '';
        position: absolute;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
    }
    
    .score-circle span {
        position: relative;
        z-index: 1;
        font-weight: 700;
        color: #10b981;
        font-size: 0.75rem;
    }
    
    .status-active {
        background: rgba(245, 158, 11, 0.1);
        color: #92400e;
        border: 1px solid rgba(245, 158, 11, 0.3);
        animation: pulse 2s infinite;
    }
    
    /* Enhanced Empty State */
    .empty-icon-glow {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 50%;
        background: inherit;
        opacity: 0.3;
        filter: blur(8px);
        animation: pulse 3s infinite;
    }
    
    .empty-action {
        margin-top: 2rem;
    }
    
    /* Filter Empty State */
    .filter-empty-state {
        grid-column: 1 / -1;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 300px;
        padding: 2rem;
    }
    
    .filter-empty-state .empty-state {
        text-align: center;
        max-width: 400px;
    }
    
    .filter-empty-state .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        color: #64748b;
        font-size: 2rem;
    }
    
    .filter-empty-state .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .filter-empty-state .empty-description {
        color: #64748b;
        font-size: 1rem;
        line-height: 1.6;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .section-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .section-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .exam-filter-tabs {
            flex-wrap: wrap;
        }
        
        .meta-grid {
            grid-template-columns: 1fr;
        }
        
        .progress-indicator {
            order: -1;
        }
    }
</style>
@endpush

@push('styles')
<style>
/* Ban Notification Styles */
.ban-notification {
    border-left: 4px solid #f59e0b !important;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);
    margin: 1rem 0;
    animation: banNotificationSlide 0.5s ease-out;
}

.ban-notification .ban-icon {
    color: #d97706;
    font-size: 1.5rem;
    margin-right: 1rem;
    animation: pulse 2s infinite;
}

.banned-subjects-list {
    margin: 1.5rem 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(220, 38, 38, 0.1);
    border: 2px solid rgba(220, 38, 38, 0.15);
}

.banned-subject-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(220, 38, 38, 0.1);
    margin-bottom: 0.75rem;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 8px;
    transition: all 0.3s ease;
    border-left: 4px solid #dc2626;
}

.banned-subject-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.banned-subject-item:hover {
    background: rgba(255, 255, 255, 0.8);
    transform: translateX(5px);
    box-shadow: 0 2px 10px rgba(220, 38, 38, 0.15);
}

.banned-subject-item i {
    color: #dc2626;
    font-size: 1.1rem;
    background: rgba(220, 38, 38, 0.1);
    padding: 8px;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.banned-subject-item .subject-name {
    font-weight: 700;
    color: #1f2937;
    flex: 1;
    font-size: 1.1rem;
}

.banned-subject-item .ban-reason {
    font-size: 0.9rem;
    color: #6b7280;
    flex: 1;
    font-weight: 500;
}

.banned-subject-item .ban-date {
    font-size: 0.85rem;
    color: #9ca3af;
    font-weight: 600;
}

.reactivation-info {
    margin-top: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
    border-radius: 12px;
    border: 2px solid rgba(59, 130, 246, 0.2);
    text-align: center;
}

.reactivation-text {
    color: #1e40af;
    font-size: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-weight: 600;
}

.reactivation-text i {
    color: #3b82f6;
    font-size: 1.2rem;
    animation: pulse 2s infinite;
}

.reactivation-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    color: white !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
    padding: 1rem 2rem !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
    border: none !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    transition: all 0.3s ease !important;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.reactivation-btn:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%) !important;
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4) !important;
    color: white !important;
}

.reactivation-btn:active {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
}

.reactivation-btn .btn-ripple {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%) scale(0);
    transition: transform 0.5s ease;
}

/* Animations */
@keyframes banNotificationSlide {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.animate-bounce-in {
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    50% {
        opacity: 1;
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .banned-subject-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .banned-subject-item .subject-name,
    .banned-subject-item .ban-reason {
        flex: none;
    }
    
    .reactivation-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize animations
        initializeAnimations();
        
        // Initialize counters
        initializeCounters();
        
        // Initialize progress circles
        initializeProgressCircles();
        
        // Initialize filter tabs
        initializeFilterTabs();
        
        // Initialize button ripple effects
        initializeRippleEffects();
    });
    
    function initializeAnimations() {
        // Stagger animations for exam cards
        const examCards = document.querySelectorAll('.exam-card');
        examCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    }
    
    function initializeCounters() {
        const counters = document.querySelectorAll('.counter');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const increment = target / (duration / 16); // 60fps
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.floor(current);
            }, 16);
        });
    }
    
    function initializeProgressCircles() {
        const circles = document.querySelectorAll('.progress-circle, .score-circle');
        
        circles.forEach(circle => {
            const percentage = circle.getAttribute('data-percentage');
            if (percentage) {
                circle.style.setProperty('--percentage', `${percentage}%`);
                
                // Animate the circle
                setTimeout(() => {
                    circle.style.background = `conic-gradient(#4f46e5 ${percentage}%, #e2e8f0 0%)`;
                    if (circle.classList.contains('score-circle')) {
                        circle.style.background = `conic-gradient(#10b981 ${percentage}%, #e2e8f0 0%)`;
                    }
                }, 500);
            }
        });
    }
    
    function initializeFilterTabs() {
        const filterTabs = document.querySelectorAll('.filter-tab');
        const examCards = document.querySelectorAll('.exam-card');
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                filterTabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                tab.classList.add('active');
                
                const filter = tab.getAttribute('data-filter');
                console.log('Filter selected:', filter);
                
                let visibleCount = 0;
                
                examCards.forEach((card, index) => {
                    const status = card.getAttribute('data-status');
                    console.log(`Card ${index + 1}: status=${status}, filter=${filter}`);
                    
                    let shouldShow = false;
                    
                    if (filter === 'all') {
                        shouldShow = true;
                    } else if (filter === 'completed' && status === 'completed') {
                        shouldShow = true;
                    } else if (filter === 'pending' && status === 'pending') {
                        shouldShow = true;
                    }
                    
                    if (shouldShow) {
                        card.style.display = 'block';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        
                        // Stagger the animation
                        setTimeout(() => {
                            card.style.transition = 'all 0.3s ease-out';
                            card.style.opacity = '1';
                            card.style.transform = 'scale(1)';
                        }, visibleCount * 50);
                        
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                console.log(`Showing ${visibleCount} cards for filter: ${filter}`);
                
                // Show empty state if no cards are visible
                showEmptyStateIfNeeded(filter, visibleCount);
            });
        });
    }
    
    function showEmptyStateIfNeeded(filter, visibleCount) {
        // Remove any existing empty state
        const existingEmptyState = document.querySelector('.filter-empty-state');
        if (existingEmptyState) {
            existingEmptyState.remove();
        }
        
        if (visibleCount === 0) {
            const examsGrid = document.getElementById('examsGrid');
            const emptyState = document.createElement('div');
            emptyState.className = 'filter-empty-state';
            
            let message = '';
            let icon = '';
            
            if (filter === 'completed') {
                icon = 'fas fa-graduation-cap';
                message = 'No completed exams yet. Start taking exams to see your results here!';
            } else if (filter === 'pending') {
                icon = 'fas fa-clock';
                message = 'All exams completed! Great job on finishing all available subjects.';
            }
            
            emptyState.innerHTML = `
                <div class="empty-state animate-fade-in">
                    <div class="empty-icon">
                        <i class="${icon}"></i>
                        <div class="empty-icon-glow"></div>
                    </div>
                    <h3 class="empty-title">${filter === 'completed' ? 'No Completed Exams' : 'No Pending Exams'}</h3>
                    <p class="empty-description">${message}</p>
                </div>
            `;
            
            examsGrid.appendChild(emptyState);
        }
    }
    
    function initializeRippleEffects() {
        const enhancedButtons = document.querySelectorAll('.btn-enhanced');
        
        enhancedButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = this.querySelector('.btn-ripple');
                if (ripple) {
                    ripple.style.transform = 'scale(0)';
                    setTimeout(() => {
                        ripple.style.transform = 'scale(4)';
                    }, 10);
                    
                    setTimeout(() => {
                        ripple.style.transform = 'scale(0)';
                    }, 500);
                }
            });
        });
    }
    
    function toggleViewMode() {
        const grid = document.getElementById('examsGrid');
        const icon = document.getElementById('viewModeIcon');
        const text = document.getElementById('viewModeText');
        
        // For now, just provide visual feedback
        if (icon.classList.contains('fa-th-large')) {
            icon.className = 'fas fa-list';
            text.textContent = 'List View';
        } else {
            icon.className = 'fas fa-th-large';
            text.textContent = 'Grid View';
        }
    }
    
    // Add loading states for exam start buttons
    document.querySelectorAll('a[href*="exam.start"]').forEach(link => {
        link.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const text = this.querySelector('span');
            
            if (icon && text) {
                icon.className = 'fas fa-spinner fa-spin';
                text.textContent = 'Loading...';
                this.style.pointerEvents = 'none';
            }
        });
    });
</script>
@endpush