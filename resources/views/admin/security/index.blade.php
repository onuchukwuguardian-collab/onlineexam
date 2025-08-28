@extends('layouts.admin_bootstrap')

@section('title', 'Security Violations')

@section('headerContent')
    <h2><i class="fas fa-shield-alt"></i> Security Violations Management</h2>
    <p>Monitor exam security violations and manage student access</p>
@endsection

@push('styles')
<!-- Bootstrap 4 CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<!-- DataTables Bootstrap 4 CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/buttons.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/responsive.bootstrap4.min.css') }}">
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
    
    .stats-card.card-yellow {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    .stats-card.card-red {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    .stats-card.card-green {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .violation-type-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #007bff;
    }
    
    .quick-action {
        display: block;
        width: 100%;
        padding: 15px;
        margin-bottom: 10px;
        border: none;
        border-radius: 8px;
        text-align: left;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .quick-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        text-decoration: none;
        color: inherit;
    }
    
    .quick-action-blue {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .quick-action-yellow {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }
    
    .quick-action-green {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    
    .tab-content {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .nav-tabs .nav-link.active {
        border-bottom: 2px solid #007bff;
        color: #007bff;
        background-color: transparent;
        border-top: none;
        border-left: none;
        border-right: none;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #6c757d;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #007bff;
    }
    
    .modal {
        z-index: 1050;
    }
    
    .violation-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .violation-tab-switch {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .violation-right-click {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .violation-copy-attempt {
        background-color: #f8d7da;
        color: #721c24;
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
                        <h3 class="mb-0">{{ number_format($stats['total_violations']) }}</h3>
                        <p class="mb-0 opacity-75">Total Violations</p>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card card-yellow">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['violations_today']) }}</h3>
                        <p class="mb-0 opacity-75">Today's Violations</p>
                    </div>
                    <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card card-red">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['active_bans']) }}</h3>
                        <p class="mb-0 opacity-75">Active Bans</p>
                    </div>
                    <i class="fas fa-ban fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card card-green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ number_format($stats['unique_violators']) }}</h3>
                        <p class="mb-0 opacity-75">Unique Violators</p>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Violation Types Breakdown and Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="violation-type-card">
                <h4 class="mb-3"><i class="fas fa-chart-pie"></i> Violation Types</h4>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: #fff3cd;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-external-link-alt text-warning mr-3"></i>
                            <span class="font-weight-medium">Tab Switches</span>
                        </div>
                        <span class="font-weight-bold text-warning">{{ number_format($stats['tab_switches']) }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: #f8d7da;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-mouse-pointer text-danger mr-3"></i>
                            <span class="font-weight-medium">Right Clicks</span>
                        </div>
                        <span class="font-weight-bold text-danger">{{ number_format($stats['right_clicks']) }}</span>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: #f8d7da;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-copy text-danger mr-3"></i>
                            <span class="font-weight-medium">Copy Attempts</span>
                        </div>
                        <span class="font-weight-bold text-danger">{{ number_format($stats['copy_attempts']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="violation-type-card">
                <h4 class="mb-3"><i class="fas fa-tools"></i> Quick Actions</h4>
                <button onclick="exportReport('csv')" class="quick-action quick-action-blue">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-download mr-3"></i>
                        <div>
                            <div class="font-weight-medium">Export Report</div>
                            <div class="small opacity-75">Download violations as CSV</div>
                        </div>
                    </div>
                </button>
                <button onclick="showClearModal()" class="quick-action quick-action-yellow">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-broom mr-3"></i>
                        <div>
                            <div class="font-weight-medium">Clear Old Data</div>
                            <div class="small opacity-75">Remove old violation records</div>
                        </div>
                    </div>
                </button>
                <button onclick="refreshData()" class="quick-action quick-action-green">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-sync-alt mr-3"></i>
                        <div>
                            <div class="font-weight-medium">Refresh Data</div>
                            <div class="small opacity-75">Update statistics and tables</div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs for Different Views -->
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="securityTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="violations-tab" data-toggle="tab" href="#violations" role="tab" aria-controls="violations" aria-selected="true">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Recent Violations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="banned-tab" data-toggle="tab" href="#banned" role="tab" aria-controls="banned" aria-selected="false">
                        <i class="fas fa-ban mr-2"></i>Banned Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="reactivation-tab" data-toggle="tab" href="#reactivation" role="tab" aria-controls="reactivation" aria-selected="false">
                        <i class="fas fa-paper-plane mr-2"></i>Reactivation Requests
                        @php
                            $pendingCount = \App\Models\ReactivationRequest::where('status', 'pending')->count();
                        @endphp
                        @if($pendingCount > 0)
                            <span class="badge badge-warning ml-1">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            
            <div class="tab-content" id="securityTabsContent">
    
                <!-- Violations Tab -->
                <div class="tab-pane fade show active" id="violations" role="tabpanel" aria-labelledby="violations-tab">
                    <div class="table-responsive">
                        <table id="violationsTable" class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Violation</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($violations as $violation)
                                <tr>
                                    <td>
                                        <div class="small">
                                            <div class="font-weight-medium">{{ $violation->occurred_at->format('M d, Y') }}</div>
                                            <div class="text-muted">{{ $violation->occurred_at->format('H:i:s') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="font-weight-medium">{{ $violation->user->name ?? 'Unknown' }}</div>
                                            <div class="text-muted">{{ $violation->user->email ?? 'N/A' }}</div>
                                            @if($violation->user->registration_number)
                                            <div class="text-info">Reg: {{ $violation->user->registration_number }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $violation->subject->name ?? 'Unknown' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($violation->violation_type) {
                                                'tab_switch' => 'badge-danger',
                                                'right_click' => 'badge-warning',
                                                'copy_attempt' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $violation->violation_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $isBanned = \App\Models\ExamBan::where('user_id', $violation->user_id)
                                                ->where('subject_id', $violation->subject_id)
                                                ->where('is_active', true)
                                                ->exists();
                                        @endphp
                                        @if($isBanned)
                                            <span class="badge badge-danger">
                                                <i class="fas fa-ban mr-1"></i>Banned
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                <i class="fas fa-check mr-1"></i>Active
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary view-violation-btn" 
                                                    data-violation-id="{{ $violation->id }}" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if(!$isBanned)
                                            <button class="btn btn-sm btn-outline-danger ban-student-btn" 
                                                    data-user-id="{{ $violation->user_id }}" 
                                                    data-subject-id="{{ $violation->subject_id }}" 
                                                    data-student-name="{{ $violation->user->name }}" 
                                                    data-subject-name="{{ $violation->subject->name }}" 
                                                    title="Ban Student">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-shield-alt fa-3x mb-3 text-muted"></i>
                                            <h5 class="font-weight-medium">No violations found</h5>
                                            <p class="mb-0">All students are following exam rules</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($violations->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Showing {{ $violations->firstItem() }} to {{ $violations->lastItem() }} of {{ $violations->total() }} violations
                        </div>
                        {{ $violations->links() }}
                    </div>
                    @endif
                </div>
    
                <!-- Banned Students Tab -->
                <div class="tab-pane fade" id="banned" role="tabpanel" aria-labelledby="banned-tab">
                    <div class="table-responsive">
                        <table id="bannedStudentsTable" class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Banned Date</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bannedStudents as $ban)
                                <tr>
                                    <td>
                                        <div class="small">
                                            <div class="font-weight-medium">{{ $ban->user->name ?? 'Unknown' }}</div>
                                            <div class="text-muted">{{ $ban->user->email ?? 'N/A' }}</div>
                                            @if($ban->user->registration_number)
                                            <div class="text-info">Reg: {{ $ban->user->registration_number }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $ban->subject->name ?? 'Unknown' }}</span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="font-weight-medium">{{ $ban->banned_at->format('M d, Y') }}</div>
                                            <div class="text-muted">{{ $ban->banned_at->format('H:i:s') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted small" style="max-width: 200px;" title="{{ $ban->ban_reason }}">
                                            {{ Str::limit($ban->ban_reason, 50) }}
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success unban-btn" 
                                                data-ban-id="{{ $ban->id }}" 
                                                data-student-name="{{ $ban->user->name }}" 
                                                data-subject-name="{{ $ban->subject->name }}" 
                                                title="Unban Student">
                                            <i class="fas fa-undo mr-1"></i> Unban
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-user-check fa-3x mb-3 text-success"></i>
                                            <h5 class="font-weight-medium">No banned students</h5>
                                            <p class="mb-0">All students have access to exams</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($bannedStudents->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Showing {{ $bannedStudents->firstItem() }} to {{ $bannedStudents->lastItem() }} of {{ $bannedStudents->total() }} banned students
                        </div>
                        {{ $bannedStudents->links() }}
                    </div>
                    @endif
                </div>

                <!-- Reactivation Requests Tab -->
                <div class="tab-pane fade" id="reactivation" role="tabpanel" aria-labelledby="reactivation-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-paper-plane text-primary mr-2"></i>
                            Student Reactivation Requests
                        </h5>
                        <a href="{{ route('admin.security.reactivation-requests') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>
                            Manage All Requests
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="reactivationTable" class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $reactivationRequests = \App\Models\ReactivationRequest::with(['user', 'subject'])
                                        ->where('status', 'pending')
                                        ->orderBy('requested_at', 'desc')
                                        ->take(10)
                                        ->get();
                                @endphp
                                @forelse($reactivationRequests as $request)
                                <tr>
                                    <td>
                                        <div class="small">
                                            <div class="font-weight-medium">{{ $request->user->name ?? 'Unknown' }}</div>
                                            <div class="text-muted">{{ $request->user->email ?? 'N/A' }}</div>
                                            @if($request->user->registration_number)
                                            <div class="text-info">Reg: {{ $request->user->registration_number }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $request->subject->name ?? 'Unknown' }}</span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="font-weight-medium">{{ $request->requested_at->format('M d, Y') }}</div>
                                            <div class="text-muted">{{ $request->requested_at->format('H:i:s') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock mr-1"></i>Pending Review
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.security.reactivation-requests.show', $request) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Review Request">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-success approve-request-btn" 
                                                    data-request-id="{{ $request->id }}"
                                                    data-student-name="{{ $request->user->name }}"
                                                    data-subject-name="{{ $request->subject->name }}"
                                                    title="Quick Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger reject-request-btn" 
                                                    data-request-id="{{ $request->id }}"
                                                    data-student-name="{{ $request->user->name }}"
                                                    data-subject-name="{{ $request->subject->name }}"
                                                    title="Quick Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                            <h5 class="font-weight-medium">No Pending Requests</h5>
                                            <p class="mb-0">All reactivation requests have been processed</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($reactivationRequests->count() >= 10)
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.security.reactivation-requests') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list mr-2"></i>View All Reactivation Requests
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ban Student Modal -->
<div class="modal fade" id="banModal" tabindex="-1" role="dialog" aria-labelledby="banModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="banModalLabel">
                    <i class="fas fa-ban text-danger mr-2"></i>Ban Student
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="banForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="banUserId" name="user_id">
                    <input type="hidden" id="banSubjectId" name="subject_id">
                    
                    <div class="form-group">
                        <label class="font-weight-medium">Student:</label>
                        <div id="banStudentName" class="text-muted"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-medium">Subject:</label>
                        <div id="banSubjectName" class="text-muted"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="banReason" class="font-weight-medium">Reason for Ban:</label>
                        <textarea id="banReason" name="reason" rows="3" class="form-control" 
                                  placeholder="Enter the reason for banning this student..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban mr-2"></i>Ban Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unban Student Modal -->
<div class="modal fade" id="unbanModal" tabindex="-1" role="dialog" aria-labelledby="unbanModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unbanModalLabel">
                    <i class="fas fa-undo text-success mr-2"></i>Unban Student
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="unbanForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="unbanId" name="ban_id">
                    
                    <div class="form-group">
                        <label class="font-weight-medium">Student:</label>
                        <div id="unbanStudentName" class="text-muted"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-medium">Subject:</label>
                        <div id="unbanSubjectName" class="text-muted"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="unbanReason" class="font-weight-medium">Reason for Unban:</label>
                        <textarea id="unbanReason" name="reason" rows="3" class="form-control" 
                                  placeholder="Enter the reason for unbanning this student..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-undo mr-2"></i>Unban Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear Data Modal -->
<div class="modal fade" id="clearModal" tabindex="-1" role="dialog" aria-labelledby="clearModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearModalLabel">
                    <i class="fas fa-broom text-warning mr-2"></i>Clear Old Violations
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="clearForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="clearDays" class="font-weight-medium">Clear violations older than:</label>
                        <select id="clearDays" name="days" class="form-control">
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                            <option value="180">6 months</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                            <div class="small">
                                This action cannot be undone. Old violation records will be permanently deleted.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-broom mr-2"></i>Clear Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<!-- jQuery (Local) -->
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<!-- Bootstrap 4 JS (Local) -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<!-- DataTables -->
<script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/dataTables.bootstrap4.min.js') }}"></script>

<script>
// Define functions in global scope first
// View violation details
function viewViolation(violationId) {
    // Implementation for viewing violation details
    alert('View violation details for ID: ' + violationId);
}

// Ban student function
function banStudent(userId, subjectId, studentName, subjectName) {
    $('#banUserId').val(userId);
    $('#banSubjectId').val(subjectId);
    $('#banStudentName').text(studentName);
    $('#banSubjectName').text(subjectName);
    $('#banReason').val('');
    $('#banModal').modal('show');
}

// Unban student function
function unbanStudent(banId, studentName, subjectName) {
    console.log('unbanStudent called with:', banId, studentName, subjectName);
    
    $('#unbanId').val(banId);
    $('#unbanStudentName').text(studentName);
    $('#unbanSubjectName').text(subjectName);
    $('#unbanReason').val('');
    $('#unbanModal').modal('show');
}

// Show clear data modal
function showClearModal() {
    $('#clearModal').modal('show');
}

// Export report function
function exportReport(format) {
    window.location.href = '/admin/security/export?format=' + format;
}

// Refresh data function
function refreshData() {
    location.reload();
}

// Show alert function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert to the top of the container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// Initialize DataTables function
function initializeDataTables() {
    // Initialize DataTables for violations table (6 columns: Date/Time, Student, Subject, Type, Description, Actions)
    if ($('#violationsTable').length) {
        try {
            // Check if table has data rows (excluding empty state)
            const hasDataRows = $('#violationsTable tbody tr:not(:has(td[colspan]))').length > 0;
            
            if (hasDataRows) {
                $('#violationsTable').DataTable({
                    responsive: true,
                    order: [[0, 'desc']], // Sort by Date/Time column (1st column, 0-indexed)
                    pageLength: 10,
                    destroy: true, // Allow re-initialization
                    columnDefs: [
                        { orderable: false, targets: [5] }, // Disable sorting for Actions column (6th column)
                        { className: 'text-center', targets: [5] } // Center align Actions column
                    ],
                    language: {
                        search: "Search violations:",
                        lengthMenu: "Show _MENU_ violations",
                        info: "Showing _START_ to _END_ of _TOTAL_ violations",
                        emptyTable: "No violations found",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
                console.log('✅ Violations DataTable initialized successfully');
            } else {
                console.log('ℹ️ Violations table is empty, skipping DataTable initialization');
            }
        } catch (error) {
            console.error('❌ Failed to initialize violations DataTable:', error);
            console.error('Table HTML:', $('#violationsTable')[0]?.outerHTML?.substring(0, 500));
        }
    } else {
        console.warn('⚠️ Violations table element not found');
    }
    
    // Initialize DataTables for banned students table (5 columns: Student, Subject, Banned Date, Reason, Actions)
    if ($('#bannedStudentsTable').length) {
        try {
            // Check if table has data rows (excluding empty state)
            const hasDataRows = $('#bannedStudentsTable tbody tr:not(:has(td[colspan]))').length > 0;
            
            if (hasDataRows) {
                $('#bannedStudentsTable').DataTable({
                    responsive: true,
                    order: [[2, 'desc']], // Sort by Banned Date column (3rd column, 0-indexed)
                    pageLength: 10,
                    destroy: true, // Allow re-initialization
                    columnDefs: [
                        { orderable: false, targets: [4] }, // Disable sorting for Actions column (5th column)
                        { className: 'text-center', targets: [4] } // Center align Actions column
                    ],
                    language: {
                        search: "Search banned students:",
                        lengthMenu: "Show _MENU_ students",
                        info: "Showing _START_ to _END_ of _TOTAL_ students",
                        emptyTable: "No banned students found",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
                console.log('✅ Banned students DataTable initialized successfully');
            } else {
                console.log('ℹ️ Banned students table is empty, skipping DataTable initialization');
            }
        } catch (error) {
            console.error('❌ Failed to initialize banned students DataTable:', error);
            console.error('Table HTML:', $('#bannedStudentsTable')[0]?.outerHTML?.substring(0, 500));
        }
    } else {
        console.warn('⚠️ Banned students table element not found');
    }
    
    // Initialize DataTables for reactivation requests table (5 columns: Student, Subject, Request Date, Status, Actions)
    if ($('#reactivationTable').length) {
        try {
            // Check if table has data rows (excluding empty state)
            const hasDataRows = $('#reactivationTable tbody tr:not(:has(td[colspan]))').length > 0;
            
            if (hasDataRows) {
                $('#reactivationTable').DataTable({
                    responsive: true,
                    order: [[2, 'desc']], // Sort by Request Date column (3rd column, 0-indexed)
                    pageLength: 10,
                    destroy: true, // Allow re-initialization
                    columnDefs: [
                        { orderable: false, targets: [4] }, // Disable sorting for Actions column (5th column)
                        { className: 'text-center', targets: [3, 4] } // Center align Status and Actions columns
                    ],
                    language: {
                        search: "Search reactivation requests:",
                        lengthMenu: "Show _MENU_ requests",
                        info: "Showing _START_ to _END_ of _TOTAL_ requests",
                        emptyTable: "No reactivation requests found",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
                console.log('✅ Reactivation requests DataTable initialized successfully');
            } else {
                console.log('ℹ️ Reactivation requests table is empty, skipping DataTable initialization');
            }
        } catch (error) {
            console.error('❌ Failed to initialize reactivation requests DataTable:', error);
            console.error('Table HTML:', $('#reactivationTable')[0]?.outerHTML?.substring(0, 500));
        }
    } else {
        console.warn('⚠️ Reactivation requests table element not found');
    }
}

// Document ready function - single consolidated version
$(document).ready(function() {
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        xhrFields: {
            withCredentials: true
        }
    });
    
    // Delay DataTables initialization to ensure DOM is fully ready
    setTimeout(function() {
        initializeDataTables();
    }, 300);
    
    // Add event handler for unban buttons
    $(document).on('click', '.unban-btn', function() {
        console.log('Unban button clicked');
        
        const banId = $(this).data('ban-id');
        const studentName = $(this).data('student-name');
        const subjectName = $(this).data('subject-name');
        
        console.log('Data extracted:', banId, studentName, subjectName);
        
        // Verify the function exists
        if (typeof unbanStudent === 'function') {
            unbanStudent(banId, studentName, subjectName);
        } else {
            console.error('unbanStudent function not found!');
            alert('Error: unbanStudent function not found!');
        }
    });
    
    // Add event handler for view violation buttons
    $(document).on('click', '.view-violation-btn', function() {
        const violationId = $(this).data('violation-id');
        if (typeof viewViolation === 'function') {
            viewViolation(violationId);
        } else {
            console.error('viewViolation function not found!');
            alert('Error: viewViolation function not found!');
        }
    });
    
    // Add event handler for ban student buttons
    $(document).on('click', '.ban-student-btn', function() {
        const userId = $(this).data('user-id');
        const subjectId = $(this).data('subject-id');
        const studentName = $(this).data('student-name');
        const subjectName = $(this).data('subject-name');
        
        if (typeof banStudent === 'function') {
            banStudent(userId, subjectId, studentName, subjectName);
        } else {
            console.error('banStudent function not found!');
            alert('Error: banStudent function not found!');
        }
    });
    
    // Add event handler for approve reactivation request buttons
    $(document).on('click', '.approve-request-btn', function() {
        const requestId = $(this).data('request-id');
        const studentName = $(this).data('student-name');
        const subjectName = $(this).data('subject-name');
        
        if (confirm(`Are you sure you want to approve the reactivation request for ${studentName} in ${subjectName}?`)) {
            approveReactivationRequest(requestId, 'Quick approval from security dashboard');
        }
    });
    
    // Add event handler for reject reactivation request buttons
    $(document).on('click', '.reject-request-btn', function() {
        const requestId = $(this).data('request-id');
        const studentName = $(this).data('student-name');
        const subjectName = $(this).data('subject-name');
        
        const reason = prompt(`Enter rejection reason for ${studentName}'s reactivation request for ${subjectName}:`);
        if (reason && reason.trim() !== '') {
            rejectReactivationRequest(requestId, reason.trim());
        }
    });
    
    // Handle ban form submission
    $('#banForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            user_id: $('#banUserId').val(),
            subject_id: $('#banSubjectId').val(),
            reason: $('#banReason').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '/admin/security/ban',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#banModal').modal('hide');
                    showAlert('success', response.message);
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Failed to ban student. Please try again.');
            }
        });
    });
    
    // Handle unban form submission
    $('#unbanForm').submit(function(e) {
        e.preventDefault();
        
        const banId = $('#unbanId').val();
        const formData = {
            reason: $('#unbanReason').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '/admin/security/bans/' + banId + '/unban',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#unbanModal').modal('hide');
                    showAlert('success', response.message);
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Failed to unban student. Please try again.');
            }
        });
    });
    
    // Handle clear data form submission
    $('#clearForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            days: $('#clearDays').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        if (confirm('Are you sure you want to clear old violation data? This action cannot be undone.')) {
            $.ajax({
                url: '/admin/security/clear',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#clearModal').modal('hide');
                        showAlert('success', response.message);
                        location.reload();
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert('error', response?.message || 'Failed to clear data. Please try again.');
                }
            });
        }
    });
});

// Approve reactivation request function
function approveReactivationRequest(requestId, adminResponse) {
    $.ajax({
        url: `/admin/security/reactivation-requests/${requestId}/approve`,
        method: 'POST',
        data: {
            admin_response: adminResponse,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response?.message || 'Failed to approve reactivation request. Please try again.');
        }
    });
}

// Reject reactivation request function
function rejectReactivationRequest(requestId, adminResponse) {
    $.ajax({
        url: `/admin/security/reactivation-requests/${requestId}/reject`,
        method: 'POST',
        data: {
            admin_response: adminResponse,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert('error', response?.message || 'Failed to reject reactivation request. Please try again.');
        }
    });
}
</script>
@endpush
