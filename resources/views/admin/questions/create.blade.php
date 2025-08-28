@extends('layouts.admin')
@section('title', "- Add Question to {$subject->name}")
@section('headerContent')<h3 class="font-bold pl-2">Add Question to: {{ $subject->name }}</h3>@endsection

@section('content')
    <form action="{{ route('admin.questions.store', $subject->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl mx-auto" id="questionForm">
        @csrf
        @include('admin.questions._form_fields', ['question' => null]) {{-- Pass null for question --}}

        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('admin.questions.index', $subject->id) }}" class="admin-btn-secondary">Cancel</a>
            <button type="submit" class="admin-btn-primary">Save Question</button>
        </div>
    </form>
@endsection

@push('scripts')
    @include('admin.questions._options_js')
@endpush
