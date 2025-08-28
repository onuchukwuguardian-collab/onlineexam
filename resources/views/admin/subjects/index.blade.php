@extends('layouts.admin_bootstrap')
@section('title', '- Manage Subjects')
@section('headerContent')
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="font-weight-bold">Manage Subjects</h3>
        <a href="{{ route('admin.subjects.create') }}" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i> Add New Subject
        </a>
    </div>
@endsection

@section('content')
    {{-- Filter by class and search --}}
    <form method="GET" action="{{ route('admin.subjects.index') }}" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="class_id_filter" class="form-label">Filter by Class:</label>
                <select name="class_id_filter" id="class_id_filter" class="form-control">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id_filter') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="search_subject" class="form-label">Search Subject:</label>
                <input type="text" name="search_subject" id="search_subject" value="{{ request('search_subject') }}" placeholder="Subject name" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                    @if(request()->hasAny(['class_id_filter', 'search_subject']))
                        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary"><i class="fas fa-times mr-1"></i>Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-hover">
                {{-- Table Head --}}
                <thead class="thead-dark">
                    <tr>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Duration (Mins)</th>
                        <th>Questions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                {{-- Table Body --}}
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td>{{ $subject->name }}</td>
                            <td>{{ $subject->classModel->name ?? 'N/A' }}</td>
                            <td>{{ $subject->exam_duration_minutes }}</td>
                            <td>
                                <a href="{{ route('admin.questions.index', $subject->id) }}" class="btn btn-info btn-sm @if($subject->questions_count == 0) btn-warning @endif" title="Manage Questions">
                                    <i class="fas fa-list-ol"></i>
                                    <span>{{ $subject->questions_count }} Questions</span>
                                </a>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.subjects.edit', $subject->id) }}" class="btn btn-primary btn-sm" title="Edit Subject">
                                        <i class="fas fa-book-open"></i>
                                        <span class="d-none d-sm-inline">Edit</span>
                                    </a>
                                    <a href="{{ route('admin.questions.index', $subject->id) }}" class="btn btn-success btn-sm" title="Manage Questions">
                                        <i class="fas fa-question-circle"></i>
                                        <span class="d-none d-sm-inline">Questions</span>
                                    </a>
                                    <form action="{{ route('admin.subjects.destroy', $subject->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete subject {{ $subject->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Subject">
                                            <i class="fas fa-book-dead"></i>
                                            <span class="d-none d-sm-inline">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No subjects found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $subjects->appends(request()->query())->links() }}
    </div>
@endsection
