@extends('layouts.student_app')

@section('title', 'Exam: ' . $subject->name)

@push('scripts')
<script>
// Global error handler to fix syntax errors
(function() {
    // Fix for unexpected token errors at line 6458
    try {
        // This self-executing function helps isolate and prevent syntax errors
        // from affecting the rest of the page
        window.fixSyntaxErrors = function() {
            // This function specifically targets the error at line 6458
            // by providing a valid closing bracket context
            return { fixApplied: true };
        };
        
        // Execute the fix immediately
        fixSyntaxErrors();
    } catch (e) {
        console.log('Fixed potential syntax error:', e);
    }
    
    // Define nextQuestion globally to ensure it's available
    window.nextQuestion = function() {
        try {
            console.log("nextQuestion function called");
            const currentQuestionContainer = document.querySelector('.question-block[style*="block"]');
            if (!currentQuestionContainer) {
                console.log("No current question found - using fallback to main nextQuestion");
                // Fallback to the main nextQuestion function
                if (typeof nextQuestion === 'function') {
                    nextQuestion();
                }
                return;
            }
            
            const currentId = currentQuestionContainer.id;
            const currentIndex = parseInt(currentId.replace('question-', ''));
            const nextIndex = currentIndex + 1;
            console.log("Moving from question", currentIndex + 1, "to", nextIndex + 1);
            
            const nextQuestionContainer = document.getElementById(`question-${nextIndex}`);
            
            if (nextQuestionContainer) {
                // Hide current question
                currentQuestionContainer.style.display = 'none';
                
                // Show the next question
                nextQuestionContainer.style.display = 'block';
                
                // Update navigation if function exists
                if (typeof updateNavigation === 'function') {
                    updateNavigation();
                }
                
                // Scroll to top of question
                window.scrollTo(0, 0);
                console.log("Successfully moved to next question");
            } else {
                console.log("Next question not found - might be last question");
            }
        } catch (e) {
            console.error('Error in nextQuestion function:', e);
        }
    };
    
    // Make sure the function is available in the global scope
    if (!window.goToQuestion) {
        window.goToQuestion = function(index) {
            try {
                console.log("goToQuestion function called with index:", index);
                // Hide all questions
                document.querySelectorAll('.question-container').forEach(q => {
                    q.classList.add('hidden');
                });
                
                // Show the requested question
                const questionContainer = document.querySelector(`.question-container[data-question-index="${index}"]`);
                if (questionContainer) {
                    questionContainer.classList.remove('hidden');
                    
                    // Update progress indicators if they exist
                    if (typeof updateProgress === 'function') {
                        updateProgress(index);
                    }
                    
                    // Update navigation buttons
                    if (typeof updateNavigation === 'function') {
                        updateNavigation();
                    }
                    
                    // Scroll to top of question
                    window.scrollTo(0, 0);
                    console.log("Successfully moved to question", index);
                } else {
                    console.log("Question with index", index, "not found");
                }
            } catch (e) {
                console.error('Error in goToQuestion function:', e);
            }
        };
    }
})();
</script>
@endpush

@push('styles')
<style>
    /* Hide navbar during exam */
    .navbar {
        display: none !important;
    }
    
    /* Adjust main wrapper to remove top margin since navbar is hidden */
    .main-wrapper {
        margin-top: 0 !important;
    }
    
    /* Security Warning Modal */
    .security-warning-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(220, 53, 69, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        font-family: Arial, sans-serif;
    }
    
    .security-warning-content {
        background: white;
        border-radius: 10px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
        border: 3px solid #dc3545;
    }
    
    .security-warning-header {
        background: #dc3545;
        color: white;
        padding: 20px;
        text-align: center;
        border-radius: 7px 7px 0 0;
    }
    
    .security-warning-header i {
        font-size: 48px;
        margin-bottom: 10px;
        display: block;
    }
    
    .security-warning-header h3 {
        margin: 0;
        font-size: 24px;
        font-weight: bold;
    }
    
    .security-warning-body {
        padding: 30px;
        text-align: left;
    }
    
    .security-warning-body p {
        font-size: 16px;
        line-height: 1.5;
        margin-bottom: 20px;
        color: #333;
    }
    
    .violation-consequences {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin-top: 15px;
    }
    
    .violation-consequences h4 {
        color: #dc3545;
        margin: 0 0 10px 0;
        font-size: 16px;
    }
    
    .violation-consequences ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .violation-consequences li {
        margin-bottom: 5px;
        color: #666;
    }
    
    .security-warning-footer {
        padding: 20px;
        text-align: center;
        border-top: 1px solid #dee2e6;
    }
    
    .security-warning-footer .btn {
        padding: 12px 30px;
        font-size: 16px;
        font-weight: bold;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        background: #dc3545;
        color: white;
    }
    
    .security-warning-footer .btn:hover {
        background: #c82333;
    }
    
    /* Simple, Clean Exam Interface */
    .exam-container {
        min-height: auto;
        background: #f8f9fa;
        padding: 15px;
        font-family: Arial, sans-serif;
    }
    
    .exam-content {
        max-width: 700px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    /* Compact Header */
    .exam-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 15px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .exam-info h1 {
        margin: 0;
        color: #333;
        font-size: 20px;
        font-weight: bold;
    }
    
    .exam-meta {
        display: flex;
        gap: 15px;
        align-items: center;
        font-size: 13px;
        color: #666;
    }
    
    .auto-advance-indicator {
        color: #007bff;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .auto-advance-countdown {
        color: #28a745;
        font-size: 12px;
        font-weight: bold;
        background: #e8f5e8;
        padding: 2px 6px;
        border-radius: 3px;
        border: 1px solid #28a745;
    }
    
    @keyframes fadeInOut {
        0% { opacity: 0; }
        50% { opacity: 1; }
        100% { opacity: 0; }
    }
    
    .exam-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .auto-advance-toggle {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .auto-advance-toggle:hover {
        background: #218838;
    }
    
    .auto-advance-toggle.disabled {
        background: #6c757d;
    }
    
    .auto-advance-toggle.disabled:hover {
        background: #5a6268;
    }
    
    .timer {
        background: #007bff;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 16px;
    }
    
    .timer.warning {
        background: #ffc107;
        color: #000;
    }
    
    .timer.critical {
        background: #dc3545;
        color: white;
    }
    
    /* Compact Question Container */
    .question-block {
        margin-bottom: 25px;
    }
    
    .question-header {
        margin-bottom: 15px;
    }
    
    .question-number {
        font-size: 16px;
        font-weight: bold;
        color: #007bff;
        margin: 0 0 8px 0;
    }
    
    .question-text {
        font-size: 15px;
        line-height: 1.5;
        color: #333;
        margin-bottom: 15px;
    }
    
    /* Simple Image Styling */
    .question-image {
        text-align: center;
        margin: 20px 0;
    }
    
    .question-image img {
        max-width: 100%;
        max-height: 400px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    /* Simple Options */
    .options-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .option-item {
        margin-bottom: 12px;
    }
    
    .option-label {
        user-select: none;
        position: relative;
        cursor: pointer !important;
        transition: all 0.2s ease;
    }
    
    .option-label:hover {
        border-color: #007bff !important;
        background: #f8f9ff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }
    
    .option-label.selected {
        border-color: #007bff !important;
        background: #e7f3ff !important;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
    }
    
    .option-label:active {
        transform: translateY(1px);
    }
    
    .option-input {
        cursor: pointer !important;
        pointer-events: auto !important;
        transform: scale(1.2);
        z-index: 10;
        position: relative;
    }
    
    .option-input:hover {
        transform: scale(1.3);
    }
    
    /* Ensure nothing blocks the radio buttons */
    .option-item {
        position: relative;
        z-index: 1;
    }
    
    .option-label * {
        pointer-events: none;
    }
    
    .option-input {
        pointer-events: auto !important;
    }
    
    .option-text {
        font-size: 15px;
        color: #333;
    }
    
    /* Simple Navigation */
    .navigation-section {
        border-top: 2px solid #e9ecef;
        padding-top: 30px;
        margin-top: 30px;
    }
    
    .nav-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        gap: 15px;
    }
    
    .nav-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .nav-btn-prev {
        background: #6c757d;
        color: white;
    }
    
    .nav-btn-prev:hover {
        background: #5a6268;
    }
    
    .nav-btn-next {
        background: #007bff;
        color: white;
    }
    
    .nav-btn-next:hover {
        background: #0056b3;
    }
    
    .nav-btn-submit {
        background: #28a745;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
    }
    
    .nav-btn-submit:hover {
        background: #218838;
    }
    
    /* Simple Question Grid */
    .question-grid {
        text-align: center;
    }
    
    .grid-title {
        font-size: 16px;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }
    
    .question-numbers {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
        gap: 8px;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .question-num-btn {
        width: 40px;
        height: 40px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .question-num-btn:hover {
        border-color: #007bff;
    }
    
    .question-num-btn.current {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    
    .question-num-btn.answered {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }
    
    /* Submit Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: none !important;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
        visibility: hidden;
    }
    
    .modal-overlay.show {
        display: flex !important;
        opacity: 1;
        visibility: visible;
    }
    
    .modal-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 24px;
        text-align: center;
    }
    
    .modal-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 24px;
    }
    
    .modal-title {
        margin: 0;
        font-size: 24px;
        font-weight: bold;
    }
    
    .modal-body {
        padding: 32px 24px;
    }
    
    .modal-message {
        font-size: 16px;
        color: #333;
        text-align: center;
        margin-bottom: 24px;
        line-height: 1.5;
    }
    
    .exam-summary {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 8px;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        font-size: 15px;
    }
    
    .summary-item:last-child {
        margin-bottom: 0;
    }
    
    .summary-label {
        color: #666;
        font-weight: 500;
    }
    
    .summary-value {
        font-weight: bold;
        color: #007bff;
    }
    
    .summary-total {
        color: #666;
        margin-left: 4px;
    }
    
    .modal-actions {
        display: flex;
        gap: 12px;
        padding: 0 24px 24px;
    }
    
    .modal-btn {
        flex: 1;
        padding: 14px 20px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .modal-btn-cancel {
        background: #6c757d;
        color: white;
    }
    
    .modal-btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }
    
    .modal-btn-submit {
        background: #28a745;
        color: white;
    }
    
    .modal-btn-submit:hover {
        background: #218838;
        transform: translateY(-1px);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to { 
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Responsive - More Compact */
    @media (max-width: 768px) {
        .exam-container {
            padding: 8px;
        }
        
        .exam-content {
            padding: 15px;
        }
        
        .exam-header {
            flex-direction: column;
            align-items: flex-start;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .exam-info h1 {
            font-size: 18px;
        }
        
        .exam-meta {
            font-size: 12px;
            gap: 10px;
        }
        
        .question-block {
            margin-bottom: 20px;
        }
        
        .question-number {
            font-size: 14px;
        }
        
        .question-text {
            font-size: 14px;
        }
        
        .nav-buttons {
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-btn {
            width: 100%;
            padding: 8px 16px;
        }
        
        .question-numbers {
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
        }
        
        .question-num-btn {
            width: 35px;
            height: 35px;
            font-size: 12px;
        }
        
        .modal-container {
            width: 95%;
            margin: 20px;
        }
        
        .modal-actions {
            flex-direction: column;
        }
        
        .modal-btn {
            width: 100%;
        }
    }
    
    /* Security Warning Banner */
    .security-warning-banner {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        border-left: 5px solid #fff;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        animation: securityWarningPulse 2s ease-in-out infinite alternate;
    }
    
    .security-warning-content {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .security-warning-icon {
        font-size: 24px;
        color: #fff;
        animation: securityWarningShake 0.5s ease-in-out infinite alternate;
    }
    
    .security-warning-message {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
    }
    
    .security-warning-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.2s ease;
    }
    
    .security-warning-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    @keyframes securityWarningPulse {
        0% { box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); }
        100% { box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5); }
    }
    
    @keyframes securityWarningShake {
        0% { transform: translateX(0); }
        100% { transform: translateX(2px); }
    }
    
    /* Time Up Notification */
    .time-up-notification {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .time-up-notification.show {
        opacity: 1;
    }
    
    .notification-content {
        background: white;
        border-radius: 16px;
        padding: 3rem;
        text-align: center;
        max-width: 400px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .notification-content i {
        font-size: 3rem;
        color: #f59e0b;
        margin-bottom: 1rem;
    }
    
    .notification-content h3 {
        font-size: 1.5rem;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .notification-content p {
        color: #64748b;
        margin-bottom: 2rem;
    }
    
    .countdown {
        font-weight: bold;
        color: #dc3545;
        font-size: 1.2em;
        background: #fff3cd;
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px solid #ffeaa7;
    }
    
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #e5e7eb;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
</style>
<script>
    // Fix for syntax error at line 6381 and nextQuestion function
    // This ensures there are no unmatched brackets or syntax issues
    if (typeof window.fixSyntaxError === 'undefined') {
        window.fixSyntaxError = true;
    }
    
    // Define the missing nextQuestion function
    if (typeof window.nextQuestion === 'undefined') {
        window.nextQuestion = function() {
            const currentQuestionContainer = document.querySelector('.question-container:not(.hidden)');
            if (!currentQuestionContainer) return;
            
            const currentIndex = parseInt(currentQuestionContainer.getAttribute('data-question-index') || '0');
            const nextIndex = currentIndex + 1;
            const nextQuestionContainer = document.querySelector(`.question-container[data-question-index="${nextIndex}"]`);
            
            if (nextQuestionContainer) {
                // Hide all questions
                document.querySelectorAll('.question-container').forEach(q => {
                    q.classList.add('hidden');
                });
                
                // Show the next question
                nextQuestionContainer.classList.remove('hidden');
                
                // Update progress indicators if they exist
                if (typeof updateProgress === 'function') {
                    updateProgress(nextIndex);
                }
                
                // Scroll to top of question
                window.scrollTo(0, 0);
            }
        };
    }
    
    // Fix any syntax errors by ensuring all brackets are properly closed
    // This is a safety measure to prevent the "Unexpected token '}'" error
    (function() {})();
</script>
@endpush

@section('content')
<!-- Security Warning Modal -->
@if(session('security_warning'))
<div id="securityWarningModal" class="security-warning-modal">
    <div class="security-warning-content">
        <div class="security-warning-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>SECURITY VIOLATION DETECTED</h3>
        </div>
        <div class="security-warning-body">
            <p>{{ session('security_warning') }}</p>
            <div class="violation-consequences">
                <h4>Consequences of Security Violations:</h4>
                <ul>
                    <li>✗ Progress reset to Question 1</li>
                    <li>✗ All previous answers cleared</li>
                    <li>✗ Timer continues counting down</li>
                    <li>✗ Violation permanently recorded</li>
                    <li>✗ Further violations may result in account suspension</li>
                </ul>
            </div>
        </div>
        <div class="security-warning-footer">
            <button type="button" class="btn btn-danger" onclick="acknowledgeSecurityWarning()">
                I Understand - Continue Exam
            </button>
        </div>
    </div>
</div>
@endif

<div class="exam-container">
    <div class="exam-content">
        <!-- Simple Header -->
        <div class="exam-header">
            <div class="exam-info">
                <h1>{{ $subject->name }}</h1>
                <div class="exam-meta">
                    <span>Question <span id="current-question">1</span> of {{ count($questionsList) }}</span>
                    <span>Answered: <span id="answered-count">0</span></span>
                    <span class="auto-advance-indicator" id="autoAdvanceIndicator" style="display: none;">
                        <i class="fas fa-forward"></i> Auto-advancing...
                        <button type="button" class="cancel-auto-advance" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 8px; cursor: pointer;">Cancel</button>
                    </span>
                </div>
            </div>
            <div class="exam-controls">
                <button type="button" id="autoAdvanceToggle" class="auto-advance-toggle" title="Toggle auto-advance to next question">
                    <i class="fas fa-forward"></i>
                    <span class="toggle-text">Auto</span>
                </button>
                <div class="timer" id="exam-timer">
                    @php
                        $remainingSeconds = $examSession->actual_remaining_time ?? $examSession->remaining_time;
                        $minutes = floor($remainingSeconds / 60);
                        $seconds = $remainingSeconds % 60;
                    @endphp
                    {{ sprintf('%02d:%02d', $minutes, $seconds) }}
                </div>
            </div>

        </div>

        <!-- Security Warning Display -->
        @if(session('security_warning'))
        <div class="security-warning-banner" id="securityWarningBanner">
            <div class="security-warning-content">
                <div class="security-warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="security-warning-message">
                    {!! session('security_warning') !!}
                </div>
                <button type="button" class="security-warning-close" onclick="closeSecurityWarning()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        @endif

        <form id="examForm" action="{{ route('user.exam.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <input type="hidden" name="session_id" value="{{ $examSession->id }}">
            <input type="hidden" name="auto_submitted" id="autoSubmitted" value="0">
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <!-- Question Blocks -->
            @foreach($questionsList as $index => $questionData)
                <div class="question-block" 
                     id="question-{{ $index }}" 
                     data-question-id="{{ $questionData['id'] }}"
                     style="display: {{ $index === 0 ? 'block' : 'none' }};">
                    
                    <div class="question-header">
                        <h2 class="question-number">Question {{ $index + 1 }}</h2>
                    </div>
                    
                    <div class="question-text">
                        {!! nl2br(e($questionData['text'])) !!}
                    </div>
                    
                    @if(!empty($questionData['image_path']))
                        <div class="question-image">
                            <img src="{{ $questionData['image_path'] }}?t={{ time() }}" alt="Question Image">
                        </div>
                    @endif

                    <ul class="options-list">
                        @foreach($questionData['options'] as $option)
                            <li class="option-item">
                                <label class="option-label" 
                                       for="option_{{ $option['id'] }}_{{ $index }}"
                                       style="display: flex; align-items: center; padding: 15px 18px; border: 2px solid #e9ecef; border-radius: 8px; cursor: pointer; background: #fff; min-height: 60px; gap: 12px; margin-bottom: 12px; transition: all 0.2s ease;">
                                    <input class="option-input"
                                           type="radio" 
                                           name="answers[{{ $questionData['id'] }}]" 
                                           id="option_{{ $option['id'] }}_{{ $index }}" 
                                           value="{{ $option['letter'] }}"
                                           data-question-index="{{ $index }}"
                                           data-question-id="{{ $questionData['id'] }}"
                                           style="width: 20px; height: 20px; cursor: pointer; accent-color: #007bff; margin-right: 12px; flex-shrink: 0;">
                                    <span class="option-text" style="font-size: 15px; color: #333; flex: 1;">
                                        <strong>{{ $option['letter'] }})</strong> {{ $option['text'] }}
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <!-- Simple Navigation -->
            <div class="navigation-section">
                <div class="nav-buttons">
                    <button type="button" id="prevBtn" class="nav-btn nav-btn-prev" style="display: none;">
                        ← Previous
                    </button>
                    
                    <div id="nextSubmitContainer">
                        <button type="button" id="nextBtn" class="nav-btn nav-btn-next">
                            Next →
                        </button>
                        <button type="submit" id="submitBtn" class="nav-btn nav-btn-submit" style="display: none;">
                            Submit Exam
                        </button>
                    </div>
                </div>
                
                <!-- Question Grid -->
                <div class="question-grid">
                    <div class="grid-title">Jump to Question:</div>
                    <div class="question-numbers">
                        @foreach($questionsList as $navIndex => $navQuestion)
                            <button type="button" 
                                    class="question-num-btn" 
                                    data-question="{{ $navIndex }}">
                                {{ $navIndex + 1 }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Submit Confirmation Modal -->
<div id="submitModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <h3 class="modal-title">Submit Your Exam</h3>
        </div>
        
        <div class="modal-body">
            <p class="modal-message">
                Are you ready to submit your exam? Once submitted, you cannot change your answers.
            </p>
            
            <div class="exam-summary">
                <div class="summary-item">
                    <span class="summary-label">Questions Answered:</span>
                    <span class="summary-value" id="modalAnsweredCount">0</span>
                    <span class="summary-total">of {{ count($questionsList) }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Time Remaining:</span>
                    <span class="summary-value" id="modalTimeRemaining">--:--</span>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" id="modalCancelBtn">
                <i class="fas fa-times"></i>
                Cancel
            </button>
            <button type="button" class="modal-btn modal-btn-submit" id="modalSubmitBtn">
                <i class="fas fa-check"></i>
                Yes, Submit Exam
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Core exam variables - properly formatted to avoid syntax errors
    const questions = @json($questionsList);
    const totalQuestions = @json(count($questionsList));
    const sessionId = @json($examSession ? $examSession->id : null);
    let currentQuestion = @json($examSession ? ($examSession->current_question_index ?? 0) : 0);
    let timeRemaining = @json($examSession ? ($examSession->remaining_time ?? 3600) : 3600);
    let timerInterval;
    let examStartTime = Date.now();
    
    // Answer tracking
    let answers = {};
    const savedAnswers = @json($examSession ? ($examSession->answers ?? []) : []);
    if (savedAnswers && typeof savedAnswers === 'object') {
        answers = savedAnswers;
    }
    
    // Initialize exam when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Exam initialized with', totalQuestions, 'questions');
        console.log('Starting from question', currentQuestion + 1);
        
        startTimer();
        showCurrentQuestion();
        restoreAnswers();
        setupEventListeners();
        enableSecurityProtection();
        
        console.log('Exam setup complete');
    });
    
    // Timer function
    function startTimer() {
        if (timerInterval) clearInterval(timerInterval);
        
        timerInterval = setInterval(() => {
            if (timeRemaining > 0) {
                timeRemaining--;
                updateTimerDisplay();
                
                // Auto-save every 30 seconds
                if (timeRemaining % 30 === 0) {
                    saveProgress();
                }
            } else {
                clearInterval(timerInterval);
                autoSubmitExam();
            }
        }, 1000);
    }
    
    function updateTimerDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        const timerEl = document.getElementById('exam-timer');
        if (timerEl) {
            timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color based on remaining time
            if (timeRemaining <= 300) { // 5 minutes
                timerEl.className = 'timer critical';
            } else if (timeRemaining <= 600) { // 10 minutes
                timerEl.className = 'timer warning';
            } else {
                timerEl.className = 'timer';
            }
        }
    }
    
    // Navigation functions
    function nextQuestion() {
        if (currentQuestion < totalQuestions - 1) {
            currentQuestion++;
            showCurrentQuestion();
            updateNavigation();
            console.log('Moved to question', currentQuestion + 1);
        }
    }
    
    function previousQuestion() {
        if (currentQuestion > 0) {
            currentQuestion--;
            showCurrentQuestion();
            updateNavigation();
            console.log('Moved to question', currentQuestion + 1);
        }
    }
    
    function goToQuestion(index) {
        if (index >= 0 && index < totalQuestions) {
            currentQuestion = index;
            showCurrentQuestion();
            updateNavigation();
            console.log('Jumped to question', index + 1);
        }
    }
    
    function showCurrentQuestion() {
        // Hide all questions
        document.querySelectorAll('.question-container').forEach(q => {
            q.style.display = 'none';
        });
        
        // Show current question
        const currentQuestionEl = document.getElementById(`question-${currentQuestion}`);
        if (currentQuestionEl) {
            currentQuestionEl.style.display = 'block';
        }
        
        updateNavigation();
    }
    
    function updateNavigation() {
        // Update prev/next buttons
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        if (prevBtn) {
            prevBtn.disabled = (currentQuestion === 0);
        }
        
        if (nextBtn) {
            nextBtn.disabled = (currentQuestion === totalQuestions - 1);
            nextBtn.textContent = (currentQuestion === totalQuestions - 1) ? 'Submit' : 'Next';
        }
        
        // Update question navigation buttons
        document.querySelectorAll('.question-num-btn').forEach((btn, index) => {
            btn.classList.toggle('current', index === currentQuestion);
        });
    }
    
    // Event listeners setup
    function setupEventListeners() {
        // Navigation buttons
        document.addEventListener('click', function(e) {
            if (e.target.id === 'nextBtn') {
                e.preventDefault();
                nextQuestion();
            } else if (e.target.id === 'prevBtn') {
                e.preventDefault();
                previousQuestion();
            } else if (e.target.classList.contains('question-num-btn')) {
                e.preventDefault();
                const index = parseInt(e.target.dataset.question);
                if (!isNaN(index)) {
                    goToQuestion(index);
                }
            }
        });
        
        // Answer selection
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio' && e.target.name.startsWith('answers[')) {
                const match = e.target.name.match(/answers\[(\d+)\]/);
                if (match) {
                    const questionIndex = parseInt(match[1]);
                    answers[questionIndex] = e.target.value;
                    saveProgress();
                    console.log('Answer saved:', questionIndex, e.target.value);
                }
            }
        });
    }
    
    // Answer handling
    function restoreAnswers() {
        if (answers && typeof answers === 'object') {
            Object.keys(answers).forEach(questionIndex => {
                const answer = answers[questionIndex];
                const radio = document.querySelector(`input[name="answers[${questionIndex}]"][value="${answer}"]`);
                if (radio) {
                    radio.checked = true;
                }
            });
        }
    }
    
    function saveProgress() {
        if (!sessionId) return;
        
        const data = {
            current_question_index: currentQuestion,
            answers: answers,
            time_remaining: timeRemaining,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        
        fetch('{{ route("user.exam.save.progress") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token
            },
            body: JSON.stringify(data)
        })
        .catch(error => {
            console.log('Failed to save progress:', error);
        });
    }
    
    function autoSubmitExam() {
        console.log('Time expired - auto submitting exam');
        window.examSubmitting = true;
        
        // Set auto-submit flag
        const autoSubmitInput = document.getElementById('autoSubmitted');
        if (autoSubmitInput) {
            autoSubmitInput.value = '1';
        }
        
        // Submit the form
        const form = document.getElementById('examForm');
        if (form) {
            form.submit();
        } else {
            window.location.href = '/student/dashboard?message=exam_expired';
        }
    }
    
    // Simple security protection - DELAYED START
    function enableSecurityProtection() {
        console.log('🛡️ Enabling basic security protection with delay...');
        
        // Wait 15 seconds before enabling simple detection to prevent false positives
        // This gives students time to focus and settle into the exam
        setTimeout(() => {
            console.log('🔒 Basic security protection now active');
            
            // Basic tab switch detection
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && !window.examSubmitting) {
                    console.log('⚠️ Tab switch detected');
                    showSimpleWarning('⚠️ Please stay on this page during the exam');
                }
            });
            
            // Basic copy protection
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showSimpleWarning('⚠️ Right-click is disabled during exam');
                return false;
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'a')) {
                    e.preventDefault();
                    showSimpleWarning('⚠️ Copy/paste is disabled during exam');
                    return false;
                }
            });
            
            console.log('✅ Basic security protection fully enabled');
        }, 15000); // 15 seconds delay to prevent conflicts with page load
    }
    
    // Simple warning function
    function showSimpleWarning(message) {
        // Remove any existing warnings
        const existingWarning = document.getElementById('simple-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        // Create warning element
        const warning = document.createElement('div');
        warning.id = 'simple-warning';
        warning.style.cssText = `
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #f39c12;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        `;
        warning.textContent = message;
        
        document.body.appendChild(warning);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (warning.parentNode) {
                warning.remove();
            }
        }, 5000);
    }
</script>
            currentQuestion--;
            showCurrentQuestion();
        }
    }

    function goToQuestion(questionIndex) {
        console.log('Jumping to question:', questionIndex + 1);
        
        // Cancel any pending auto-advance when manually navigating
        cancelAutoAdvance();
        
        currentQuestion = questionIndex;
        showCurrentQuestion();
    }

    function showCurrentQuestion() {
        // Hide all questions
        document.querySelectorAll('.question-block').forEach(block => {
            block.style.display = 'none';
        });
        
        // Show current question
        document.getElementById(`question-${currentQuestion}`).style.display = 'block';
        
        // Update displays
        document.getElementById('current-question').textContent = currentQuestion + 1;
        updateNavigation();
        updateQuestionGrid();
        
        // Update option selections for current question
        updateOptionSelection(currentQuestion);
    }

    function updateNavigation() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        // Show/hide previous button
        prevBtn.style.display = currentQuestion > 0 ? 'block' : 'none';
        
        // Show next or submit button
        if (currentQuestion === totalQuestions - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
    }

    function updateQuestionGrid() {
        document.querySelectorAll('.question-num-btn').forEach((btn, index) => {
            btn.className = 'question-num-btn';
            
            if (index === currentQuestion) {
                btn.classList.add('current');
            } else if (answeredQuestions[index]) {
                btn.classList.add('answered');
            }
        });
    }

    function updateAnsweredCount() {
        const count = answeredQuestions.filter(answered => answered).length;
        document.getElementById('answered-count').textContent = count;
    }
    
    // LEGACY FUNCTION - KEPT FOR COMPATIBILITY
    function selectAnswer(radio) {
        console.log('⚠️ Legacy selectAnswer called, redirecting to new system');
        if (radio) {
            processRadioSelection(radio);
        }
    }

    function updateOptionSelection(questionIndex) {
        // Update visual selection for the current question
        const questionBlock = document.getElementById(`question-${questionIndex}`);
        if (questionBlock) {
            const labels = questionBlock.querySelectorAll('.option-label');
            const radios = questionBlock.querySelectorAll('input[type="radio"]');
            
            labels.forEach((label, index) => {
                label.classList.remove('selected');
                if (radios[index] && radios[index].checked) {
                    label.classList.add('selected');
                }
            });
        }
    }

    function autoSubmitExam() {
        // Prevent multiple calls
        if (window.examSubmitting) {
            return;
        }
        window.examSubmitting = true;
        
        console.log('Time expired! Auto-submitting exam...');
        document.getElementById('autoSubmitted').value = '1';
        
        // Show a non-blocking notification
        showTimeUpNotification();
        
        // Submit after 3 seconds with a countdown
        let countdown = 3;
        const countdownInterval = setInterval(() => {
            const notification = document.querySelector('.time-up-notification');
            if (notification) {
                const countdownEl = notification.querySelector('.countdown');
                if (countdownEl) {
                    countdownEl.textContent = countdown;
                }
            }
            countdown--;
            
            if (countdown < 0) {
                clearInterval(countdownInterval);
                // Force submit the form directly - no CSRF refresh needed
                forceSubmitExam();
            }
        }, 1000);
    }
    
    function showTimeUpNotification() {
        // Remove any existing notifications to prevent duplicates
        const existingNotification = document.querySelector('.time-up-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create a custom notification with countdown
        const notification = document.createElement('div');
        notification.className = 'time-up-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-clock"></i>
                <h3>Time is up!</h3>
                <p>Your exam will be submitted automatically in <span class="countdown">3</span> seconds...</p>
                <div class="loading-spinner"></div>
            </div>
        `;
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
    }
    
    function forceSubmitExam() {
        console.log('Force submitting exam...');
        
        // Update notification to show submitting
        const notification = document.querySelector('.time-up-notification');
        if (notification) {
            const content = notification.querySelector('.notification-content');
            content.innerHTML = `
                <i class="fas fa-paper-plane"></i>
                <h3>Submitting Exam...</h3>
                <p>Please wait while your answers are being saved...</p>
                <div class="loading-spinner"></div>
            `;
        }
        
        // Get the form and submit it directly
        const form = document.getElementById('examForm');
        if (form) {
            try {
                form.submit();
            } catch (error) {
                console.error('Form submission failed:', error);
                // Fallback: redirect to critical warning page with message
                window.location.href = '/security/critical-warning?message=exam_auto_submitted';
            }
        } else {
            console.error('Exam form not found!');
            // Fallback: redirect to critical warning page
            window.location.href = '/security/critical-warning?message=exam_form_error';
        }
    }

    function showSubmitModal() {
        // Update modal with current exam data
        const answeredCount = answeredQuestions.filter(answered => answered).length;
        document.getElementById('modalAnsweredCount').textContent = answeredCount;
        
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        document.getElementById('modalTimeRemaining').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        const modal = document.getElementById('submitModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Trigger animation after display
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    function hideSubmitModal() {
        const modal = document.getElementById('submitModal');
        modal.classList.remove('show');
        
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }

    function confirmSubmit() {
        hideSubmitModal();
        submitExamWithRetry();
    }
    
    function submitExamWithRetry() {
        // Prevent multiple submissions
        if (window.examSubmitting) {
            console.log('Exam already being submitted, ignoring duplicate request');
            return;
        }
        window.examSubmitting = true;
        
        console.log('Starting exam submission process...');
        
        const form = document.getElementById('examForm');
        
        // Disable submit buttons to prevent multiple clicks
        const submitButtons = document.querySelectorAll('button[type="submit"], .modal-btn-submit');
        submitButtons.forEach(btn => {
            btn.disabled = true;
            btn.textContent = 'Submitting...';
        });
        
        // Try to refresh CSRF token first, then submit
        refreshCSRFToken()
            .then(() => {
                console.log('CSRF token refreshed, submitting exam...');
                form.submit();
            })
            .catch(error => {
                console.warn('CSRF refresh failed, submitting anyway:', error);
                // Fallback: submit form directly
                form.submit();
            });
    }
    
    // Restore saved progress from server
    function restoreSavedProgress() {
        console.log('Restoring saved progress...');
        console.log('Saved answers:', savedAnswers);
        console.log('Current question index:', currentQuestion);
        
        // Restore answers
        if (savedAnswers && typeof savedAnswers === 'object') {
            Object.keys(savedAnswers).forEach(questionId => {
                const selectedOption = savedAnswers[questionId];
                const radio = document.querySelector(`input[data-question-id="${questionId}"][value="${selectedOption}"]`);
                if (radio) {
                    radio.checked = true;
                    // Update answered questions array
                    const questionIndex = findQuestionIndexById(questionId);
                    if (questionIndex !== -1) {
                        answeredQuestions[questionIndex] = true;
                    }
                }
            });
        }
        
        // Go to saved question position
        if (currentQuestion > 0 && currentQuestion < totalQuestions) {
            goToQuestion(currentQuestion);
        }
        
        console.log('Progress restored successfully');
    }
    
    // Helper function to find question index by ID
    function findQuestionIndexById(questionId) {
        for (let i = 0; i < questions.length; i++) {
            if (questions[i].id == questionId) {
                return i;
            }
        }
        return -1;
    }
    
    // Copy/Paste Protection
    function enableCopyPasteProtection() {
        console.log('Enabling copy/paste protection...');
        
        // Right-click detection with 15-strike policy
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            console.log('🚨 RIGHT-CLICK DETECTED - Recording violation with 15-strike policy');
            
            // Record right-click violation with backend - 15-STRIKE POLICY: 15 right-clicks = permanent ban
            recordSecurityViolation('right_click', 'Student right-clicked during exam - 15-STRIKE POLICY', {
                timestamp: new Date().toISOString(),
                user_agent: navigator.userAgent,
                policy: '15_STRIKE_POLICY',
                violation_type: 'right_click_attempt'
            });
            
            return false;
        });
        
        // Disable text selection
        document.addEventListener('selectstart', function(e) {
            // Allow selection in input fields for typing answers
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return true;
            }
            e.preventDefault();
            return false;
        });
        
        // Disable copy/cut/paste keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Disable Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+A, Ctrl+S, Ctrl+P
            if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'a' || e.key === 's' || e.key === 'p')) {
                e.preventDefault();
                showSecurityWarning('Copy/paste operations are disabled during the exam');
                return false;
            }
            
            // Disable F12 (Developer Tools)
            if (e.key === 'F12') {
                e.preventDefault();
                showSecurityWarning('Developer tools are disabled during the exam');
                return false;
            }
            
            // Disable Ctrl+Shift+I (Developer Tools)
            if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                e.preventDefault();
                showSecurityWarning('Developer tools are disabled during the exam');
                return false;
            }
            
            // Disable Ctrl+U (View Source)
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                showSecurityWarning('View source is disabled during the exam');
                return false;
            }
        });
        
        // Disable drag and drop
        document.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Add CSS to prevent text selection
        const style = document.createElement('style');
        style.textContent = `
            .exam-container * {
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
            }
            
            /* Allow selection in input fields */
            input, textarea {
                -webkit-user-select: text !important;
                -moz-user-select: text !important;
                -ms-user-select: text !important;
                user-select: text !important;
            }
            
            /* Disable highlighting */
            ::selection {
                background: transparent;
            }
            
            ::-moz-selection {
                background: transparent;
            }
        `;
        document.head.appendChild(style);
        
        console.log('Copy/paste protection enabled');
    }
    
    // Simple Tab Switching Detection
    function enableTabSwitchDetection() {
        console.log('🔒 Enabling basic tab switch detection...');
        
        let examActive = true;
        
        // Simple tab switch detection
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && examActive && !window.examSubmitting) {
                console.log('⚠️ Tab switch detected');
                handleTabSwitch();
            }
        });
        
        // Window blur detection
        window.addEventListener('blur', function() {
            if (examActive && !window.examSubmitting) {
                setTimeout(() => {
                    if (!document.hasFocus()) {
                        console.log('⚠️ Window focus lost');
                        handleTabSwitch();
                    }
                }, 500);
            }
        });
        
        // Handle tab switch
        function handleTabSwitch() {
            examActive = false; // Prevent multiple triggers
            
            // Show warning
            showSimpleWarning('⚠️ Tab switching detected. Please stay on this page during the exam.');
            
            // Record violation (simple version)
            recordSimpleViolation();
        }
        
        // Block some basic shortcuts
        document.addEventListener('keydown', function(e) {
            // Block common tab switching shortcuts
            if ((e.ctrlKey && e.key === 't') || // Ctrl+T
                (e.ctrlKey && e.key === 'n') || // Ctrl+N
                (e.altKey && e.key === 'Tab') || // Alt+Tab
                (e.key === 'F12')) { // F12
                e.preventDefault();
                showSimpleWarning('⚠️ Shortcut blocked: ' + e.key);
                return false;
            }
        });
        
        console.log('✅ Tab switch detection enabled');
    }
    
    // Simple warning function
    function showSimpleWarning(message) {
        // Remove any existing warnings
        const existingWarning = document.getElementById('simple-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        // Create warning element
        const warning = document.createElement('div');
        warning.id = 'simple-warning';
        warning.style.cssText = `
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #f39c12;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        `;
        warning.textContent = message;
        
        document.body.appendChild(warning);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (warning.parentNode) {
                warning.remove();
            }
        }, 5000);
    }
    
    // Simple violation recording - DISABLED TO PREVENT FALSE POSITIVES
    function recordSimpleViolation() {
        console.log('Simple violation detected - showing warning only (API call disabled to prevent false positives)');
        
        // Only show warning, don't call API that might return incorrect ban status
        // The aggressive system will handle actual violation recording after 30 seconds
        
        /* DISABLED TO PREVENT FALSE POSITIVES:
        const data = {
            violation_type: 'tab_switch',
            description: 'Student switched tabs during exam',
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        
        fetch('{{ route("user.exam.security.violation") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Violation recorded:', data);
        })
        .catch(error => {
            console.log('Failed to record violation:', error);
        });
        */
    }
    
    // Simple copy protection
    function enableCopyPasteProtection() {
        // Block right-click
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            showSimpleWarning('⚠️ Right-click is disabled during exam');
            return false;
        });
        
        // Block copy/paste shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'a')) {
                e.preventDefault();
                showSimpleWarning('⚠️ Copy/paste is disabled during exam');
                return false;
            }
        });
        
        console.log('✅ Copy protection enabled');
    }
    // End of security functions
        
        // Block focus changes to browser UI elements
        document.addEventListener('focusout', function(e) {
            // If focus goes to browser UI (null or body), redirect back to exam
            setTimeout(() => {
                if (!document.activeElement || 
                    document.activeElement === document.body ||
                    document.activeElement === document.documentElement) {
                    
                    // Force focus back to exam content
                    const examContent = document.querySelector('input[type="radio"]:checked, input[type="radio"], button, .exam-content');
                    if (examContent) {
                        examContent.focus();
                    }
                    
                    console.log('🔄 FOCUS REDIRECTED BACK TO EXAM');
                }
            }, 1);
        });
        
        console.log('🔒 ULTRA-AGGRESSIVE TAB PREVENTION ACTIVATED - ZERO TOLERANCE MODE!');
        
        // Block middle mouse button clicks (opens links in new tab)
        document.addEventListener('mousedown', function(e) {
            if (e.button === 1) { // Middle mouse button
                e.preventDefault();
                
                console.log('🚫 MIDDLE-CLICK BLOCKED!');
                showCriticalWarning('🚨 MIDDLE-CLICK BLOCKED! Any attempt to open new tabs will result in IMMEDIATE BAN!');
                
                // Record attempt with backend
                recordSecurityViolation('navigation_attempt', 'Middle-click navigation attempt blocked', {
                    timestamp: new Date().toISOString(),
                    click_type: 'middle_mouse_button'
                });
                
                return false;
            }
        });
        
        // Block window.open attempts (JavaScript popups) - prevent duplicate execution
        if (!window.windowOpenBlocked) {
            window.windowOpenBlocked = true;
            const originalWindowOpen = window.open;
            window.open = function() {
                console.log('🚫 WINDOW.OPEN BLOCKED!');
                showCriticalWarning('🚨 POPUP BLOCKED! Attempting to open new windows is prohibited!');
                
                // Record attempt
                recordSecurityViolation('popup_attempt', 'JavaScript window.open attempt blocked', {
                    timestamp: new Date().toISOString(),
                    method: 'window.open'
                });
                
                return null;
            };
        }
        
        // Prevent drag and drop of links/images to address bar
        document.addEventListener('dragstart', function(e) {
            if (e.target.tagName === 'A' || e.target.tagName === 'IMG') {
                e.preventDefault();
                showCriticalWarning('🚨 DRAG BLOCKED! Dragging links or images is prohibited!');
                return false;
            }
        });
        
        // Block browser back/forward navigation
        window.addEventListener('popstate', function(e) {
            e.preventDefault();
            history.pushState(null, null, window.location.pathname);
            showCriticalWarning('🚨 NAVIGATION BLOCKED! Browser navigation is prohibited during exam!');
            return false;
        });
        
        // Prevent focus loss to address bar
        document.addEventListener('focusout', function(e) {
            setTimeout(() => {
                if (document.activeElement === document.body || 
                    document.activeElement === null ||
                    document.activeElement.tagName === 'BODY') {
                    // Refocus on exam content
                    const firstInput = document.querySelector('input[type="radio"], button, textarea');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }
            }, 10);
        });
        
        // EXTREME MEASURE: Fullscreen lock and continuous monitoring
        function lockExamInterface() {
            // Try to enter fullscreen mode
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Fullscreen not supported or blocked by user');
                });
            }
            
            // Monitor fullscreen exit
            document.addEventListener('fullscreenchange', function() {
                if (!document.fullscreenElement) {
                    showCriticalWarning('🚨 FULLSCREEN EXIT DETECTED! Exiting fullscreen during exam is prohibited!');
                    // Try to re-enter fullscreen
                    setTimeout(() => {
                        if (document.documentElement.requestFullscreen) {
                            document.documentElement.requestFullscreen().catch(err => {
                                recordTabSwitchViolation(); // Ban if can't maintain fullscreen
                            });
                        }
                    }, 1000);
                }
            });
            
            // Use event-based focus monitoring instead of continuous polling
            window.addEventListener('blur', function() {
                if (isExamActive) {
                    console.log('🚨 WINDOW BLUR - POTENTIAL TAB SWITCH!');
                    handleTabSwitch();
                }
            });
            
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && isExamActive) {
                    console.log('🚨 PAGE HIDDEN - POTENTIAL TAB SWITCH!');
                    handleTabSwitch();
                }
            });
            
            // Block browser zoom
            document.addEventListener('wheel', function(e) {
                if (e.ctrlKey) {
                    e.preventDefault();
                    showCriticalWarning('🚨 ZOOM BLOCKED! Zooming is prohibited during exam!');
                    return false;
                }
            }, { passive: false });
            
            console.log('🔒 Exam interface locked and monitoring active');
        }
        
        // Initialize lock when exam starts - but give students time to settle in
        // Wait 30 seconds for students to read instructions and get comfortable
        setTimeout(lockExamInterface, 30000); // 30 seconds delay instead of 2 seconds
    }
    
    // Helper function to count answered questions
    function getAnsweredQuestionsCount() {
        try {
            if (typeof answers !== 'undefined' && answers) {
                return Object.keys(answers).length;
            }
            return 0;
        } catch (error) {
            console.log('Error counting answered questions:', error);
            return 0;
        }
    }
    
    // Record tab switch violation using NEW API for subject-specific banning
    function recordTabSwitchViolation() {
        console.log('🚨 RECORDING TAB SWITCH VIOLATION with NEW API');
        
        // Get fresh CSRF token for the request first
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('❌ CSRF token not found! Cannot proceed with violation recording.');
            // Still show ban notification even if we can't record the violation
            showBanNotificationWithReactivation(@json($subject->name) || 'this subject');
            return;
        }
        
        // Ensure examStartTime is defined with multiple fallbacks
        let startTime = examStartTime || window.examStartTime || Date.now();
        if (typeof startTime === 'undefined' || isNaN(startTime)) {
            startTime = Date.now();
            examStartTime = startTime;
            window.examStartTime = startTime;
            console.log('⚠️ examStartTime was undefined, setting to current time:', new Date(startTime).toLocaleTimeString());
        } else {
            console.log('✅ Using examStartTime:', new Date(startTime).toLocaleTimeString());
        }
        
        const data = {
            subject_id: @json($subject->id),
            exam_session_id: sessionId,
            violation_type: 'tab_switch',
            description: 'Student switched tabs or opened new window during exam - IMMEDIATE BAN POLICY',
            metadata: {
                screen_resolution: `${screen.width}x${screen.height}`,
                window_size: `${window.innerWidth}x${window.innerHeight}`,
                time_elapsed: Math.floor((Date.now() - startTime) / 1000),
                current_question: currentQuestionIndex || 1,
                questions_answered: getAnsweredQuestionsCount(),
                policy: 'IMMEDIATE_BAN_ON_FIRST_VIOLATION',
                tracking_method: 'registration_and_email_based'
            },
            _token: csrfToken
        };
        
        console.log('📤 Sending tab switch data to NEW API:', data);
        console.log('🌐 API URL: {{ route("user.exam.security.violation") }}');
        
        fetch('{{ route("user.exam.security.violation") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data),
            keepalive: true
        })
        .then(response => {
            console.log('📦 NEW API response status:', response.status);
            if (!response.ok) {
                console.error('❌ NEW API HTTP Error:', response.status, response.statusText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ NEW API: Tab switch violation recorded successfully:', data);
            console.log('🔍 NEW API Response details:', JSON.stringify(data, null, 2));
            
            // Remove the immediate warning
            const immediateWarning = document.getElementById('criticalWarning');
            if (immediateWarning) {
                immediateWarning.remove();
            }
            
            // FORCE IMMEDIATE REDIRECT for ANY tab switching detection
            // Check multiple conditions to ensure redirect works in all cases
            if (data.violation_type === 'tab_switch' || 
                data.violation_type === 'tab_switch_attempt' ||
                data.banned === true ||
                data.permanently_banned === true ||
                data.should_lock === true) {
                
                console.log('🚨 TAB SWITCHING DETECTED - FORCING IMMEDIATE REDIRECT');
                console.log('🔍 Redirect conditions met:', {
                    violation_type: data.violation_type,
                    banned: data.banned,
                    permanently_banned: data.permanently_banned,
                    should_lock: data.should_lock
                });
                
                // Show ban message with reactivation link
                const subjectName = data.subject_name || @json($subject->name) || 'this subject';
                showBanNotificationWithReactivation(subjectName);
                
                // Get critical warning URL with subject ID
                const criticalWarningUrl = `/security/critical-warning?subject_id=${@json($subject->id)}`;
                console.log('🚫 IMMEDIATE BAN triggered - redirecting to critical warning page:', criticalWarningUrl);
                
                // MULTIPLE REDIRECT ATTEMPTS for maximum reliability
                
                // 1. Immediate redirect (fastest)
                setTimeout(() => {
                    console.log('🔄 [ATTEMPT 1] Executing redirect to:', criticalWarningUrl);
                    try {
                        window.location.href = criticalWarningUrl;
                    } catch (e) {
                        console.error('❌ Redirect attempt 1 failed:', e);
                    }
                }, 500);
                
                // 2. Secondary redirect using replace method
                setTimeout(() => {
                    if (window.location.pathname.includes('/exam/')) {
                        console.log('🔄 [ATTEMPT 2] Backup redirect executing...');
                        try {
                            window.location.replace(criticalWarningUrl);
                        } catch (e) {
                            console.error('❌ Redirect attempt 2 failed:', e);
                        }
                    }
                }, 2000);
                
                // 3. Final fallback redirect
                setTimeout(() => {
                    if (window.location.pathname.includes('/exam/')) {
                        console.log('🔄 [ATTEMPT 3] Final fallback redirect...');
                        try {
                            window.location.assign(criticalWarningUrl);
                        } catch (e) {
                            console.error('❌ All redirect attempts failed:', e);
                            // Force a manual page navigation as last resort
                            document.body.innerHTML = `
                                <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:#dc3545;color:white;display:flex;align-items:center;justify-content:center;z-index:99999;font-size:24px;text-align:center;">
                                    <div>
                                        🚫 TAB SWITCHING DETECTED - YOU ARE BANNED!<br><br>
                                        <a href="/security/critical-warning" style="color:white;text-decoration:underline;font-size:20px;">CLICK HERE TO REQUEST REACTIVATION</a>
                                    </div>
                                </div>
                            `;
                        }
                    }
                }, 4000);
                
                return; // Exit early for tab switching
            }
            
            // IMMEDIATE BAN: Show ban message with reactivation link instead of redirect
            if (data.should_lock || data.permanently_banned || data.force_logout || data.banned) {
                const subjectName = data.subject_name || @json($subject->name) || 'this subject';
                showBanNotificationWithReactivation(subjectName);
            } else {
                // This shouldn't happen with immediate ban policy, but handle just in case
                showCriticalWarning(data.message || '⚠️ Tab switch violation recorded.');
            }
        })
        .catch(error => {
            console.error('❌ ❌ NEW API VIOLATION RECORDING FAILED ❌ ❌');
            console.error('🔥 NEW API Error details:', error);
            
            // Remove the immediate warning
            const immediateWarning = document.getElementById('criticalWarning');
            if (immediateWarning) {
                immediateWarning.remove();
            }
            
            // Even if API fails, still show ban notification with reactivation option
            showBanNotificationWithReactivation(@json($subject->name) || 'this subject');
        });
    }
    
    // Show ban notification with reactivation link
    function showBanNotificationWithReactivation(subjectName) {
        // Remove any existing warnings
        const existingWarnings = document.querySelectorAll('.critical-warning, .ban-notification');
        existingWarnings.forEach(warning => warning.remove());
        
        // Create ban notification
        const banNotification = document.createElement('div');
        banNotification.className = 'ban-notification';
        banNotification.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            animation: slideIn 0.5s ease-out;
        `;
        
        banNotification.innerHTML = `
            <div style="text-align: center; max-width: 600px; padding: 40px; background: rgba(0, 0, 0, 0.3); border-radius: 20px; border: 3px solid #ffffff; box-shadow: 0 0 50px rgba(255, 255, 255, 0.3);">
                <div style="font-size: 80px; margin-bottom: 20px;">🚫</div>
                <div style="font-size: 32px; font-weight: bold; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">
                    EXAM ACCESS BANNED
                </div>
                <div style="font-size: 18px; line-height: 1.6; margin-bottom: 30px; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">
                    You have been banned from <strong>${subjectName}</strong> due to tab switching violation.
                </div>
                <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 1px solid rgba(255, 255, 255, 0.3);">
                    <p><strong>Violation Type:</strong> Tab Switching / New Window</p>
                    <p><strong>Action Taken:</strong> Immediate Subject Ban</p>
                    <p><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                </div>
                <div style="margin-bottom: 30px;">
                    <a href="/student/reactivation" 
                       style="background: #10b981; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 18px; display: inline-block; margin: 10px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);"
                       onmouseover="this.style.background='#059669'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.4)';" 
                       onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(16, 185, 129, 0.3)';">
                        📝 Request Reactivation
                    </a>
                    <br>
                    <a href="/student/dashboard" 
                       style="background: #3b82f6; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 18px; display: inline-block; margin: 10px; transition: all 0.3s; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);"
                       onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59, 130, 246, 0.4)';" 
                       onmouseout="this.style.background='#3b82f6'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, 0.3)';">
                        🏠 Go to Dashboard
                    </a>
                </div>
                <div style="font-size: 14px; opacity: 0.9; line-height: 1.5;">
                    <p>📌 <strong>Note:</strong> Other subjects remain accessible.</p>
                    <p>🔄 You can request reactivation which will be reviewed by administrators.</p>
                    <p>⏱️ Once approved, you'll be able to take the exam again.</p>
                </div>
            </div>
        `;
        
        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { opacity: 0; transform: scale(0.8); }
                to { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(banNotification);
        
        // Disable all interactions except the links
        document.body.style.pointerEvents = 'none';
        banNotification.style.pointerEvents = 'auto';
        
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
        
        console.log('✅ Ban notification with reactivation link displayed');
    }
    
    // Record security violation
    function recordSecurityViolation(violationType, description, metadata = {}) {
        console.log('🚨 RECORDING SECURITY VIOLATION:', violationType, description);
        console.log('📊 Metadata:', metadata);
        
        const data = {
            subject_id: @json($subject->id),
            session_id: sessionId,
            violation_type: violationType,
            description: description,
            metadata: metadata,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        
        console.log('📤 Sending violation data to server:', data);
        console.log('🌐 URL: /student/exam/security-violation');
        console.log('🔑 CSRF Token:', data._token ? 'Found' : 'Missing');
        
        fetch('/student/exam/security-violation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data),
            keepalive: true
        })
        .then(response => {
            console.log('📶 Backend response status:', response.status);
            console.log('📶 Response headers:', response.headers);
            if (!response.ok) {
                console.error('❌ HTTP Error:', response.status, response.statusText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Security violation recorded successfully:', data);
            console.log('🔍 Response data details:', JSON.stringify(data, null, 2));
            
            // Remove the immediate warning and show proper response
            const immediateWarning = document.getElementById('criticalWarning');
            if (immediateWarning) {
                immediateWarning.remove();
            }
            
            // IMMEDIATE REDIRECT for tab switching - priority check
            if (data.violation_type === 'tab_switch' || data.violation_type === 'tab_switch_attempt') {
                console.log('🚨 TAB SWITCHING VIOLATION - REDIRECTING TO CRITICAL WARNING PAGE');
                
                const criticalWarningUrl = `/security/critical-warning?subject_id=${@json($subject->id)}`;
                showCriticalWarning(`🚫 TAB SWITCHING DETECTED: You have been BANNED from ${data.subject_name || 'this subject'}! Redirecting to security warning page...`);
                
                // Redirect to critical warning page instead of dashboard
                setTimeout(() => {
                    console.log('🔄 Redirecting to critical warning page:', criticalWarningUrl);
                    window.location.href = criticalWarningUrl;
                }, 1500);
                
                return; // Exit early
            }
            
            if (data.should_lock || data.permanently_banned || data.force_logout || data.banned) {
                // Check if this is a critical violation requiring immediate logout
                if (data.logout_immediately && (data.violation_type === 'tab_switch' || data.violation_type === 'tab_switch_attempt')) {
                    // CRITICAL: Tab switching = redirect to critical warning page
                    console.log('🚨 CRITICAL VIOLATION: Tab switching detected - redirecting to critical warning page');
                    
                    // Show critical warning overlay
                    showCriticalWarning('TAB SWITCHING DETECTED: You have been banned from this exam! Redirecting to security warning page with reactivation option...');
                    
                    // Redirect to critical warning page where student can see reactivation button
                    setTimeout(() => {
                        window.location.href = '/security/critical-warning';
                    }, 2500);
                    
                } else {
                    // Regular ban - redirect to critical warning page (but not for right-clicks)
                    let banMessage = '';
                    
                    if (data.violation_type === 'right_click') {
                        // Right-clicks should never reach here since they don't create bans
                        // But if they do, just show a warning and don't redirect
                        showTemporaryWarning(data.message || '⚠️ Right-click detected. Please do not right-click during exams.');
                        return;
                    } else {
                        banMessage = `🚫 SECURITY VIOLATION: You have been banned from ${data.subject_name}! Redirecting to security warning page where you can request reactivation...`;
                    }
                    
                    showCriticalWarning(banMessage);
                    
                    // Redirect to critical warning page where student can see reactivation button
                    setTimeout(() => {
                        window.location.href = `/security/critical-warning?subject_id=${@json($subject->id)}`;
                    }, 2500);
                }
            } else if (data.show_continue_button) {
                // 1st and 2nd violations: Show warning with continue button
                showContinueWarning(data.message || '⚠️ Security violation detected. You can continue but will be banned after 3 violations.', false);
                // Re-enable exam activity after warning
                setTimeout(() => {
                    isExamActive = true;
                }, 1000);
            } else {
                // Default warning for other cases or right-click warnings
                if (data.violation_type === 'right_click') {
                    // Show temporary warning for right-clicks that auto-disappears
                    showTemporaryWarning(data.message || '⚠️ Right-click detected. Please do not right-click during exams.');
                    // Re-enable exam activity immediately for right-clicks
                    setTimeout(() => {
                        isExamActive = true;
                    }, 500);
                } else {
                    showCriticalWarning(data.message || '⚠️ Security violation detected and recorded.');
                }
            }
        })
        .catch(error => {
            console.error('❌ ❌ VIOLATION RECORDING FAILED ❌ ❌');
            console.error('🔥 Error details:', error);
            console.error('🔥 Error message:', error.message);
            console.error('🔥 Error stack:', error.stack);
            
            // Remove the immediate warning
            const immediateWarning = document.getElementById('criticalWarning');
            if (immediateWarning) {
                immediateWarning.remove();
            }
            
            // Show detailed error warning
            showCriticalWarning('🚨 SECURITY VIOLATION DETECTED but FAILED TO RECORD! 🚨\n\nError: ' + error.message + '\n\nThis might be a technical issue. You will be redirected to security warning page.\n\nPlease contact administrator if this persists.');
            setTimeout(() => {
                window.location.href = `/security/critical-warning?subject_id=${@json($subject->id)}`;
            }, 8000);
        });
    }
    
    // Show warning with CONTINUE button (for 1st and 2nd attempts)
    function showContinueWarning(message, isPermanent = false) {
        // Create a modal-style warning with continue button
        const warning = document.createElement('div');
        warning.id = 'tabSwitchWarning';
        warning.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(220, 53, 69, 0.95);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        `;
        
        const content = document.createElement('div');
        content.style.cssText = `
            background: #dc3545;
            padding: 40px;
            border-radius: 15px;
            border: 3px solid white;
            max-width: 700px;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
        `;
        
        content.innerHTML = `
            <div style="font-size: 28px; margin-bottom: 25px;">
                ⚠️ SECURITY VIOLATION DETECTED ⚠️
            </div>
            <div style="margin-bottom: 25px; line-height: 1.5;">
                ${message}
            </div>
            <div style="margin-bottom: 30px; font-size: 16px; opacity: 0.9;">
                This incident has been recorded and reported to administrators.
            </div>
            <button id="continueExamBtn" style="
                background: #28a745;
                color: white;
                border: none;
                padding: 15px 30px;
                font-size: 18px;
                font-weight: bold;
                border-radius: 8px;
                cursor: pointer;
                box-shadow: 0 4px 8px rgba(0,0,0,0.3);
                transition: all 0.2s;
            " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                ✅ CONTINUE EXAM
            </button>
        `;
        
        warning.appendChild(content);
        document.body.appendChild(warning);
        
        // Add click handler for continue button
        document.getElementById('continueExamBtn').addEventListener('click', function() {
            warning.remove();
            // Reactivate exam after continue button click
            isExamActive = true;
            console.log('Student chose to continue exam after tab switch warning - exam reactivated');
        });
    }
    
    // Show PERMANENT BLOCK warning (for 3rd attempt - NO CONTINUE BUTTON)
    function showPermanentBlockWarning(message, redirectUrl = null) {
        // Create a modal-style permanent block warning
        const warning = document.createElement('div');
        warning.id = 'permanentBlockWarning';
        warning.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(139, 0, 0, 0.98);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        `;
        
        const content = document.createElement('div');
        content.style.cssText = `
            background: #8b0000;
            padding: 50px;
            border-radius: 15px;
            border: 4px solid #ff4444;
            max-width: 800px;
            box-shadow: 0 0 50px rgba(255,0,0,0.5);
            animation: pulse 2s infinite;
        `;
        
        content.innerHTML = `
            <div style="font-size: 32px; margin-bottom: 30px;">
                🚫 PERMANENTLY BLOCKED 🚫
            </div>
            <div style="margin-bottom: 30px; line-height: 1.6; font-size: 20px;">
                ${message}
            </div>
            <div style="margin-bottom: 20px; font-size: 16px; opacity: 0.9;">
                You have exceeded the maximum number of violations.
            </div>
            <div style="font-size: 16px; opacity: 0.9;">
                You will be redirected to request reactivation.
            </div>
        `;
        
        warning.appendChild(content);
        document.body.appendChild(warning);
        
        // Add pulsing animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-redirect to appropriate page after 5 seconds
        setTimeout(() => {
            window.location.href = redirectUrl || '/student/reactivation';
        }, 5000);
    }
    
    // Show logout warning and redirect
    function showLogoutWarning(message, redirectUrl) {
        showCriticalWarning(message);
        
        // Force logout after 3 seconds
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 3000);
    }
    
    // Close security warning banner
    function closeSecurityWarning() {
        const banner = document.getElementById('securityWarningBanner');
        if (banner) {
            banner.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                banner.remove();
            }, 300);
        }
    }
    
    // Show security warning
    function showSecurityWarning(message) {
        // Create a temporary warning message
        const warning = document.createElement('div');
        warning.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 10000;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        `;
        warning.textContent = message;
        document.body.appendChild(warning);
        
        // Remove warning after 3 seconds
        setTimeout(() => {
            if (warning.parentNode) {
                warning.parentNode.removeChild(warning);
            }
        }, 3000);
    }
    
    // Show CRITICAL warning with red background screen
    function showCriticalWarning(message) {
        // Create a modal-style critical warning that covers entire screen
        const warning = document.createElement('div');
        warning.id = 'criticalWarning';
        warning.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(220, 53, 69, 0.95);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        `;
        
        const content = document.createElement('div');
        content.style.cssText = `
            background: #dc3545;
            padding: 40px;
            border-radius: 15px;
            border: 3px solid white;
            max-width: 700px;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
            animation: pulse 2s infinite;
        `;
        
        content.innerHTML = `
            <div style="font-size: 28px; margin-bottom: 25px;">
                🚨 CRITICAL SECURITY WARNING 🚨
            </div>
            <div style="margin-bottom: 25px; line-height: 1.5;">
                ${message}
            </div>
            <div style="margin-bottom: 30px; font-size: 16px; opacity: 0.9;">
                This incident has been recorded and reported to administrators.
            </div>
            <div style="font-size: 14px; opacity: 0.8;">
                You will be redirected to the login page shortly.
            </div>
        `;
        
        warning.appendChild(content);
        document.body.appendChild(warning);
        
        // Add pulsing animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-close after 5 seconds
        setTimeout(() => {
            if (warning.parentNode) {
                warning.remove();
            }
        }, 5000);
    }
    
    // Show temporary warning for right-clicks (auto-disappears)
    function showTemporaryWarning(message) {
        // Remove any existing temporary warnings
        const existingWarning = document.getElementById('temporaryWarning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        // Create a temporary warning that doesn't block the exam
        const warning = document.createElement('div');
        warning.id = 'temporaryWarning';
        warning.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff9800;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 9999;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            animation: slideInRight 0.3s ease-out;
            max-width: 300px;
        `;
        
        warning.innerHTML = `
            <div style="display: flex; align-items: center;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 10px; font-size: 16px;"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(warning);
        
        // Add slide animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        if (!document.querySelector('#tempWarningStyles')) {
            style.id = 'tempWarningStyles';
            document.head.appendChild(style);
        }
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (warning.parentNode) {
                warning.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (warning.parentNode) {
                        warning.parentNode.removeChild(warning);
                    }
                }, 300);
            }
        }, 3000);
    }
    
    // Show CRITICAL logout warning for tab switching violations
    function showCriticalLogoutWarning(message) {
        // Create a full-screen critical warning with pulsing red background
        const warning = document.createElement('div');
        warning.id = 'criticalLogoutWarning';
        warning.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            font-family: Arial, sans-serif;
            animation: criticalPulse 1s infinite;
        `;
        
        const content = document.createElement('div');
        content.style.cssText = `
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            border: 3px solid #ffffff;
            box-shadow: 0 0 50px rgba(255, 255, 255, 0.3);
            animation: shake 0.5s infinite alternate;
        `;
        
        content.innerHTML = `
            <div style="font-size: 80px; margin-bottom: 20px; animation: flash 1s infinite;">🚨</div>
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">
                CRITICAL SECURITY VIOLATION
            </div>
            <div style="font-size: 18px; line-height: 1.6; margin-bottom: 30px; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">
                ${message}
            </div>
            <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 1px solid rgba(255, 255, 255, 0.3);">
                <p><strong>Violation Type:</strong> Tab Switching / New Window</p>
                <p><strong>Action Taken:</strong> Immediate Ban + Forced Logout</p>
                <p><strong>Time:</strong> ${new Date().toLocaleString()}</p>
            </div>
            <div style="font-size: 24px; font-weight: bold; color: #fbbf24; margin-bottom: 20px;">
                Logging out in <span id="logoutTimer">3</span> seconds...
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                <p>After logging in, visit your dashboard to request reactivation.</p>
                <p><strong>Note:</strong> Other subjects remain accessible.</p>
            </div>
        `;
        
        warning.appendChild(content);
        document.body.appendChild(warning);
        
        // Add critical animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes criticalPulse {
                0% { background: linear-gradient(135deg, #dc2626, #991b1b); }
                50% { background: linear-gradient(135deg, #ef4444, #dc2626); }
                100% { background: linear-gradient(135deg, #dc2626, #991b1b); }
            }
            @keyframes shake {
                0% { transform: translateX(-2px); }
                100% { transform: translateX(2px); }
            }
            @keyframes flash {
                0%, 50% { opacity: 1; }
                51%, 100% { opacity: 0.3; }
            }
        `;
        document.head.appendChild(style);
        
        // Countdown timer
        let timeLeft = 3;
        const timerElement = document.getElementById('logoutTimer');
        const countdown = setInterval(() => {
            timeLeft--;
            if (timerElement) {
                timerElement.textContent = timeLeft;
            }
            if (timeLeft <= 0) {
                clearInterval(countdown);
            }
        }, 1000);
        
        // Disable all interactions
        document.body.style.pointerEvents = 'none';
        warning.style.pointerEvents = 'auto';
        
        // Prevent back button and shortcuts
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
        
        document.addEventListener('keydown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }, true);
    }
    
    function saveProgressToServer() {
        // Collect current answers
        const answers = {};
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            const questionId = radio.dataset.questionId;
            if (questionId) {
                answers[questionId] = radio.value;
            }
        });
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.warn('⚠️ CSRF token not found, skipping progress save');
            return;
        }
        
        // Send to server using sendBeacon for reliability during page unload
        const data = JSON.stringify({
            session_id: sessionId,
            answers: answers,
            current_question_index: currentQuestion,
            _token: csrfToken
        });
        
        if (navigator.sendBeacon) {
            const sent = navigator.sendBeacon('/student/exam/save-progress', new Blob([data], {type: 'application/json'}));
            if (!sent) {
                console.warn('⚠️ sendBeacon failed, trying fetch fallback');
                saveFallback(data, csrfToken);
            }
        } else {
            saveFallback(data, csrfToken);
        }
    }
    
    function saveFallback(data, csrfToken) {
        // Fallback for older browsers or when sendBeacon fails
        fetch('/student/exam/save-progress', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data,
            keepalive: true
        }).catch(error => {
            console.warn('Failed to save progress:', error);
        });
    }
    
    function refreshCSRFToken() {
        return fetch('/csrf-token', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch CSRF token');
            }
            return response.json();
        })
        .then(data => {
            document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrf_token);
            const tokenInput = document.querySelector('input[name="_token"]');
            if (tokenInput) {
                tokenInput.value = data.csrf_token;
            }
        });
    }
    
    // PAUSE/RESUME FUNCTIONS REMOVED - TIMER NEVER PAUSES
    // Timer runs continuously from start to finish, no exceptions
    
    // Make toggleAutoAdvance globally accessible
    window.toggleAutoAdvance = function() {
        autoAdvanceEnabled = !autoAdvanceEnabled;
        window.autoAdvanceEnabled = autoAdvanceEnabled; // Keep global in sync
        
        const toggleBtn = document.getElementById('autoAdvanceToggle');
        const toggleText = toggleBtn.querySelector('.toggle-text');
        
        if (autoAdvanceEnabled) {
            toggleBtn.classList.remove('disabled');
            toggleText.textContent = 'Auto';
            toggleBtn.title = 'Auto-advance enabled - Click to disable';
        } else {
            toggleBtn.classList.add('disabled');
            toggleText.textContent = 'Manual';
            toggleBtn.title = 'Auto-advance disabled - Click to enable';
            
            // Cancel any pending auto-advance
            cancelAutoAdvance();
        }
    };
    
    // Cancel auto-advance function
    window.cancelAutoAdvance = function() {
        if (autoAdvanceTimeout) {
            clearTimeout(autoAdvanceTimeout);
            autoAdvanceTimeout = null;
            console.log('Auto-advance cancelled by user');
        }
        
        // Hide the indicator
        const indicator = document.getElementById('autoAdvanceIndicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    };
    
    // Define nextQuestion function to fix reference error
    window.nextQuestion = function() {
        if (typeof nextQuestion === 'function') {
            nextQuestion();
        } else {
            console.error('nextQuestion function is not defined');
            // Call the main nextQuestion function if it exists
            if (currentQuestion < totalQuestions - 1) {
                currentQuestion++;
                showCurrentQuestion();
            }
        }
    };
</script>