@extends('layouts.admin')

@section('title', '- Manage Classes')

@section('headerContent')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-1">Manage Classes</h2>
            <p class="mb-0 opacity-75">Organize students into different class levels</p>
        </div>
        <a href="{{ route('admin.classes.create') }}" class="btn btn-success">
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
                <form method="GET" action="{{ route('admin.classes.index') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                                <input type="text" name="search_class" class="form-control" 
                                       value="{{ request('search_class') }}" 
                                       placeholder="Search class name or group...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group" role="group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                @if(request('search_class'))
                                    <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Classes Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="classesTable">
                        <thead class="thead-light">
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
                            @forelse($classes as $class)
                            <tr>
                                <td>
                                    <strong>{{ $class->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $class->level_group ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $class->description ?? 'No description' }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $class->users->count() }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-success">{{ $class->subjects->count() }}</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.classes.edit', $class->id) }}" 
                                           class="action-btn edit-btn action-btn-sm" title="Edit Class">
                                            <i class="fas fa-chalkboard"></i>
                                            <span class="hidden sm:inline">Edit</span>
                                        </a>
                                        <form action="{{ route('admin.classes.destroy', $class->id) }}" 
                                              method="POST" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete class {{ $class->name }}? This will also affect associated students and subjects.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn delete-btn action-btn-sm" title="Delete Class">
                                                <i class="fas fa-school"></i>
                                                <span class="hidden sm:inline">Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    <p>No classes found. <a href="{{ route('admin.classes.create') }}">Create your first class</a></p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                @if(method_exists($classes, 'links'))
                    <div class="d-flex justify-content-center">
                        {{ $classes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .card-header {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
        border-radius: 12px 12px 0 0;
        margin: -2rem -2rem 2rem -2rem;
        padding: 1.5rem 2rem;
    }
    
    .card-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }
    
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
    
    .btn-success {
        background: #10b981;
        color: white;
    }
    
    .btn-success:hover {
        background: #059669;
        color: white;
        text-decoration: none;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2563eb;
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
        color: white;
        text-decoration: none;
    }
    
    .btn-outline-primary {
        background: transparent;
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }
    
    .btn-outline-primary:hover {
        background: #3b82f6;
        color: white;
        text-decoration: none;
    }
    
    .btn-outline-danger {
        background: transparent;
        color: #ef4444;
        border: 1px solid #ef4444;
    }
    
    .btn-outline-danger:hover {
        background: #ef4444;
        color: white;
        text-decoration: none;
    }
    
    .btn-outline-secondary {
        background: transparent;
        color: #6b7280;
        border: 1px solid #6b7280;
    }
    
    .btn-outline-secondary:hover {
        background: #6b7280;
        color: white;
        text-decoration: none;
    }
    
    .form-control {
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .input-group-text {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        color: #6b7280;
    }
    
    .table {
        background: white;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .table th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        font-weight: 600;
        color: #374151;
        padding: 1rem 0.75rem;
    }
    
    .table td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .table-hover tbody tr:hover {
        background: #f8fafc;
    }
    
    .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .badge-primary {
        background: #3b82f6;
        color: white;
    }
    
    .badge-info {
        background: #06b6d4;
        color: white;
    }
    
    .badge-success {
        background: #10b981;
        color: white;
    }
    
    .text-muted {
        color: #6b7280 !important;
    }
</style>
@endpush

@push('scripts')

<script>
$(document).ready(function() {
    // Initialize DataTable if there are classes
    @if($classes->count() > 0)
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
    @endif
});
</script>
@endpush
