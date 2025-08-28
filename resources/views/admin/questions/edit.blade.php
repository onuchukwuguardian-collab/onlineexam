@extends('layouts.admin')
@section('title', "- Edit Question for {$subject->name}")
@section('headerContent')
    <h3 class="font-bold pl-2">Edit Question #{{$question->id}} for: {{ $subject->name }}</h3>
@endsection

@section('content')
    <form action="{{ route('admin.questions.update', $question->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl mx-auto" id="questionForm">
        @csrf
        @method('PUT')
        {{-- Pass $formOptions to the partial, which is prepared in the controller --}}
        @include('admin.questions._form_fields', ['question' => $question, 'formOptions' => $formOptions]) 
        
        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('admin.questions.index', $subject->id) }}" class="admin-btn-secondary">Cancel</a>
            <button type="submit" class="admin-btn-primary">Update Question</button>
        </div>
    </form>
@endsection

@push('scripts')
    @include('admin.questions._options_js') {{-- Re-use the same JS --}}
@endpush
