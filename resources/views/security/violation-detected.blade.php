@extends('layouts.student_app')

@section('title', 'Security Violation Detected')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/exam-security.css') }}">
@endpush

@section('content')
<div class="security-violation-container">
    <div class="security-violation-card">
        <div class="violation-header">
            <div class="violation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Security Violation Detected</h1>
        </div>
        
        <div class="violation-body">
            <div class="violation-message">
                <p class="lead">Your exam access has been temporarily suspended due to a security violation.</p>
                <div class="violation-details">
                    <h3>Violation Details</h3>
                    <ul class="violation-list">
                        <li>
                            <strong>Type:</strong> Tab Switching Detected
                        </li>
                        <li>
                            <strong>Subject:</strong> 
                            @php
                                $subjectName = 'Unknown Subject';
                                // Primary: Use subject passed from controller (enhanced fallback methods)
                                if(isset($subject) && $subject) {
                                    $subjectName = $subject->name;
                                } 
                                // Secondary: Use ban's subject relationship
                                elseif($ban && $ban->subject) {
                                    $subjectName = $ban->subject->name;
                                } 
                                // Tertiary: Use subjectId variable passed from controller
                                elseif(isset($subjectId) && $subjectId) {
                                    $fallbackSubject = \App\Models\Subject::find($subjectId);
                                    if($fallbackSubject) {
                                        $subjectName = $fallbackSubject->name;
                                    }
                                }
                                // Final fallback: try URL parameter
                                elseif(request()->get('subject_id')) {
                                    $fallbackSubject = \App\Models\Subject::find(request()->get('subject_id'));
                                    if($fallbackSubject) {
                                        $subjectName = $fallbackSubject->name;
                                    }
                                }
                            @endphp
                            {{ $subjectName }}
                        </li>
                        <li>
                            <strong>Time:</strong> {{ $ban && $ban->banned_at ? $ban->banned_at->format('F j, Y, g:i a') : now()->format('F j, Y, g:i a') }}
                        </li>
                        <li>
                            <strong>Reason:</strong> {{ $ban && $ban->ban_reason ? $ban->ban_reason : 'Opening multiple browser tabs during the exam is prohibited for security reasons.' }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="reactivation-section">
                <h2>Request Reactivation</h2>
                <p>You can request to have your access reactivated by submitting the form below. Your request will be reviewed by an administrator.</p>
                
                <form id="reactivationForm" action="{{ route('user.student.reactivation.store') }}" method="POST">
                    @csrf
                    @php
                        $formSubjectId = '';
                        // Primary: Use subjectId variable passed from controller
                        if(isset($subjectId) && $subjectId) {
                            $formSubjectId = $subjectId;
                        }
                        // Secondary: Use subject object passed from controller
                        elseif(isset($subject) && $subject) {
                            $formSubjectId = $subject->id;
                        }
                        // Tertiary: Use ban's subject relationship
                        elseif($ban && $ban->subject_id) {
                            $formSubjectId = $ban->subject_id;
                        }
                        // Final fallback: URL parameter
                        elseif(request()->get('subject_id')) {
                            $formSubjectId = request()->get('subject_id');
                        }
                    @endphp
                    <input type="hidden" name="subject_id" value="{{ $formSubjectId }}">
                    
                    <div class="form-group">
                        <label for="reactivationMessage">Explanation</label>
                        <textarea 
                            id="reactivationMessage" 
                            name="request_message" 
                            class="form-control @error('request_message') is-invalid @enderror" 
                            rows="5" 
                            placeholder="Please explain why you should be reactivated and what happened..."
                            required
                        >{{ old('request_message') }}</textarea>
                        @error('request_message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Please provide a detailed explanation of what happened and why you should be reactivated (minimum 50 characters).</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitRequestBtn">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('reactivationForm');
                    const textarea = document.getElementById('reactivationMessage');
                    const submitBtn = document.getElementById('submitRequestBtn');
                    
                    form.addEventListener('submit', function(e) {
                        const message = textarea.value.trim();
                        if (message.length < 50) {
                            e.preventDefault();
                            alert('Please provide at least 50 characters in your explanation.');
                            textarea.focus();
                            return false;
                        }
                    });
                    
                    // Add character counter
                    if (textarea) {
                        const counterDiv = document.createElement('div');
                        counterDiv.className = 'text-muted small mt-1';
                        counterDiv.innerHTML = '<span id="charCount">0</span>/50 characters minimum';
                        textarea.parentNode.appendChild(counterDiv);
                        
                        const charCount = document.getElementById('charCount');
                        
                        function updateCount() {
                            const count = textarea.value.length;
                            charCount.textContent = count;
                            
                            if (count < 50) {
                                charCount.style.color = '#dc3545';
                                submitBtn.disabled = true;
                                submitBtn.style.opacity = '0.6';
                            } else {
                                charCount.style.color = '#28a745';
                                submitBtn.disabled = false;
                                submitBtn.style.opacity = '1';
                            }
                        }
                        
                        textarea.addEventListener('input', updateCount);
                        updateCount();
                    }
                });
                </script>
                
                @if(session('success'))
                    <div class="alert alert-success mt-4">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger mt-4">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger mt-4">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Please correct the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="violation-footer">
            <div class="policy-note">
                <i class="fas fa-info-circle"></i>
                <p>Please note that repeated violations may result in permanent suspension from all exams. Reactivation is at the discretion of the administrator.</p>
            </div>
            <a href="{{ route('user.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>

<style>
    .security-violation-container {
        max-width: 800px;
        margin: 3rem auto;
        padding: 0 1rem;
    }
    
    .security-violation-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .violation-header {
        background: linear-gradient(135deg, #dc2626, #991b1b);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    
    .violation-header h1 {
        margin-top: 1rem;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .violation-icon {
        font-size: 4rem;
        color: #fbbf24;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .violation-body {
        padding: 2rem;
    }
    
    .violation-message {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .violation-message .lead {
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
    }
    
    .violation-details {
        background: #f3f4f6;
        padding: 1.5rem;
        border-radius: 8px;
        margin-top: 1.5rem;
        text-align: left;
    }
    
    .violation-list {
        list-style: none;
        padding: 0;
        margin: 1rem 0 0;
    }
    
    .violation-list li {
        padding: 0.75rem 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .violation-list li:last-child {
        border-bottom: none;
    }
    
    .reactivation-section {
        border-top: 1px solid #e5e7eb;
        margin-top: 2rem;
        padding-top: 2rem;
    }
    
    .reactivation-section h2 {
        margin-bottom: 1rem;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .violation-footer {
        background: #f9fafb;
        padding: 1.5rem 2rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .policy-note {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        color: #6b7280;
        max-width: 70%;
    }
    
    .policy-note i {
        margin-top: 0.25rem;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .policy-note p {
        margin: 0;
        font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
        .violation-footer {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .policy-note {
            max-width: 100%;
            margin-bottom: 1rem;
        }
    }
</style>
@endsection