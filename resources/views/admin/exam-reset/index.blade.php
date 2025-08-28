@extends('layouts.admin_bootstrap')

@section('title', 'Exam Reset')

@section('headerContent')
    <h2><i class="fas fa-redo-alt"></i> Exam Reset Management</h2>
    <p>Reset student exam progress by registration number, class, and subject</p>
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
    
    .stats-card.green {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .stats-card.orange {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    .stats-card.red {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    }
    
    .form-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #007bff;
    }
    
    .recent-resets {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .btn-reset {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .btn-bulk-reset {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-bulk-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        border-left: 4px solid #28a745;
    }
    
    .exam-badge {
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        margin-right: 5px;
        margin-bottom: 5px;
        display: inline-block;
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
                        <h3 class="mb-0">{{ $stats['total_resets_today'] }}</h3>
                        <p class="mb-0">Resets Today</p>
                    </div>
                    <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['total_resets_week'] }}</h3>
                        <p class="mb-0">Resets This Week</p>
                    </div>
                    <i class="fas fa-calendar-week fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card orange">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['total_students'] }}</h3>
                        <p class="mb-0">Total Students</p>
                    </div>
                    <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card red">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['total_subjects'] }}</h3>
                        <p class="mb-0">Total Subjects</p>
                    </div>
                    <i class="fas fa-book fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Individual Student Reset -->
        <div class="col-md-6">
            <div class="form-section">
                <h4 class="mb-3">
                    <i class="fas fa-user text-primary"></i> 
                    Reset Individual Student
                </h4>
                
                <form id="resetStudentForm">
                    @csrf
                    <div class="form-group">
                        <label for="search_type">
                            <i class="fas fa-search"></i> Search By
                        </label>
                        <select class="form-control" id="search_type" name="search_type" required>
                            <option value="registration">Registration Number</option>
                            <option value="email">Email Address</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="registration_group">
                        <label for="registration_number">
                            <i class="fas fa-id-card"></i> Registration Number
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="registration_number" 
                               name="registration_number" 
                               placeholder="Enter student registration number">
                    </div>
                    
                    <div class="form-group" id="email_group" style="display: none;">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Enter student email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="class_id">
                            <i class="fas fa-users"></i> Class
                        </label>
                        <select class="form-control" id="class_id" name="class_id" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject_id">
                            <i class="fas fa-book"></i> Subject
                        </label>
                        <select class="form-control" id="subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-info btn-sm" onclick="searchStudent()">
                            <i class="fas fa-search"></i> Search Student
                        </button>
                    </div>
                    
                    <!-- Student Info Display -->
                    <div id="studentInfo" style="display: none;"></div>
                    
                    <div class="form-group">
                        <label for="reason">
                            <i class="fas fa-comment"></i> Reason for Reset
                        </label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Enter reason for resetting this exam"
                                  required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-reset btn-block">
                        <i class="fas fa-redo-alt"></i> Reset Student Exam
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Bulk Reset -->
        <div class="col-md-6">
            <div class="form-section">
                <h4 class="mb-3">
                    <i class="fas fa-users text-warning"></i> 
                    Bulk Reset (Entire Class)
                </h4>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This will reset the selected subject for ALL students in the selected class.
                </div>
                
                <form id="bulkResetForm">
                    @csrf
                    <div class="form-group">
                        <label for="bulk_class_id">
                            <i class="fas fa-users"></i> Class
                        </label>
                        <select class="form-control" id="bulk_class_id" name="class_id" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_subject_id">
                            <i class="fas fa-book"></i> Subject
                        </label>
                        <select class="form-control" id="bulk_subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_reason">
                            <i class="fas fa-comment"></i> Reason for Bulk Reset
                        </label>
                        <textarea class="form-control" 
                                  id="bulk_reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Enter reason for bulk reset (will affect entire class)"
                                  required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-bulk-reset btn-block">
                        <i class="fas fa-users"></i> Reset Entire Class
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Recent Resets -->
    <div class="row">
        <div class="col-12">
            <div class="recent-resets">
                <h4 class="mb-3">
                    <i class="fas fa-history text-info"></i> 
                    Recent Resets
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="resetsTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>Date/Time</th>
                                <th>Student</th>
                                <th>Registration</th>
                                <th>Subject</th>
                                <th>Reset By</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentResets as $reset)
                            <tr>
                                <td>{{ $reset->reset_time->format('M d, Y H:i') }}</td>
                                <td>
                                    <strong>{{ $reset->user->name }}</strong><br>
                                    <small class="text-muted">{{ $reset->user->email }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $reset->user->registration_number }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $reset->subject->name }}</span>
                                </td>
                                <td>{{ $reset->resetByAdmin->name ?? 'System' }}</td>
                                <td>
                                    <small>{{ Str::limit($reset->reason, 50) }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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
    
    // Toggle search type fields
    $('#search_type').change(function() {
        const searchType = $(this).val();
        if (searchType === 'registration') {
            $('#registration_group').show();
            $('#email_group').hide();
            $('#registration_number').prop('required', true);
            $('#email').prop('required', false);
        } else if (searchType === 'email') {
            $('#registration_group').hide();
            $('#email_group').show();
            $('#registration_number').prop('required', false);
            $('#email').prop('required', true);
        }
    });
    
    // Initialize DataTable
    $('#resetsTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            search: "Search resets:",
            lengthMenu: "Show _MENU_ resets per page",
            info: "Showing _START_ to _END_ of _TOTAL_ resets",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
    
    // Load subjects when class is selected (Individual Reset)
    $('#class_id').change(function() {
        const classId = $(this).val();
        const subjectSelect = $('#subject_id');
        
        subjectSelect.html('<option value="">Loading...</option>');
        
        if (classId) {
            $.ajax({
                url: `/admin/exam-reset/subjects/${classId}`,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(subjects) {
                    subjectSelect.html('<option value="">Select Subject</option>');
                    subjects.forEach(function(subject) {
                        subjectSelect.append(`<option value="${subject.id}">${subject.name}</option>`);
                    });
                },
                error: function(xhr) {
                    console.error('Error loading subjects:', xhr);
                    subjectSelect.html('<option value="">Error loading subjects</option>');
                }
            });
        } else {
            subjectSelect.html('<option value="">Select Subject</option>');
        }
    });
    
    // Load subjects when class is selected (Bulk Reset)
    $('#bulk_class_id').change(function() {
        const classId = $(this).val();
        const subjectSelect = $('#bulk_subject_id');
        
        subjectSelect.html('<option value="">Loading...</option>');
        
        if (classId) {
            $.ajax({
                url: `/admin/exam-reset/subjects/${classId}`,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(subjects) {
                    subjectSelect.html('<option value="">Select Subject</option>');
                    subjects.forEach(function(subject) {
                        subjectSelect.append(`<option value="${subject.id}">${subject.name}</option>`);
                    });
                },
                error: function(xhr) {
                    console.error('Error loading subjects:', xhr);
                    subjectSelect.html('<option value="">Error loading subjects</option>');
                }
            });
        } else {
            subjectSelect.html('<option value="">Select Subject</option>');
        }
    });
    
    // Individual Reset Form Submit
    $('#resetStudentForm').submit(function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...').prop('disabled', true);
        
        $.ajax({
            url: '/admin/exam-reset/student',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.message);
                    $('#resetStudentForm')[0].reset();
                    $('#studentInfo').hide();
                    location.reload(); // Refresh to show updated recent resets
                } else {
                    alert('❌ ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert('❌ ' + (response?.message || 'An error occurred'));
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Bulk Reset Form Submit
    $('#bulkResetForm').submit(function(e) {
        e.preventDefault();
        
        const className = $('#bulk_class_id option:selected').text();
        const subjectName = $('#bulk_subject_id option:selected').text();
        
        if (!confirm(`⚠️ Are you sure you want to reset ${subjectName} for ALL students in ${className}?\n\nThis action cannot be undone!`)) {
            return;
        }
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '/admin/exam-reset/bulk',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.message);
                    $('#bulkResetForm')[0].reset();
                    location.reload(); // Refresh to show updated recent resets
                } else {
                    alert('❌ ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert('❌ ' + (response?.message || 'An error occurred'));
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});

// Search Student Function
function searchStudent() {
    const searchType = $('#search_type').val();
    const regNumber = $('#registration_number').val();
    const email = $('#email').val();
    const classId = $('#class_id').val();
    
    // Validate required fields based on search type
    if (searchType === 'registration') {
        if (!regNumber || !classId) {
            alert('Please enter registration number and select class first');
            return;
        }
    } else if (searchType === 'email') {
        if (!email) {
            alert('Please enter email address first');
            return;
        }
    }
    
    // Prepare data based on search type
    const searchData = {
        search_type: searchType,
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    if (searchType === 'registration') {
        searchData.registration_number = regNumber;
        searchData.class_id = classId;
    } else if (searchType === 'email') {
        searchData.email = email;
    }
    
    $.ajax({
        url: '/admin/exam-reset/search-student',
        method: 'POST',
        data: searchData,
        success: function(response) {
            if (response.success) {
                const student = response.student;
                let examsList = '';
                
                if (student.completed_exams.length > 0) {
                    student.completed_exams.forEach(function(exam) {
                        examsList += `<span class="exam-badge">${exam.subject_name}: ${exam.score}/${exam.total_questions} (${exam.percentage}%)</span>`;
                    });
                } else {
                    examsList = '<span class="text-muted">No completed exams</span>';
                }
                
                // Show class information for email searches
                let classInfo = '';
                if (searchType === 'email' && student.class_name) {
                    classInfo = `<p><strong>Class:</strong> ${student.class_name}</p>`;
                    // Auto-populate class field if student has a class
                    if (student.class_id) {
                        $('#class_id').val(student.class_id).trigger('change');
                    }
                }
                
                $('#studentInfo').html(`
                    <div class="student-info">
                        <h5><i class="fas fa-user"></i> Student Found</h5>
                        <p><strong>Name:</strong> ${student.name}</p>
                        <p><strong>Email:</strong> ${student.email}</p>
                        <p><strong>Registration:</strong> ${student.registration_number}</p>
                        ${classInfo}
                        <p><strong>Completed Exams:</strong><br>${examsList}</p>
                    </div>
                `).show();
            } else {
                alert('❌ ' + response.message);
                $('#studentInfo').hide();
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('❌ ' + (response?.message || 'Student not found'));
            $('#studentInfo').hide();
        }
    });
}
</script>
@endpush