@extends('layouts.student_app')

@section('title', 'Critical Security Warning')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/exam-security.css') }}">
<style>
    .critical-warning-container {
        max-width: 700px;
        margin: 3rem auto;
        padding: 0 1rem;
    }
    
    .critical-warning-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 15px 50px rgba(220, 38, 38, 0.25);
        border: 1px solid rgba(220, 38, 38, 0.2);
        animation: pulseWarning 2s infinite;
    }
    
    @keyframes pulseWarning {
        0%, 100% { box-shadow: 0 15px 50px rgba(220, 38, 38, 0.25); }
        50% { box-shadow: 0 15px 70px rgba(220, 38, 38, 0.5); }
    }
    
    .critical-header {
        background: linear-gradient(135deg, #dc2626, #991b1b);
        padding: 2rem;
        text-align: center;
        color: white;
    }
    
    .critical-icon {
        font-size: 5rem;
        color: #fbbf24;
        margin-bottom: 1rem;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .critical-body {
        padding: 2rem;
        text-align: center;
    }
    
    .critical-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 1rem;
        color: #dc2626;
    }
    
    .critical-description {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        color: #4b5563;
    }
    
    .violation-details {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.5rem;
        margin: 1rem 0 2rem;
        text-align: left;
    }
    
    .violation-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .violation-list li {
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
    }
    
    .violation-list li:last-child {
        border-bottom: none;
    }
    
    .actions {
        margin-top: 2rem;
    }
    
    .btn-action {
        padding: 0.75rem 2rem;
        font-weight: bold;
        border-radius: 6px;
        margin: 0 0.5rem;
        transition: all 0.3s ease;
    }
    
    .btn-reactivation {
        background: #1d4ed8;
        color: white;
    }
    
    .btn-reactivation:hover {
        background: #1e40af;
        transform: translateY(-2px);
    }
    
    .btn-dashboard {
        background: #4b5563;
        color: white;
    }
    
    .btn-dashboard:hover {
        background: #374151;
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="critical-warning-container">
    <div class="critical-warning-card">
        <div class="critical-header">
            <div class="critical-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>SECURITY VIOLATION</h1>
            <p>Your exam access has been suspended</p>
        </div>
        
        <div class="critical-body">
            <div class="critical-title">Critical Security Violation</div>
            <p class="critical-description">
                A security violation has been detected during your exam session. 
                Your access has been temporarily suspended due to:
            </p>
            
            <div class="violation-details">
                <ul class="violation-list">
                    <li>
                        <strong>Violation Type:</strong> 
                        <span>Tab Switching / Opening New Window</span>
                    </li>
                    <li>
                        <strong>Policy:</strong>
                        <span>1-Strike Immediate Ban</span>
                    </li>
                    <li>
                        <strong>Time:</strong>
                        <span>{{ now()->format('F j, Y, g:i a') }}</span>
                    </li>
                </ul>
            </div>
            
            <p>
                <strong>This is considered a serious breach of exam integrity.</strong><br>
                Opening other tabs or windows during an exam is strictly prohibited.
            </p>
            
            <div class="actions">
                <a href="{{ route('security.violation-detected', ['subject_id' => request()->get('subject_id')]) }}" class="btn btn-action btn-reactivation">
                    <i class="fas fa-unlock-alt"></i> Request Reactivation
                </a>
                <a href="{{ route('user.dashboard') }}" class="btn btn-action btn-dashboard">
                    <i class="fas fa-home"></i> Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection