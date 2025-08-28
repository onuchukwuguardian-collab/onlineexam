@extends('layouts.admin')
@extends('layouts.admin_bootstrap')

@section('title', '- Dashboard')

@section('headerContent')
    Admin Dashboard Overview
@endsection

@section('content')
<div class="container-fluid">
    {{-- Welcome Message --}}
    <div class="card bg-primary text-white mb-4">
        <div class="card-body">
            <h2 class="card-title h3 mb-2">Welcome back, {{ Auth::user()->name }}!</h2>
            <p class="card-text opacity-75">Here's an overview of your exam management system.</p>
        </div>
    </div>

    {{-- General Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small mb-1 opacity-75">REGISTERED STUDENTS</p>
                        <h3 class="display-4 mb-0">{{ $totalRegisteredStudents }}</h3>
                    </div>
                    <i class="fas fa-users fa-3x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small mb-1 opacity-75">TOOK EXAMS</p>
                        <h3 class="display-4 mb-0">{{ $totalExamTakers }}</h3>
                    </div>
                    <i class="fas fa-user-check fa-3x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small mb-1 opacity-75">TOTAL SUBJECTS</p>
                        <h3 class="display-4 mb-0">{{ $totalSubjects }}</h3>
                    </div>
                    <i class="fas fa-book-open fa-3x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small mb-1 opacity-75">TOTAL QUESTIONS</p>
                        <h3 class="display-4 mb-0">{{ $totalQuestions }}</h3>
                    </div>
                    <i class="fas fa-question-circle fa-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mb-4">
        <h3 class="h4 mb-3">Quick Actions</h3>
        <div class="row">
            <div class="col-md-3 mb-3">
                <a href="{{ route('admin.users.create') }}" class="card bg-light text-decoration-none h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-user-plus fa-2x text-primary mr-3"></i>
                        <div>
                            <h5 class="card-title mb-1">Add User</h5>
                            <small class="text-muted">Create new student</small>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('admin.subjects.create') }}" class="card bg-light text-decoration-none h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-book-open fa-2x text-success mr-3"></i>
                        <div>
                            <h5 class="card-title mb-1">Add Subject</h5>
                            <small class="text-muted">Create new subject</small>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('admin.security.index') }}" class="card bg-light text-decoration-none h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-users-slash fa-2x text-warning mr-3"></i>
                        <div>
                            <h5 class="card-title mb-1">Bulk Reactivate</h5>
                            <small class="text-muted">Manage banned students</small>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <button onclick="showSystemResetModal()" class="card bg-light text-decoration-none h-100 border-0 w-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-database fa-2x text-danger mr-3"></i>
                        <div>
                            <h5 class="card-title mb-1">System Reset</h5>
                            <small class="text-muted">Clean database</small>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- Performance Overview --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title h5 mb-3">
                        <i class="fas fa-trophy text-warning mr-2"></i>Overall Best Student
                    </h4>
                    @if($overallBestStudent !== 'N/A')
                        <p class="h4 text-primary mb-0">{{ $overallBestStudent }}</p>
                    @else
                        <p class="text-muted mb-0">No scores recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title h5 mb-3">
                        <i class="fas fa-medal text-primary mr-2"></i>Best Per Class
                    </h4>
                    @if(!empty($bestStudentPerClass))
                        @foreach($bestStudentPerClass as $className => $studentInfo)
                            <div class="mb-2 p-2 bg-light rounded">
                                <small class="text-muted d-block">{{ $className }}</small>
                                <strong>{{ $studentInfo }}</strong>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No data yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- System Management --}}
    <div class="card mb-8" style="border-left: 4px solid #ef4444;">
        <h4 class="text-xl mb-4" style="color: #ef4444;"><i class="fas fa-cog"></i> System Management</h4>
        <div class="grid grid-2">
            <div>
                <h5 class="text-lg mb-2">Database Reset</h5>
                <p class="text-sm mb-4" style="color: #6b7280;">
                    Clean all data from the system and start fresh. This will remove all students, questions, subjects, and exam results.
                </p>
                <button onclick="showSystemResetModal()" class="btn btn-danger">
                    <i class="fas fa-database"></i> Reset System
                </button>
            </div>
            <div>
                <h5 class="text-lg mb-2">Backup & Security</h5>
                <p class="text-sm mb-4" style="color: #6b7280;">
                    Create system backups and manage security settings. Always backup before performing system reset.
                </p>
                <button onclick="createSystemBackup()" class="btn btn-warning">
                    <i class="fas fa-download"></i> Create Backup
                </button>
            </div>
        </div>
    </div>

    {{-- System Overview --}}
    <div class="grid grid-2 mb-8">
        <div class="card">
            <h4 class="text-xl mb-4"><i class="fas fa-chart-line" style="color: #10b981;"></i> System Status</h4>
            <div class="mb-2 flex justify-between">
                <span>Database Status</span>
                <span class="btn" style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; font-size: 0.75rem;">Online</span>
            </div>
            <div class="mb-2 flex justify-between">
                <span>Exam System</span>
                <span class="btn" style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; font-size: 0.75rem;">Active</span>
            </div>
            <div class="mb-2 flex justify-between">
                <span>Security</span>
                <span class="btn" style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; font-size: 0.75rem;">Secured</span>
            </div>
        </div>

        <div class="card">
            <h4 class="text-xl mb-4"><i class="fas fa-info-circle" style="color: #3b82f6;"></i> System Info</h4>
            <div class="mb-2">
                <span style="color: #6b7280;">Total Classes:</span>
                <strong>{{ $totalClasses ?? 0 }}</strong>
            </div>
            <div class="mb-2">
                <span style="color: #6b7280;">Admin Users:</span>
                <strong>{{ $totalAdmins ?? 0 }}</strong>
            </div>
            <div class="mb-2">
                <span style="color: #6b7280;">Average Score:</span>
                <strong>{{ number_format($averageScore ?? 0, 1) }}%</strong>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="card">
        <h4 class="text-xl mb-6"><i class="fas fa-chart-bar" style="color: #8b5cf6;"></i> Exam Analytics</h4>
        <div class="grid grid-3">
            <div>
                <h5 class="text-lg mb-4">Performance by Class</h5>
                <canvas id="studentsPerformanceChart" style="max-height: 300px;"></canvas>
            </div>
            <div>
                <h5 class="text-lg mb-4">Questions by Subject</h5>
                <canvas id="subjectPopularityChart" style="max-height: 300px;"></canvas>
            </div>
            <div>
                <h5 class="text-lg mb-4">Performance Distribution</h5>
                <canvas id="performanceDistributionChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        <p class="text-sm text-center mt-4" style="color: #6b7280;">
            Interactive charts showing real-time system analytics and student performance metrics.
        </p>
    </div>

    <!-- System Reset Modal -->
    <div id="systemResetModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> System Reset</h3>
                <button type="button" onclick="closeSystemResetModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-4">
                    <h4><i class="fas fa-warning"></i> DANGER ZONE</h4>
                    <p>This action will permanently delete ALL data from the system including:</p>
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li>All student accounts and exam results</li>
                        <li>All questions and subjects</li>
                        <li>All exam history and scores</li>
                        <li>All uploaded images</li>
                    </ul>
                    <p style="margin-top: 1rem;"><strong>This action cannot be undone!</strong></p>
                </div>

                <div id="resetStep1" class="reset-step">
                    <h4>Step 1: Backup Confirmation</h4>
                    <p>Before proceeding, ensure you have backed up all important data.</p>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="backupConfirm" class="mr-2">
                            I confirm that I have backed up all necessary data
                        </label>
                    </div>
                    <button type="button" onclick="proceedToStep2()" class="btn btn-warning" id="step1Btn" disabled>
                        <i class="fas fa-arrow-right"></i> Proceed to Security Verification
                    </button>
                </div>

                <div id="resetStep2" class="reset-step" style="display: none;">
                    <h4>Step 2: Security Verification</h4>
                    <p>Enter the system reset code from your .env file to confirm this action:</p>
                    <div class="form-group">
                        <label>Reset Code:</label>
                        <input type="password" id="resetCode" class="form-input" placeholder="Enter reset code from .env">
                        <small class="text-sm" style="color: #6b7280;">
                            Look for SYSTEM_RESET_CODE in your .env file
                        </small>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="backToStep1()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" onclick="executeSystemReset()" class="btn btn-danger" id="executeResetBtn">
                            <i class="fas fa-trash-alt"></i> RESET SYSTEM
                        </button>
                    </div>
                </div>

                <div id="resetStep3" class="reset-step" style="display: none;">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-3x mb-3" style="color: #ef4444;"></i>
                        <h4>Resetting System...</h4>
                        <p>Please wait while the system is being reset. Do not close this window.</p>
                        <div class="progress-bar">
                            <div class="progress-fill" id="resetProgress"></div>
                        </div>
                        <p id="resetStatus">Initializing reset process...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: white;
        border-radius: 0.5rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .modal-body {
        padding: 1rem;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
    }
    
    /* Alert Styles */
    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid;
    }
    .alert-danger {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }
    
    /* Progress Bar */
    .progress-bar {
        width: 100%;
        height: 20px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        margin: 1rem 0;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #ef4444, #dc2626);
        width: 0%;
        transition: width 0.3s ease;
    }
    
    /* Form Styles */
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: #374151;
    }
    .form-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Button Styles */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover:not(:disabled) { background: #dc2626; }
    .btn-warning { background: #f59e0b; color: white; }
    .btn-warning:hover:not(:disabled) { background: #d97706; }
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover:not(:disabled) { background: #4b5563; }
    
    /* Grid System */
    .grid { display: grid; gap: 1.5rem; }
    .grid-2 { grid-template-columns: repeat(2, 1fr); }
    .grid-3 { grid-template-columns: repeat(3, 1fr); }
    
    /* Responsive Grid */
    @media (max-width: 1024px) {
        .grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .grid-2, .grid-3 { grid-template-columns: 1fr; }
    }
    
    /* Utility Classes */
    .flex { display: flex; }
    .gap-2 { gap: 0.5rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mr-2 { margin-right: 0.5rem; }
    .text-center { text-align: center; }
    .text-lg { font-size: 1.125rem; }
    .text-sm { font-size: 0.875rem; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart 1: Student Performance
        const ctxStudents = document.getElementById('studentsPerformanceChart');
        if (ctxStudents) {
            new Chart(ctxStudents, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($classLabels ?? []) !!},
                    datasets: [{
                        label: 'Average Score %',
                        data: {!! json_encode($performanceByClass ?? []) !!},
                        backgroundColor: [
                            '#3b82f6',
                            '#10b981', 
                            '#8b5cf6',
                            '#f59e0b',
                            '#ef4444',
                            '#06b6d4',
                            '#84cc16'
                        ],
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 100,
                            grid: { color: '#e5e7eb' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        title: { 
                            display: true, 
                            text: 'Performance by Class Level',
                            font: { size: 14, weight: 'bold' }
                        }
                    }
                }
            });
        }

        // Chart 2: Subject Distribution
        const ctxSubjects = document.getElementById('subjectPopularityChart');
        if (ctxSubjects) {
            new Chart(ctxSubjects, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($subjectLabels ?? []) !!},
                    datasets: [{
                        label: 'Questions Available',
                        data: {!! json_encode($subjectData ?? []) !!},
                        backgroundColor: [
                            '#3b82f6',
                            '#10b981',
                            '#f59e0b', 
                            '#ef4444',
                            '#8b5cf6',
                            '#6b7280',
                            '#06b6d4',
                            '#84cc16',
                            '#f97316',
                            '#ec4899'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true, 
                            position: 'bottom',
                            labels: { 
                                padding: 15,
                                usePointStyle: true,
                                font: { size: 12 }
                            }
                        },
                        title: { 
                            display: true, 
                            text: 'Question Distribution by Subject',
                            font: { size: 14, weight: 'bold' }
                        }
                    }
                }
            });
        }

        // Chart 3: Performance Distribution
        const ctxPerformance = document.getElementById('performanceDistributionChart');
        if (ctxPerformance) {
            new Chart(ctxPerformance, {
                type: 'pie',
                data: {
                    labels: {!! json_encode($performanceLabels ?? []) !!},
                    datasets: [{
                        label: 'Number of Students',
                        data: {!! json_encode($performanceData ?? []) !!},
                        backgroundColor: [
                            '#10b981', // Excellent - Green
                            '#3b82f6', // Good - Blue
                            '#f59e0b', // Average - Orange
                            '#ef4444'  // Poor - Red
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true, 
                            position: 'bottom',
                            labels: { 
                                padding: 10,
                                usePointStyle: true,
                                font: { size: 11 }
                            }
                        },
                        title: { 
                            display: true, 
                            text: 'Student Performance Levels',
                            font: { size: 14, weight: 'bold' }
                        }
                    }
                }
            });
        }
    });

    // System Reset Functions
    function showSystemResetModal() {
        console.log('System reset modal triggered');
        const modal = document.getElementById('systemResetModal');
        if (modal) {
            modal.style.display = 'flex';
            resetToStep1();
        } else {
            console.error('System reset modal not found');
        }
    }

    function closeSystemResetModal() {
        document.getElementById('systemResetModal').style.display = 'none';
        resetToStep1();
    }

    function resetToStep1() {
        document.getElementById('resetStep1').style.display = 'block';
        document.getElementById('resetStep2').style.display = 'none';
        document.getElementById('resetStep3').style.display = 'none';
        document.getElementById('backupConfirm').checked = false;
        document.getElementById('resetCode').value = '';
        document.getElementById('step1Btn').disabled = true;
    }

    // Step 1: Backup confirmation
    document.getElementById('backupConfirm').addEventListener('change', function() {
        document.getElementById('step1Btn').disabled = !this.checked;
    });

    function proceedToStep2() {
        document.getElementById('resetStep1').style.display = 'none';
        document.getElementById('resetStep2').style.display = 'block';
        document.getElementById('resetCode').focus();
    }

    function backToStep1() {
        document.getElementById('resetStep2').style.display = 'none';
        document.getElementById('resetStep1').style.display = 'block';
    }

    function executeSystemReset() {
        const resetCode = document.getElementById('resetCode').value.trim();
        
        if (!resetCode) {
            alert('Please enter the reset code from your .env file');
            return;
        }

        if (!confirm('Are you absolutely sure you want to reset the entire system? This action cannot be undone!')) {
            return;
        }

        // Show progress step
        document.getElementById('resetStep2').style.display = 'none';
        document.getElementById('resetStep3').style.display = 'block';

        // Start reset process
        performSystemReset(resetCode);
    }

    function performSystemReset(resetCode) {
        const progressBar = document.getElementById('resetProgress');
        const statusText = document.getElementById('resetStatus');
        
        // Simulate progress steps
        const steps = [
            { progress: 10, text: 'Verifying reset code...' },
            { progress: 20, text: 'Creating system backup...' },
            { progress: 40, text: 'Clearing exam results...' },
            { progress: 60, text: 'Removing user accounts...' },
            { progress: 80, text: 'Deleting questions and subjects...' },
            { progress: 90, text: 'Cleaning up files...' },
            { progress: 100, text: 'Reset complete!' }
        ];

        let currentStep = 0;

        function updateProgress() {
            if (currentStep < steps.length) {
                const step = steps[currentStep];
                progressBar.style.width = step.progress + '%';
                statusText.textContent = step.text;
                currentStep++;
                
                if (currentStep === 1) {
                    // Actually send the reset request after verification step
                    sendResetRequest(resetCode);
                }
                
                setTimeout(updateProgress, 1000);
            }
        }

        updateProgress();
    }

    function createSystemBackup() {
        if (!confirm('Create a system backup? This may take a few minutes.')) {
            return;
        }

        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('/admin/create-backup', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Backup created successfully: ' + data.filename);
            } else {
                alert('Backup failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Backup failed: Network error');
        });
    }

    function sendResetRequest(resetCode) {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('reset_code', resetCode);

        fetch('/admin/system-reset', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => {
                    alert('System reset completed successfully! You will be redirected to the login page.');
                    window.location.href = '/login';
                }, 2000);
            } else {
                document.getElementById('resetStatus').textContent = 'Reset failed: ' + (data.message || 'Invalid reset code');
                document.getElementById('resetProgress').style.background = '#ef4444';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('resetStatus').textContent = 'Reset failed: Network error';
            document.getElementById('resetProgress').style.background = '#ef4444';
        });
    }
</script>
@endpush
