@extends('layouts.admin_bootstrap')

@section('title', '- Manage Classes')

@section('headerContent')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Manage Classes</h2>
            <p class="mb-0 opacity-75">Organize students into different class levels</p>
        </div>
        <a href="{{ route('admin.classes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Class
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chalkboard-teacher"></i> All Classes
                </h5>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" placeholder="Search classes..." id="searchInput">
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Classes Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="classesTable">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Level Group</th>
                                <th>Description</th>
                                <th>Students</th>
                                <th>Subjects</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classes ?? [] as $class)
                            <tr>
                                <td>
                                    <strong>{{ $class->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $class->level_group ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $class->description ?? 'No description' }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $class->users_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-success">{{ $class->subjects_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.classes.edit', $class->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteClass({{ $class->id }})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No classes found. <a href="{{ route('admin.classes.create') }}">Create your first class</a></p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/dataTables.bootstrap4.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#classesTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "Search classes:",
            lengthMenu: "Show _MENU_ classes per page",
            info: "Showing _START_ to _END_ of _TOTAL_ classes",
            emptyTable: "No classes available"
        }
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        $('#classesTable').DataTable().search(this.value).draw();
    });
});

function deleteClass(classId) {
    if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/classes/${classId}`;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = document.querySelector('meta[name="csrf-token"]').content;
        
        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush