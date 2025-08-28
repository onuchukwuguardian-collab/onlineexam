@extends('layouts.student_app')

@section('title', $subject->name . ' - Exam')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/exam-security.css') }}">
@endpush

@section('header')
<!-- Security Context (hidden) -->
<div id="examSecurityContext" 
     data-subject-id="{{ $subject->id }}" 
     data-user-id="{{ Auth::user()->id }}" 
     data-exam-session-id="{{ $examSession->id }}"
     style="display: none;"></div>

<!-- Exam Questions Data (hidden) -->
<script type="application/json" id="exam-questions-data">
    {!! json_encode($questionsList) !!}
</script>

<!-- Exam Session Data (hidden) -->
<div id="exam-session-data" 
     data-session-id="{{ $examSession->id }}" 
     data-remaining-time="{{ $examSession->remaining_time }}"
     style="display: none;"></div>

<!-- Exam Progress Data (hidden) -->
<script type="application/json" id="exam-progress-data">
    {!! json_encode(['answers' => $savedAnswers ?? [], 'currentQuestionIndex' => $currentQuestionIndex ?? 0, 'flaggedQuestions' => $flaggedQuestions ?? []]) !!}
</script>

<div class="exam-header-professional">
    <div class="header-content">
        <div class="exam-info-section">
            <div class="exam-title-wrapper">
                <div class="exam-icon-large">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="exam-title-content">
                    <h1 class="exam-title-main">{{ $subject->name }}</h1>
                    <p class="exam-subtitle">Online Examination Session</p>
                    <div class="exam-metadata">
                        <span class="exam-meta-item">
                            <i class="fas fa-questions"></i>
                            {{ count($questionsList) }} Questions
                        </span>
                        <span class="exam-meta-item">
                            <i class="fas fa-clock"></i>
                            {{ $examSession->duration_minutes }} Minutes
                        </span>
                        <span class="exam-meta-item">
                            <i class="fas fa-user"></i>
                            {{ Auth::user()->name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="exam-status-section">
            <div class="exam-timer-card">
                <div class="timer-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="timer-content">
                    <div class="timer-label">Time Remaining</div>
                    <div class="timer-display" id="timer">{{ gmdate('H:i:s', $examSession->remaining_time) }}</div>
                </div>
            </div>
            
            <div class="exam-progress-card">
                <div class="progress-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="progress-content">
                    <div class="progress-label">Progress</div>
                    <div class="progress-stats">
                        <span id="answeredNum">0</span> / <span id="totalQuestions">{{ count($questionsList) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Progress Bar -->
    <div class="exam-progress-bar-container">
        <div class="progress-bar-wrapper">
            <div class="progress-bar-track">
                <div class="progress-bar-fill" id="examProgress" style="width: 0%"></div>
            </div>
            <div class="progress-percentage">
                <span id="progressPercent">0</span>% Complete
            </div>
        </div>
    </div>
    
    <!-- NO MERCY Policy Warning -->
    <div class="no-mercy-warning">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="warning-text">
            <strong>🚨 NO MERCY POLICY:</strong> Timer NEVER stops! Logout, browser close, or navigation away = TIME LOST FOREVER!
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="exam-main-container">
    <!-- Question Navigation Bar -->
    <div class="question-nav-bar">
        <div class="nav-controls">
            <button type="button" class="btn btn-enhanced btn-secondary" id="showProgressBtn">
                <i class="fas fa-chart-pie"></i>
                <span>Overview</span>
            </button>
            <button type="button" class="btn btn-enhanced btn-outline-primary" id="toggleAutoAdvance">
                <i class="fas fa-forward" id="autoAdvanceIcon"></i>
                <span id="autoAdvanceText">Auto-Advance</span>
            </button>
            <!-- Diagnostic button for testing -->
            <button type="button" class="btn btn-enhanced btn-info" id="testBtn" onclick="testExamFunctionality()">
                <i class="fas fa-bug"></i>
                <span>Test</span>
            </button>
        </div>
        
        <div class="question-quick-nav" id="questionNav">
            <!-- Question navigation buttons will be generated here -->
        </div>
        
        <div class="exam-actions">
            <button type="button" class="btn btn-warning btn-enhanced" id="timeWarningBtn" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Time Warning</span>
            </button>
        </div>
    </div>

    <!-- Main Exam Content -->
    <div class="exam-content-grid">
        <!-- Question Display Panel -->
        <div class="question-panel">
            <div class="question-card">
                <div class="question-header">
                    <div class="question-number-badge">
                        <span class="question-label">Question</span>
                        <span class="question-number" id="questionNumber">1</span>
                        <span class="question-total">of {{ count($questionsList) }}</span>
                    </div>
                    <div class="question-status-badge">
                        <span class="status-indicator" id="questionStatus">Not Answered</span>
                    </div>
                </div>
                
                <div class="question-body">
                    <div class="question-content">
                        <div class="question-text" id="questionText">
                            <!-- Question text will be loaded here -->
                        </div>
                        <div class="question-image" id="questionImage" style="display: none;">
                            <img id="questionImg" src="" alt="Question Image" class="question-img">
                        </div>
                    </div>

                    <!-- Options Container -->
                    <div class="options-container" id="optionsContainer">
                        <!-- Options will be loaded here -->
                    </div>
                </div>

                <!-- Question Navigation -->
                <div class="question-footer">
                    <div class="nav-buttons">
                        <button type="button" class="btn btn-outline-secondary btn-enhanced" id="prevBtn" disabled>
                            <i class="fas fa-chevron-left"></i>
                            <span>Previous</span>
                        </button>
                        
                        <button type="button" class="btn btn-outline-warning btn-enhanced" id="flagBtn">
                            <i class="fas fa-flag"></i>
                            <span id="flagText">Flag for Review</span>
                        </button>
                        
                        <button type="button" class="btn btn-primary btn-enhanced" id="nextBtn">
                            <span>Next</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <button type="button" class="btn btn-success btn-enhanced" id="finishBtn" style="display: none;">
                            <i class="fas fa-check-circle"></i>
                            <span>Finish Exam</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Panel -->
        <div class="exam-sidebar" id="examSidebar">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <h3 class="sidebar-title">
                        <i class="fas fa-list-check"></i>
                        Exam Overview
                    </h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="closeSidebarBtn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="sidebar-content">
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-mini-card">
                            <div class="stat-icon stat-primary">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="sidebarTotal">{{ count($questionsList) }}</div>
                                <div class="stat-label">Total</div>
                            </div>
                        </div>
                        
                        <div class="stat-mini-card">
                            <div class="stat-icon stat-success">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="sidebarAnswered">0</div>
                                <div class="stat-label">Answered</div>
                            </div>
                        </div>
                        
                        <div class="stat-mini-card">
                            <div class="stat-icon stat-warning">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="sidebarFlagged">0</div>
                                <div class="stat-label">Flagged</div>
                            </div>
                        </div>
                        
                        <div class="stat-mini-card">
                            <div class="stat-icon stat-info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="sidebarRemaining">{{ count($questionsList) }}</div>
                                <div class="stat-label">Remaining</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question Grid -->
                    <div class="question-grid-container">
                        <h4 class="grid-title">Question Navigator</h4>
                        <div class="question-grid" id="questionGrid">
                            <!-- Question grid will be generated here -->
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="grid-legend">
                        <div class="legend-item">
                            <div class="legend-color legend-answered"></div>
                            <span>Answered</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-flagged"></div>
                            <span>Flagged</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-current"></div>
                            <span>Current</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-unanswered"></div>
                            <span>Unanswered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Professional Time Warning Modal -->
<div class="modal-overlay" id="timeWarningModal" style="display: none;">
    <div class="modal-container modal-warning">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon warning-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="modal-title">Time Running Out!</h3>
            </div>
            
            <div class="modal-body">
                <div class="warning-content">
                    <div class="time-display">
                        <span class="time-remaining" id="warningTimeLeft">10</span>
                        <span class="time-unit">minutes remaining</span>
                    </div>
                    <p class="warning-message">
                        The timer never stops. Submit your answers before time expires!
                    </p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> Closing this browser or navigating away will NOT pause the timer!
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-enhanced" id="continueExamBtn">
                    <i class="fas fa-arrow-right"></i>
                    <span>Continue Exam</span>
                </button>
                <button type="button" class="btn btn-success btn-enhanced" id="submitFromWarningBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Now</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Professional Submit Modal -->
<div class="modal-overlay" id="submitModal" style="display: none;">
    <div class="modal-container modal-success">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="modal-title">Submit Your Exam</h3>
            </div>
            
            <div class="modal-body">
                <div class="submit-summary">
                    <h4 class="summary-title">Exam Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-icon primary">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number" id="submitTotal">0</span>
                                <span class="summary-label">Total Questions</span>
                            </div>
                        </div>
                        
                        <div class="summary-item">
                            <div class="summary-icon success">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number" id="submitAnswered">0</span>
                                <span class="summary-label">Answered</span>
                            </div>
                        </div>
                        
                        <div class="summary-item">
                            <div class="summary-icon warning">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number" id="submitFlagged">0</span>
                                <span class="summary-label">Flagged</span>
                            </div>
                        </div>
                        
                        <div class="summary-item">
                            <div class="summary-icon danger">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="summary-content">
                                <span class="summary-number" id="submitUnanswered">0</span>
                                <span class="summary-label">Unanswered</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> Once submitted, you cannot change your answers.
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-enhanced" id="goBackBtn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Go Back</span>
                </button>
                <button type="button" class="btn btn-success btn-enhanced" id="confirmSubmitBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Exam</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form id="examForm" method="POST" action="{{ route('user.exam.submit') }}" style="display: none;">
    @csrf
    <input type="hidden" name="subject_id" value="{{ $subject->id }}">
    <input type="hidden" name="session_id" value="{{ $examSession->id }}">
    <input type="hidden" name="answers" id="answersInput">
    <input type="hidden" name="auto_submitted" id="autoSubmittedInput" value="0">
</form>

@endsection

@push('styles')
<style>
/* Professional Exam Page Styles */

/* Header Styles */
.exam-header-professional {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
    color: white;
    padding: 2rem 0 1rem 0;
    margin: 0;
    position: relative;
    overflow: hidden;
}

.exam-header-professional::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="exam-pattern" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23exam-pattern)"/></svg>');
    pointer-events: none;
}

.exam-title-wrapper {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.exam-icon-large {
    width: 80px;
    height: 80px;
    border-radius: 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.exam-title-main {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    background: linear-gradient(135deg, #ffffff, #e2e8f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.exam-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0 0 1rem 0;
}

.exam-metadata {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.exam-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    font-weight: 500;
}

.exam-status-section {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.exam-timer-card, .exam-progress-card {
    background: rgba(255, 255, 255, 0.15);
    padding: 1.5rem;
    border-radius: 1rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 200px;
}

.timer-icon, .progress-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.timer-label, .progress-label {
    font-size: 0.875rem;
    opacity: 0.8;
    margin-bottom: 0.25rem;
}

.timer-display {
    font-size: 1.5rem;
    font-weight: 700;
    font-family: 'JetBrains Mono', 'Courier New', monospace;
}

.progress-stats {
    font-size: 1.25rem;
    font-weight: 700;
}

.exam-progress-bar-container {
    margin-top: 1.5rem;
    padding: 0 2rem;
}

.progress-bar-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.progress-bar-track {
    flex: 1;
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-percentage {
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    min-width: 100px;
    text-align: right;
}

/* NO MERCY Policy Warning */
.no-mercy-warning {
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: white;
    padding: 1rem 2rem;
    margin-top: 1rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
    animation: pulseWarning 2s infinite;
}

.warning-icon {
    font-size: 1.5rem;
    color: #fbbf24;
}

.warning-text {
    font-weight: 600;
    font-size: 0.875rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

@keyframes pulseWarning {
    0%, 100% { box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3); }
    50% { box-shadow: 0 4px 30px rgba(220, 38, 38, 0.6); }
}

/* Main Container */
.exam-main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Question Navigation Bar */
.question-nav-bar {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 1rem;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.nav-controls, .exam-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.question-quick-nav {
    flex: 1;
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
    max-width: 600px;
    overflow-x: auto;
    padding: 0.5rem;
}

.question-nav-btn {
    width: 40px;
    height: 40px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.question-nav-btn:hover {
    border-color: #4f46e5;
    background: #f8fafc;
}

.question-nav-btn.current {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
    transform: scale(1.1);
}

.question-nav-btn.answered {
    background: #10b981;
    color: white;
    border-color: #10b981;
}

.question-nav-btn.flagged {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

.question-nav-btn.answered.flagged {
    background: linear-gradient(45deg, #10b981 50%, #f59e0b 50%);
}

/* Main Content Grid */
.exam-content-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    align-items: start;
}

/* Question Panel */
.question-panel {
    width: 100%;
}

.question-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 1.5rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.question-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.question-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

.question-number-badge {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.question-label {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.question-number {
    font-size: 2rem;
    font-weight: 800;
    color: #4f46e5;
}

.question-total {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
}

.question-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-indicator {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    background: #e2e8f0;
    color: #64748b;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.question-body {
    padding: 2rem;
}

.question-text {
    font-size: 1.125rem;
    line-height: 1.7;
    color: #1e293b;
    margin-bottom: 2rem;
}

.question-image {
    margin: 2rem 0;
    text-align: center;
}

.question-img {
    max-width: 100%;
    height: auto;
    border-radius: 1rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Options Container */
.options-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.option-item {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    overflow: hidden;
}

.option-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(79, 70, 229, 0.1), transparent);
    transition: left 0.5s;
}

.option-item:hover::before {
    left: 100%;
}

.option-item:hover {
    border-color: #4f46e5;
    background: #f1f5f9;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.15);
}

.option-item:focus-within {
    border-color: #4f46e5;
    background: #f1f5f9;
    outline: 2px solid rgba(79, 70, 229, 0.3);
    outline-offset: 2px;
}

.option-item.selected {
    border-color: #4f46e5;
    background: rgba(79, 70, 229, 0.1);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.2);
}

.option-item.answer-saved {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    transform: scale(1.02);
    transition: all 0.3s ease;
}

.option-radio {
    width: 20px;
    height: 20px;
    accent-color: #4f46e5;
}

.option-letter {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #4f46e5;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.option-item.selected .option-letter {
    background: #10b981;
}

.option-text {
    flex: 1;
    font-size: 1rem;
    line-height: 1.6;
    color: #1e293b;
}

/* Question Footer */
.question-footer {
    background: #f8fafc;
    padding: 1.5rem 2rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}

.nav-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

/* Enhanced Buttons */
.btn-enhanced {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border-radius: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid transparent;
}

.btn-enhanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-enhanced:hover::before {
    left: 100%;
}

.btn-enhanced:hover {
    transform: translateY(-2px);
}

/* Sidebar */
.exam-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.sidebar-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 1.5rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.sidebar-header {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    padding: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-content {
    padding: 1.5rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-mini-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.stat-mini-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}

.stat-icon.stat-primary { background: #4f46e5; }
.stat-icon.stat-success { background: #10b981; }
.stat-icon.stat-warning { background: #f59e0b; }
.stat-icon.stat-info { background: #3b82f6; }

.stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

/* Question Grid */
.question-grid-container {
    margin-bottom: 2rem;
}

.grid-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
}

.question-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(36px, 1fr));
    gap: 0.5rem;
}

.grid-question {
    width: 36px;
    height: 36px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.grid-question:hover {
    border-color: #4f46e5;
    background: #f8fafc;
}

.grid-question.current {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
    transform: scale(1.1);
}

.grid-question.answered {
    background: #10b981;
    color: white;
    border-color: #10b981;
}

.grid-question.flagged {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

/* Legend */
.grid-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.legend-answered { background: #10b981; }
.legend-flagged { background: #f59e0b; }
.legend-current { background: #4f46e5; }
.legend-unanswered { background: #e2e8f0; }

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease;
    visibility: hidden;
}

.modal-overlay.show {
    opacity: 1;
    visibility: visible;
}

.modal-container {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 90vw;
    max-height: 90vh;
    width: 500px;
    overflow: hidden;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.modal-overlay.show .modal-container {
    transform: scale(1);
}

.modal-header {
    padding: 2rem 2rem 1rem 2rem;
    text-align: center;
}

.modal-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1rem auto;
}

.warning-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.success-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.modal-body {
    padding: 0 2rem 1rem 2rem;
}

.warning-content {
    text-align: center;
}

.time-display {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 1rem 2rem;
    border-radius: 1rem;
    margin: 1rem 0;
    font-weight: 700;
}

.time-remaining {
    font-size: 2rem;
    font-family: 'JetBrains Mono', 'Courier New', monospace;
}

.time-unit {
    font-size: 1rem;
    opacity: 0.9;
}

.warning-message {
    color: #64748b;
    margin: 1rem 0;
}

.summary-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
}

.summary-icon {
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}

.summary-icon.primary { background: #4f46e5; }
.summary-icon.success { background: #10b981; }
.summary-icon.warning { background: #f59e0b; }
.summary-icon.danger { background: #ef4444; }

.summary-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.summary-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.modal-footer {
    padding: 1rem 2rem 2rem 2rem;
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.alert {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #92400e;
    padding: 1rem;
    border-radius: 0.75rem;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Timer States */
.timer-warning {
    animation: pulse-warning 2s infinite;
}

.timer-danger {
    animation: pulse-danger 1s infinite;
}

@keyframes pulse-warning {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes pulse-danger {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}

/* Animations */
.fade-in {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.auto-advance-indicator {
    position: fixed;
    top: 50%;
    right: 2rem;
    background: #4f46e5;
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    font-weight: 600;
    z-index: 999;
    transform: translateX(100px);
    opacity: 0;
    transition: all 0.3s ease;
}

.auto-advance-indicator.show {
    transform: translateX(0);
    opacity: 1;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .exam-content-grid {
        grid-template-columns: 1fr;
    }
    
    .exam-sidebar {
        position: static;
        order: -1;
    }
    
    .question-nav-bar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .exam-status-section {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 768px) {
    .exam-main-container {
        padding: 1rem;
    }
    
    .exam-title-wrapper {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .exam-metadata {
        justify-content: center;
        gap: 1rem;
    }
    
    .question-card {
        border-radius: 1rem;
    }
    
    .question-header {
        padding: 1rem 1.5rem;
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .question-body {
        padding: 1.5rem;
    }
    
    .nav-buttons {
        flex-direction: column;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-footer {
        flex-direction: column;
    }
}
</style>
@endpush

@section('scripts')
<script>
// Global variables
let questions = @json($questionsList);
let currentQuestionIndex = {{ $examSession->current_question_index ?? 0 }};
let answers = @json($examSession->answers ?? []);
let flaggedQuestions = new Set();
let autoAdvanceEnabled = localStorage.getItem('autoAdvanceEnabled') === 'true';
let timerInterval;
let remainingTime = {{ $examSession->remaining_time }};
let sessionId = {{ $examSession->id }};
let timeWarningShown = false;
let finalWarningShown = false;
let examPaused = false; // WARNING: Timer NEVER pauses - this is just for compatibility

// Initialize exam
$(document).ready(function() {
    console.log('Starting exam initialization...');
    try {
        initializeExam();
        startTimer();
        setupEventListeners();
        loadCurrentQuestion();
        updateUI();
        console.log('Exam initialization completed successfully');
    } catch (error) {
        console.error('Error during exam initialization:', error);
        alert('There was an error loading the exam. Please refresh the page.');
    }
});

function initializeExam() {
    console.log('Initializing exam with questions:', questions);
    
    // Check if questions data is valid
    if (!questions || !Array.isArray(questions) || questions.length === 0) {
        console.error('Questions data is invalid or empty:', questions);
        alert('Error: No questions found. Please contact your administrator.');
        return;
    }
    
    // Check if current question index is valid
    if (currentQuestionIndex >= questions.length) {
        console.warn('Invalid current question index, resetting to 0');
        currentQuestionIndex = 0;
    }
    
    try {
        // Generate question navigation
        generateQuestionNavigation();
        generateQuestionGrid();
        
        // Set auto-advance state
        updateAutoAdvanceButton();
        
        // Load saved progress
        if (Object.keys(answers).length > 0) {
            updateProgressFromAnswers();
        }
        
        console.log('Exam initialized with', questions.length, 'questions');
    } catch (error) {
        console.error('Error during exam initialization:', error);
        alert('Error initializing exam: ' + error.message);
    }
}

function generateQuestionNavigation() {
    const nav = $('#questionNav');
    nav.empty();
    
    questions.forEach((question, index) => {
        const btn = $(`
            <button class="question-nav-btn" data-question="${index}">
                ${index + 1}
            </button>
        `);
        nav.append(btn);
    });
}

function generateQuestionGrid() {
    const grid = $('#questionGrid');
    grid.empty();
    
    questions.forEach((question, index) => {
        const btn = $(`
            <button class="grid-question" data-question="${index}">
                ${index + 1}
            </button>
        `);
        grid.append(btn);
    });
}

function loadCurrentQuestion() {
    if (currentQuestionIndex >= questions.length) {
        currentQuestionIndex = questions.length - 1;
    }
    
    const question = questions[currentQuestionIndex];
    
    // Update question content
    $('#questionNumber').text(currentQuestionIndex + 1);
    $('#currentQuestionNum').text(currentQuestionIndex + 1);
    $('#questionText').html(question.text);
    
    // Handle question image
    if (question.image_path) {
        $('#questionImg').attr('src', question.image_path);
        $('#questionImage').show();
    } else {
        $('#questionImage').hide();
    }
    
    // Load options
    loadQuestionOptions(question);
    
    // Update navigation buttons
    updateNavigationButtons();
    
    // Update question status
    updateQuestionStatus();
    
    // Add smooth question transition animation
    $('.question-card').addClass('fade-in');
    setTimeout(() => $('.question-card').removeClass('fade-in'), 300);
    
    // Scroll to top of question on mobile
    if (window.innerWidth <= 768) {
        $('html, body').animate({
            scrollTop: $('.question-card').offset().top - 20
        }, 300);
    }
}

function loadQuestionOptions(question) {
    const container = $('#optionsContainer');
    container.empty();
    
    console.log('Loading options for question:', question);
    
    if (!question.options || !Array.isArray(question.options)) {
        console.error('Question options not found or invalid:', question);
        container.html('<div class="alert alert-danger">Error: Question options not loaded properly.</div>');
        return;
    }
    
    question.options.forEach(option => {
        const isSelected = answers[question.id] === option.letter;
        const optionHtml = $(`
            <div class="option-item ${isSelected ? 'selected' : ''}" data-option="${option.letter}">
                <input type="radio" 
                       class="option-radio" 
                       name="question_${question.id}" 
                       value="${option.letter}" 
                       ${isSelected ? 'checked' : ''}>
                <span class="option-letter">${option.letter}.</span>
                <span class="option-text">${option.text}</span>
            </div>
        `);
        
        container.append(optionHtml);
    });
    
    console.log('Loaded', question.options.length, 'options for question', question.id);
}

function updateNavigationButtons() {
    // Previous button
    $('#prevBtn').prop('disabled', currentQuestionIndex === 0);
    
    // Next/Finish button
    if (currentQuestionIndex === questions.length - 1) {
        $('#nextBtn').hide();
        $('#finishBtn').show();
    } else {
        $('#nextBtn').show();
        $('#finishBtn').hide();
    }
    
    // Update navigation indicators
    $('.question-nav-btn, .grid-question').removeClass('current');
    $(`.question-nav-btn[data-question="${currentQuestionIndex}"], .grid-question[data-question="${currentQuestionIndex}"]`).addClass('current');
}

function updateQuestionStatus() {
    const question = questions[currentQuestionIndex];
    const isAnswered = answers.hasOwnProperty(question.id);
    const isFlagged = flaggedQuestions.has(question.id);
    
    let statusText = 'Not Answered';
    let statusClass = 'badge-light';
    
    if (isAnswered && isFlagged) {
        statusText = 'Answered & Flagged';
        statusClass = 'badge-warning';
    } else if (isAnswered) {
        statusText = 'Answered';
        statusClass = 'badge-success';
    } else if (isFlagged) {
        statusText = 'Flagged for Review';
        statusClass = 'badge-warning';
    }
    
    $('#questionStatus').removeClass().addClass(`badge ${statusClass}`).text(statusText);
    
    // Update flag button
    if (isFlagged) {
        $('#flagBtn').removeClass('btn-outline-primary').addClass('btn-warning');
        $('#flagText').text('Remove Flag');
    } else {
        $('#flagBtn').removeClass('btn-warning').addClass('btn-outline-primary');
        $('#flagText').text('Flag for Review');
    }
}

function updateUI() {
    updateProgress();
    updateQuestionNavigation();
    updateSidebarStats();
}

function updateProgress() {
    const totalQuestions = questions.length;
    const answeredCount = Object.keys(answers).length;
    const progressPercent = Math.round((answeredCount / totalQuestions) * 100);
    
    $('#examProgress').css('width', progressPercent + '%').attr('aria-valuenow', progressPercent);
    $('#progressPercent').text(progressPercent);
    $('#answeredNum').text(answeredCount);
    $('#answeredCount .badge').removeClass('badge-danger badge-warning badge-success');
    
    if (progressPercent === 100) {
        $('#answeredCount .badge').addClass('badge-success');
    } else if (progressPercent >= 50) {
        $('#answeredCount .badge').addClass('badge-warning');
    } else {
        $('#answeredCount .badge').addClass('badge-danger');
    }
}

function updateQuestionNavigation() {
    $('.question-nav-btn, .grid-question').each(function() {
        const questionIndex = $(this).data('question');
        const question = questions[questionIndex];
        const isAnswered = answers.hasOwnProperty(question.id);
        const isFlagged = flaggedQuestions.has(question.id);
        
        $(this).removeClass('answered flagged');
        
        if (isAnswered) {
            $(this).addClass('answered');
        }
        if (isFlagged) {
            $(this).addClass('flagged');
        }
    });
}

function updateSidebarStats() {
    const totalQuestions = questions.length;
    const answeredCount = Object.keys(answers).length;
    const flaggedCount = flaggedQuestions.size;
    const remainingCount = totalQuestions - answeredCount;
    
    $('#sidebarTotal').text(totalQuestions);
    $('#sidebarAnswered').text(answeredCount);
    $('#sidebarFlagged').text(flaggedCount);
    $('#sidebarRemaining').text(remainingCount);
}

function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    try {
        // Option selection
        $(document).on('click', '.option-item', function() {
            console.log('Option clicked:', $(this).data('option'));
            const optionLetter = $(this).data('option');
            const question = questions[currentQuestionIndex];
            
            if (!question) {
                console.error('No current question found');
                return;
            }
            
            // Update UI
            $('.option-item').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('.option-radio').prop('checked', true);
            
            // Save answer
            answers[question.id] = optionLetter;
            
            // Add visual feedback
            $(this).addClass('answer-saved');
            setTimeout(() => $(this).removeClass('answer-saved'), 1000);
            
            // Update UI
            updateUI();
            
            // Auto-advance if enabled (timer never pauses)
            if (autoAdvanceEnabled) {
                showAutoAdvanceIndicator();
                setTimeout(() => {
                    if (currentQuestionIndex < questions.length - 1) {
                        nextQuestion();
                    }
                }, 600);
            }
            
            // Save progress
            saveProgress();
        });
        
        // Navigation buttons
        $('#prevBtn').off('click').on('click', function() {
            console.log('Previous button clicked');
            previousQuestion();
        });
        $('#nextBtn').off('click').on('click', function() {
            console.log('Next button clicked');
            nextQuestion();
        });
        $('#finishBtn').off('click').on('click', function() {
            console.log('Finish button clicked');
            showSubmitModal();
        });
        
        // Question navigation
        $(document).off('click', '.question-nav-btn, .grid-question').on('click', '.question-nav-btn, .grid-question', function() {
            console.log('Question navigation clicked:', $(this).data('question'));
            const questionIndex = $(this).data('question');
            goToQuestion(questionIndex);
        });
        
        // Flag button
        $('#flagBtn').off('click').on('click', function() {
            console.log('Flag button clicked');
            toggleFlag();
        });
        
        // Auto-advance toggle
        $('#toggleAutoAdvance').off('click').on('click', function() {
            console.log('Auto-advance toggle clicked');
            toggleAutoAdvance();
        });
        
        // Progress button
        $('#showProgressBtn').off('click').on('click', function() {
            console.log('Progress button clicked');
            toggleSidebar();
        });
        $('#closeSidebarBtn').off('click').on('click', function() {
            console.log('Close sidebar clicked');
            closeSidebar();
        });
        
        // Submit confirmation
        $('#confirmSubmitBtn').off('click').on('click', function() {
            console.log('Confirm submit clicked');
            submitExam();
        });
        $('#submitFromWarningBtn').off('click').on('click', function() {
            console.log('Submit from warning clicked');
            showSubmitModal();
        });
        $('#goBackBtn').off('click').on('click', function() {
            console.log('Go back clicked');
            $('#submitModal').removeClass('show').hide();
        });
        $('#continueExamBtn').off('click').on('click', function() {
            console.log('Continue exam clicked');
            $('#timeWarningModal').removeClass('show').hide();
        });
        
        // Modal overlay click to close
        $('.modal-overlay').off('click').on('click', function(e) {
            if (e.target === this) {
                console.log('Modal overlay clicked to close');
                $(this).removeClass('show').hide();
            }
        });
        
        console.log('Event listeners setup completed successfully');
        
    } catch (error) {
        console.error('Error setting up event listeners:', error);
        alert('Error setting up exam interface: ' + error.message);
    }
    
    // Prevent accidental page leave - NO MERCY!
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = '🚨 NO MERCY POLICY: Your exam timer will continue running even if you leave this page! Time lost is GONE FOREVER!';
        return '🚨 NO MERCY POLICY: Your exam timer will continue running even if you leave this page! Time lost is GONE FOREVER!';
    });
    
    // Tab switching is now handled by exam-security.js
    // This code is maintained for backward compatibility
    // but actual violation reporting happens in the ExamSecuritySystem class
}

function nextQuestion() {
    if (currentQuestionIndex < questions.length - 1) {
        currentQuestionIndex++;
        loadCurrentQuestion();
        updateUI();
    }
}

function previousQuestion() {
    if (currentQuestionIndex > 0) {
        currentQuestionIndex--;
        loadCurrentQuestion();
        updateUI();
    }
}

function goToQuestion(index) {
    if (index >= 0 && index < questions.length) {
        currentQuestionIndex = index;
        loadCurrentQuestion();
        updateUI();
        closeSidebar();
    }
}

function toggleFlag() {
    const question = questions[currentQuestionIndex];
    
    if (flaggedQuestions.has(question.id)) {
        flaggedQuestions.delete(question.id);
    } else {
        flaggedQuestions.add(question.id);
    }
    
    updateQuestionStatus();
    updateUI();
    saveProgress();
}

function toggleAutoAdvance() {
    autoAdvanceEnabled = !autoAdvanceEnabled;
    localStorage.setItem('autoAdvanceEnabled', autoAdvanceEnabled);
    updateAutoAdvanceButton();
}

function updateAutoAdvanceButton() {
    const btn = $('#toggleAutoAdvance');
    const icon = $('#autoAdvanceIcon');
    const text = $('#autoAdvanceText');
    
    if (autoAdvanceEnabled) {
        btn.removeClass('btn-outline-primary').addClass('btn-primary');
        icon.removeClass('fa-forward').addClass('fa-forward');
        text.text('Auto-Advance ON');
    } else {
        btn.removeClass('btn-primary').addClass('btn-outline-primary');
        icon.removeClass('fa-forward').addClass('fa-forward');
        text.text('Enable Auto-Advance');
    }
}

function showAutoAdvanceIndicator() {
    const indicator = $('<div class="auto-advance-indicator">Auto-advancing...</div>');
    $('body').append(indicator);
    
    setTimeout(() => indicator.addClass('show'), 100);
    setTimeout(() => {
        indicator.removeClass('show');
        setTimeout(() => indicator.remove(), 300);
    }, 1500);
}

function toggleSidebar() {
    $('#examSidebar').toggleClass('open');
}

function closeSidebar() {
    $('#examSidebar').removeClass('open');
}

function showTimeWarning() {
    if (!timeWarningShown && remainingTime <= 600) { // 10 minutes
        timeWarningShown = true;
        $('#warningTimeLeft').text(Math.ceil(remainingTime / 60));
        $('#timeWarningModal').addClass('show').show();
        $('#timeWarningBtn').show();
    }
    
    if (!finalWarningShown && remainingTime <= 120) { // 2 minutes
        finalWarningShown = true;
        alert('⚠️ FINAL WARNING: Only 2 minutes remaining! Submit your exam NOW!');
    }
}

function startTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    timerInterval = setInterval(() => {
        // NO MERCY POLICY: Timer NEVER pauses for any reason!
        // Students who logout, navigate away, or close browser LOSE TIME!
        if (remainingTime > 0) {
            remainingTime--;
            updateTimerDisplay();
            
            // Save progress every 30 seconds
            if (remainingTime % 30 === 0) {
                saveProgress();
            }
            
            // Show warnings
            if (remainingTime <= 600) { // 10 minutes
                showTimeWarning();
            }
            
            // Auto-submit when time expires
            if (remainingTime <= 0) {
                autoSubmitExam();
            }
        }
    }, 1000);
}

function updateTimerDisplay() {
    const timerElement = $('#timer');
    const formattedTime = formatTime(remainingTime);
    
    timerElement.text(formattedTime);
    
    // Add warning classes
    timerElement.removeClass('timer-warning timer-danger');
    if (remainingTime <= 300) { // 5 minutes
        timerElement.addClass('timer-danger');
    } else if (remainingTime <= 600) { // 10 minutes
        timerElement.addClass('timer-warning');
    }
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    } else {
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
}

function showSubmitModal() {
    const totalQuestions = questions.length;
    const answeredCount = Object.keys(answers).length;
    const flaggedCount = flaggedQuestions.size;
    const unansweredCount = totalQuestions - answeredCount;
    
    $('#submitTotal').text(totalQuestions);
    $('#submitAnswered').text(answeredCount);
    $('#submitFlagged').text(flaggedCount);
    $('#submitUnanswered').text(unansweredCount);
    
    $('#submitModal').addClass('show').show();
}

function submitExam() {
    $('#submitModal').removeClass('show').hide();
    
    // Prepare form data
    $('#answersInput').val(JSON.stringify(answers));
    $('#autoSubmittedInput').val('0');
    
    // Clear timer
    clearInterval(timerInterval);
    
    // Submit form
    $('#examForm').submit();
}

function autoSubmitExam() {
    clearInterval(timerInterval);
    
    // Prepare form data
    $('#answersInput').val(JSON.stringify(answers));
    $('#autoSubmittedInput').val('1');
    
    // Show auto-submit message
    alert('Time expired! Your exam is being submitted automatically.');
    
    // Submit form
    $('#examForm').submit();
}

function saveProgress() {
    const progressData = {
        session_id: sessionId,
        answers: answers,
        current_question_index: currentQuestionIndex,
        flagged_questions: Array.from(flaggedQuestions)
    };
    
    $.ajax({
        url: '{{ route("user.exam.save.progress") }}',
        method: 'POST',
        data: progressData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.expired) {
                alert(response.message);
                window.location.href = '/security/critical-warning';
            }
        },
        error: function(xhr) {
            console.error('Failed to save progress:', xhr);
        }
    });
}

function updateProgressFromAnswers() {
    // Update question navigation based on saved answers
    updateQuestionNavigation();
    updateProgress();
}

// Add missing pauseExam function (Note: Exam can NEVER be paused per NO MERCY policy)
function pauseExam() {
    // 🚨 NO MERCY POLICY 🚨
    // The timer NEVER stops, even if students:
    // - Logout
    // - Close browser
    // - Navigate away
    // - Lose internet connection
    // - Computer crashes
    // TIME LOST IS GONE FOREVER!
    alert('🚨 NO MERCY POLICY: Exams CANNOT be paused! Timer keeps running even if you logout or close browser!');
    console.warn('🚨 ATTEMPTED TO PAUSE EXAM - DENIED! NO MERCY POLICY IN EFFECT!');
    return false;
}

// Add missing syncTimeWithServer function
function syncTimeWithServer() {
    $.ajax({
        url: '{{ route("user.exam.check.timer") }}',
        method: 'POST',
        data: {
            session_id: sessionId
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.expired) {
                alert(response.message);
                window.location.href = '/security/critical-warning';
            } else {
                remainingTime = response.remaining_time;
                updateTimerDisplay();
            }
        },
        error: function(xhr) {
            console.error('Failed to sync time:', xhr);
        }
    });
}

// Keyboard shortcuts
$(document).keydown(function(e) {
    // NO MERCY: Shortcuts work even if user tries to "pause" exam
    switch(e.key) {
        case 'ArrowLeft':
            e.preventDefault();
            if (currentQuestionIndex > 0) previousQuestion();
            break;
        case 'ArrowRight':
            e.preventDefault();
            if (currentQuestionIndex < questions.length - 1) nextQuestion();
            break;
        case 'f':
        case 'F':
            if (e.ctrlKey) {
                e.preventDefault();
                toggleFlag();
            }
            break;
        case '1':
        case '2':
        case '3':
        case '4':
        case '5':
            if (!e.ctrlKey && !e.altKey) {
                const optionIndex = parseInt(e.key) - 1;
                const question = questions[currentQuestionIndex];
                if (question.options[optionIndex]) {
                    $(`.option-item[data-option="${question.options[optionIndex].letter}"]`).click();
                }
            }
            break;
    }
});

// Auto-save on page unload
window.addEventListener('beforeunload', function() {
    saveProgress();
});

// Diagnostic function to test exam functionality
function testExamFunctionality() {
    console.log('=== EXAM DIAGNOSTIC TEST ===');
    console.log('Questions loaded:', questions ? questions.length : 'NO QUESTIONS');
    console.log('Current question index:', currentQuestionIndex);
    console.log('Current answers:', answers);
    console.log('Session ID:', sessionId);
    console.log('Remaining time:', remainingTime);
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('Option containers found:', $('.option-item').length);
    console.log('Navigation buttons found:', $('.question-nav-btn').length);
    
    if (questions && questions.length > 0) {
        console.log('First question:', questions[0]);
        if (questions[currentQuestionIndex]) {
            console.log('Current question:', questions[currentQuestionIndex]);
        } else {
            console.error('Current question index is invalid!');
        }
    } else {
        console.error('No questions loaded!');
    }
    
    // Test option clicking
    const firstOption = $('.option-item').first();
    if (firstOption.length > 0) {
        console.log('Testing option click...');
        firstOption.trigger('click');
    } else {
        console.error('No option items found!');
    }
    
    alert('Diagnostic complete - check console for details');
}
<!-- Load our custom security and navigation scripts -->
<script src="{{ asset('assets/js/exam-security.js') }}"></script>
<script src="{{ asset('assets/js/exam-navigation.js') }}"></script>
@endsection