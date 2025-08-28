@extends('layouts.admin')
@section('title', '- Add Subject')
@section('headerContent')<h3 class="font-bold pl-2">Add New Subject</h3>@endsection

@section('content')
    <form action="{{ route('admin.subjects.store') }}" method="POST" class="space-y-6 max-w-lg mx-auto">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full admin-input @error('name') border-red-500 @enderror">
            @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign to Class <span class="text-red-500">*</span></label>
            <select name="class_id" id="class_id" required class="mt-1 block w-full admin-input @error('class_id') border-red-500 @enderror">
                <option value="">-- Select Class --</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
            @error('class_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="exam_duration_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Exam Duration (Minutes) <span class="text-red-500">*</span></label>
            <input type="number" name="exam_duration_minutes" id="exam_duration_minutes" value="{{ old('exam_duration_minutes') }}" required min="1" class="mt-1 block w-full admin-input @error('exam_duration_minutes') border-red-500 @enderror">
            @error('exam_duration_minutes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('admin.subjects.index') }}" class="admin-btn-secondary">Cancel</a>
            <button type="submit" class="admin-btn-primary">Create Subject</button>
        </div>
    </form>
@endsection
