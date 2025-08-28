@extends('layouts.admin')
@section('title', '- Edit Class')
@section('headerContent')<h3 class="font-bold pl-2">Edit Class: {{ $class->name }}</h3>@endsection

@section('content')
    <form action="{{ route('admin.classes.update', $class->id) }}" method="POST" class="space-y-6 max-w-lg mx-auto">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $class->name) }}" required class="mt-1 block w-full admin-input @error('name') border-red-500 @enderror">
            @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="level_group" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Level Group <span class="text-red-500">*</span></label>
            <select name="level_group" id="level_group" required class="mt-1 block w-full admin-input @error('level_group') border-red-500 @enderror">
                <option value="">-- Select Level Group --</option>
                @foreach($levelGroups as $group)
                    <option value="{{ $group }}" {{ old('level_group', $class->level_group) == $group ? 'selected' : '' }}>{{ $group }}</option>
                @endforeach
            </select>
            @error('level_group') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (Optional)</label>
            <textarea name="description" id="description" rows="3" class="mt-1 block w-full admin-input @error('description') border-red-500 @enderror">{{ old('description', $class->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('admin.classes.index') }}" class="admin-btn-secondary">Cancel</a>
            <button type="submit" class="admin-btn-primary">Update Class</button>
        </div>
    </form>
@endsection
