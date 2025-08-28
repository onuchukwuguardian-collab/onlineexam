@extends('layouts.admin')

@section('title', 'Reactivation Requests')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Reactivation Requests</h1>
    <div class="d-flex">
        <button type="button" class="btn btn-primary mr-2" id="refreshRequestsBtn">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <button type="button" class="btn btn-success" id="bulkApproveBtn" disabled>
            <i class="fas fa-check-circle"></i> Bulk Approve
        </button>
    </div>
</div>
@stop

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-12">
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title">Reactivation Statistics</h3>
            </div>
            <div class="card-body p-0">
                <div class="row no-gutters text-center">
                    <div class="col-md-3 col-6 p-3">
                        <div class="info-box bg-gradient-warning mb-0">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pending</span>
                                <span class="info-box-number">{{ $stats['pending_requests'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 p-3">
                        <div class="info-box bg-gradient-success mb-0">
                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Approved</span>
                                <span class="info-box-number">{{ $stats['approved_requests'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 p-3">
                        <div class="info-box bg-gradient-danger mb-0">
                            <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Rejected</span>
                                <span class="info-box-number">{{ $stats['rejected_requests'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 p-3">
                        <div class="info-box bg-gradient-info mb-0">
                            <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Avg. Response Time</span>
                                <span class="info-box-number">{{ round($stats['avg_response_time']) }} hrs</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Request List -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Reactivation Requests</h3>
                
                <div class="card-tools">
                    <form action="{{ route('admin.security.reactivation-requests') }}" method="GET" class="form-inline">
                        <div class="input-group input-group-sm mr-2" style="width: 150px;">
                            <select name="status" class="form-control float-right">
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="input-group input-group-sm mr-2" style="width: 150px;">
                            <select name="subject" class="form-control float-right">
                                <option value="">All Subjects</option>
                                @foreach($subjects as $subj)
                                <option value="{{ $subj->id }}" {{ request('subject') == $subj->id ? 'selected' : '' }}>{{ $subj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <input type="text" name="search" class="form-control float-right" placeholder="Search" value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 2%">
                                    <div class="icheck-primary d-inline">
                                        <input type="checkbox" id="checkAll">
                                        <label for="checkAll"></label>
                                    </div>
                                </th>
                                <th style="width: 15%">Student</th>
                                <th style="width: 12%">Subject</th>
                                <th style="width: 25%">Request Message</th>
                                <th style="width: 10%">Violations</th>
                                <th style="width: 10%">Requested</th>
                                <th style="width: 10%">Status</th>
                                <th style="width: 16%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $request)
                            <tr class="{{ $request->status === 'pending' ? 'request-row-pending' : '' }}">
                                <td>
                                    @if($request->status === 'pending')
                                    <div class="icheck-primary d-inline">
                                        <input type="checkbox" class="request-checkbox" id="check{{ $request->id }}" data-id="{{ $request->id }}">
                                        <label for="check{{ $request->id }}"></label>
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $request->user->name }}</strong>
                                    </div>
                                    <small>{{ $request->user->email }}</small><br>
                                    <small class="text-muted">Reg: {{ $request->user->registration_number }}</small>
                                </td>
                                <td>{{ $request->subject->name }}</td>
                                <td>
                                    <div style="max-height: 60px; overflow: hidden;">
                                        {{ \Illuminate\Support\Str::limit($request->request_message, 80) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-danger">{{ $request->ban_count }} Ban(s)</span>
                                </td>
                                <td>{{ $request->requested_at->diffForHumans() }}</td>
                                <td>
                                    @if($request->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($request->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($request->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.security.reactivation-requests.show', $request->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    @if($request->status === 'pending')
                                    <button type="button" class="btn btn-sm btn-success approve-btn" data-id="{{ $request->id }}">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger reject-btn" data-id="{{ $request->id }}">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No reactivation requests found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer clearfix">
                {{ $requests->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">Approve Reactivation Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <input type="hidden" name="request_id" id="approveRequestId">
                <div class="modal-body">
                    <p>Are you sure you want to approve this student's reactivation request?</p>
                    <p><strong>This will:</strong></p>
                    <ul>
                        <li>Remove the ban from this student for this subject</li>
                        <li>Allow the student to take the exam again</li>
                        <li>Clear violation history for this subject</li>
                    </ul>
                    
                    <div class="form-group">
                        <label for="approveResponse">Response to Student (Optional)</label>
                        <textarea class="form-control" id="approveResponse" name="admin_response" rows="3" placeholder="Your response will be visible to the student">Request approved. You can now retake the exam.</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Reject Reactivation Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <input type="hidden" name="request_id" id="rejectRequestId">
                <div class="modal-body">
                    <p>Are you sure you want to reject this student's reactivation request?</p>
                    <p><strong>Note:</strong> The student will remain banned from this subject.</p>
                    
                    <div class="form-group">
                        <label for="rejectResponse">Reason for Rejection (Required)</label>
                        <textarea class="form-control" id="rejectResponse" name="admin_response" rows="3" placeholder="Explain why you're rejecting this request" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Approve Modal -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">Bulk Approve Requests</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="bulkApproveForm" action="{{ route('admin.security.reactivation-requests.bulk-approve') }}" method="POST">
                @csrf
                <div id="bulkApproveIds"></div>
                <div class="modal-body">
                    <p>Are you sure you want to approve <span id="bulkApproveCount">0</span> reactivation requests?</p>
                    
                    <div class="form-group">
                        <label for="bulkApproveResponse">Response to Students (Optional)</label>
                        <textarea class="form-control" id="bulkApproveResponse" name="admin_response" rows="3" placeholder="Your response will be visible to all selected students">Your reactivation request has been approved. You can now retake the exam.</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve All Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Handle check all
        $('#checkAll').change(function() {
            $('.request-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkApproveButton();
        });
        
        // Update bulk approve button state when individual checkboxes change
        $('.request-checkbox').change(function() {
            updateBulkApproveButton();
        });
        
        // Approve button click
        $('.approve-btn').click(function() {
            const requestId = $(this).data('id');
            $('#approveRequestId').val(requestId);
            $('#approveForm').attr('action', `/admin/security/reactivation-requests/${requestId}/approve`);
            $('#approveModal').modal('show');
        });
        
        // Reject button click
        $('.reject-btn').click(function() {
            const requestId = $(this).data('id');
            $('#rejectRequestId').val(requestId);
            $('#rejectForm').attr('action', `/admin/security/reactivation-requests/${requestId}/reject`);
            $('#rejectModal').modal('show');
        });
        
        // Bulk approve button click
        $('#bulkApproveBtn').click(function() {
            const selectedIds = [];
            $('.request-checkbox:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });
            
            $('#bulkApproveIds').empty();
            selectedIds.forEach(id => {
                $('#bulkApproveIds').append(`<input type="hidden" name="request_ids[]" value="${id}">`);
            });
            
            $('#bulkApproveCount').text(selectedIds.length);
            $('#bulkApproveModal').modal('show');
        });
        
        // Handle form submission with AJAX
        $('#approveForm').submit(function(e) {
            e.preventDefault();
            submitForm($(this), 'approve');
        });
        
        $('#rejectForm').submit(function(e) {
            e.preventDefault();
            submitForm($(this), 'reject');
        });
        
        // Update bulk approve button
        function updateBulkApproveButton() {
            const checkedCount = $('.request-checkbox:checked').length;
            $('#bulkApproveBtn').prop('disabled', checkedCount === 0);
        }
        
        // Submit form with AJAX
        function submitForm(form, action) {
            const url = form.attr('action');
            const formData = form.serialize();
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    // Disable submit button and show loading state
                    form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Something went wrong.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    Swal.fire({
                        title: 'Error!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    // Re-enable submit button
                    form.find('button[type="submit"]').prop('disabled', false);
                    if (action === 'approve') {
                        form.find('button[type="submit"]').html('Approve Request');
                    } else {
                        form.find('button[type="submit"]').html('Reject Request');
                    }
                }
            });
        }
        
        // Refresh button
        $('#refreshRequestsBtn').click(function() {
            window.location.reload();
        });
    });
</script>
@stop

@section('css')
<style>
    .request-row-pending {
        background-color: rgba(255, 243, 205, 0.2);
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
    }
</style>
@stop
