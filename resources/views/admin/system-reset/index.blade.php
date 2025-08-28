@extends('layouts.admin')

@section('title', 'System Management')

@section('headerContent')
    <h2><i class="fas fa-cogs"></i> System Management</h2>
    <p>Manage system operations, backups, and maintenance</p>
@endsection

@push('styles')
<!-- Bootstrap 4 CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<!-- FontAwesome -->
<link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">

<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .stats-card.green {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .stats-card.orange {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    .stats-card.red {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    }
    
    .action-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #007bff;
    }
    
    .danger-zone {
        border-left-color: #dc3545;
        background: #fff5f5;
    }
    
    .btn-system {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-system:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .btn-danger-system {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-danger-system:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .system-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['total_students']) }}</h3>
                        <p class="mb-0">Total Students</p>
                    </div>
                    <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['total_exams']) }}</h3>
                        <p class="mb-0">Completed Exams</p>
                    </div>
                    <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card orange">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['active_sessions']) }}</h3>
                        <p class="mb-0">Active Sessions</p>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card red">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['total_questions']) }}</h3>
                        <p class="mb-0">Total Questions</p>
                    </div>
                    <i class="fas fa-question-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Operations -->
        <div class="col-md-6">
            <div class="action-section">
                <h4 class="mb-3">
                    <i class="fas fa-cogs text-primary"></i> 
                    System Operations
                </h4>
                
                <div class="system-info">
                    <h6><i class="fas fa-info-circle text-info"></i> System Information</h6>
                    <ul class="mb-0">
                        <li><strong>PHP Version:</strong> {{ PHP_VERSION }}</li>
                        <li><strong>Laravel Version:</strong> {{ app()->version() }}</li>
                        <li><strong>Environment:</strong> {{ app()->environment() }}</li>
                        <li><strong>Debug Mode:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-system btn-block mb-2" onclick="optimizeSystem()">
                        <i class="fas fa-rocket"></i> Optimize System
                    </button>
                    <button class="btn btn-system btn-block mb-2" onclick="clearCache()">
                        <i class="fas fa-broom"></i> Clear All Caches
                    </button>
                    <button class="btn btn-system btn-block mb-2" onclick="createBackup()">
                        <i class="fas fa-download"></i> Create System Backup
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Backup Management -->
        <div class="col-md-6">
            <div class="action-section">
                <h4 class="mb-3">
                    <i class="fas fa-archive text-success"></i> 
                    Backup Management
                </h4>
                
                <div class="system-info">
                    <h6><i class="fas fa-hdd text-success"></i> Storage Information</h6>
                    <ul class="mb-0">
                        <li><strong>Disk Space:</strong> Available</li>
                        <li><strong>Database Size:</strong> {{ $stats['database_size'] ?? 'Unknown' }}</li>
                        <li><strong>Last Backup:</strong> {{ $stats['last_backup'] ?? 'Never' }}</li>
                        <li><strong>Backup Count:</strong> {{ $stats['backup_count'] ?? 0 }}</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-system btn-block mb-2" onclick="listBackups()">
                        <i class="fas fa-list"></i> View All Backups
                    </button>
                    <button class="btn btn-system btn-block mb-2" onclick="downloadLatestBackup()">
                        <i class="fas fa-download"></i> Download Latest Backup
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="row">
        <div class="col-12">
            <div class="action-section danger-zone">
                <h4 class="mb-3 text-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Danger Zone
                </h4>
                
                <div class="alert alert-danger">
                    <i class="fas fa-skull-crossbones"></i>
                    <strong>Warning:</strong> The actions below are irreversible and will permanently delete data. 
                    Use with extreme caution and ensure you have recent backups.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">
                                    <i class="fas fa-trash-alt"></i> Reset All Exam Data
                                </h5>
                                <p class="card-text">
                                    This will permanently delete all student exam scores, answers, and sessions. 
                                    Questions and user accounts will remain intact.
                                </p>
                                <button class="btn btn-danger-system" onclick="showResetModal()">
                                    <i class="fas fa-nuclear"></i> Reset All Data
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning">
                                    <i class="fas fa-database"></i> Database Maintenance
                                </h5>
                                <p class="card-text">
                                    Perform database optimization, cleanup orphaned records, 
                                    and rebuild indexes for better performance.
                                </p>
                                <button class="btn btn-warning" onclick="performMaintenance()">
                                    <i class="fas fa-wrench"></i> Run Maintenance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirm System Reset
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h5><i class="fas fa-skull-crossbones"></i> EXTREME CAUTION REQUIRED</h5>
                    <p class="mb-0">This action will permanently delete ALL exam data including:</p>
                </div>
                
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Student Exam Scores
                        <span class="badge badge-danger badge-pill">{{ $stats['total_exams'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Student Answers
                        <span class="badge badge-danger badge-pill">{{ $stats['total_answers'] ?? 'Many' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Active Exam Sessions
                        <span class="badge badge-danger badge-pill">{{ $stats['active_sessions'] }}</span>
                    </li>
                </ul>
                
                <div class="form-group">
                    <label for="confirmText">
                        Type <strong>"RESET ALL DATA"</strong> to confirm:
                    </label>
                    <input type="text" class="form-control" id="confirmText" 
                           placeholder="Type exactly: RESET ALL DATA">
                </div>
                
                <div class="form-group">
                    <label for="resetReason">Reason for reset:</label>
                    <textarea class="form-control" id="resetReason" rows="3" 
                              placeholder="Enter the reason for this system reset..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmResetBtn" disabled onclick="executeReset()">
                    <i class="fas fa-nuclear"></i> RESET ALL DATA
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Backup List Modal -->
<div class="modal fade" id="backupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-archive"></i> System Backups
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="backupList">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading backups...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- jQuery (local) -->
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<!-- Bootstrap 4 JS (local) -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Enable reset button only when correct text is entered
    $('#confirmText').on('input', function() {
        const confirmBtn = $('#confirmResetBtn');
        if ($(this).val() === 'RESET ALL DATA') {
            confirmBtn.prop('disabled', false);
        } else {
            confirmBtn.prop('disabled', true);
        }
    });
});

// System Operations
function optimizeSystem() {
    if (!confirm('This will optimize the system and may take a few minutes. Continue?')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.system.optimize") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ ' + response.message);
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Failed to optimize system');
        }
    });
}

function clearCache() {
    $.ajax({
        url: '{{ route("admin.system.optimize") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            action: 'clear_cache'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ All caches cleared successfully');
            } else {
                alert('❌ Failed to clear caches');
            }
        },
        error: function() {
            alert('❌ Failed to clear caches');
        }
    });
}

function createBackup() {
    if (!confirm('Create a new system backup? This may take several minutes.')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.system.backup") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ Backup created successfully');
            } else {
                alert('❌ Failed to create backup');
            }
        },
        error: function() {
            alert('❌ Failed to create backup');
        }
    });
}

function listBackups() {
    $('#backupModal').modal('show');
    
    $.ajax({
        url: '{{ route("admin.system.backups") }}',
        method: 'GET',
        success: function(response) {
            let html = '';
            if (response.backups && response.backups.length > 0) {
                html = '<div class="list-group">';
                response.backups.forEach(function(backup) {
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${backup.name}</h6>
                                <small class="text-muted">Size: ${backup.size} | Created: ${backup.date}</small>
                            </div>
                            <a href="/admin/system/backup/download/${backup.name}" class="btn btn-sm btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html = '<div class="text-center text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><p>No backups found</p></div>';
            }
            $('#backupList').html(html);
        },
        error: function() {
            $('#backupList').html('<div class="alert alert-danger">Failed to load backups</div>');
        }
    });
}

function downloadLatestBackup() {
    window.location.href = '{{ route("admin.system.backups") }}?download=latest';
}

function showResetModal() {
    $('#resetModal').modal('show');
}

function executeReset() {
    const reason = $('#resetReason').val();
    if (!reason.trim()) {
        alert('Please enter a reason for the reset');
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.system.reset") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            reason: reason
        },
        success: function(response) {
            if (response.success) {
                alert('✅ System reset completed successfully');
                $('#resetModal').modal('hide');
                location.reload();
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('❌ Failed to reset system');
        }
    });
}

function performMaintenance() {
    if (!confirm('Perform database maintenance? This may take several minutes.')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.system.optimize") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            action: 'maintenance'
        },
        success: function(response) {
            if (response.success) {
                alert('✅ Database maintenance completed');
            } else {
                alert('❌ Maintenance failed');
            }
        },
        error: function() {
            alert('❌ Failed to perform maintenance');
        }
    });
}
</script>
@endpush