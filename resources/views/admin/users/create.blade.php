@extends('layouts.admin') {{-- Assuming you have an admin layout --}}

@section('title', '- Add New User')

@section('headerContent')
    <h3 class="font-bold pl-2 text-2xl text-white">Add New User</h3>
@endsection

@section('content')
    <div class="admin-card max-w-2xl mx-auto"> {{-- Use a container with max-width for better form layout --}}
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Full Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full admin-input @error('name') border-red-500 dark:border-red-400 @enderror"
                       placeholder="e.g., John Doe">
                @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full admin-input @error('email') border-red-500 dark:border-red-400 @enderror"
                       placeholder="e.g., user@example.com">
                @error('email') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Password --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" required
                           class="mt-1 block w-full admin-input @error('password') border-red-500 dark:border-red-400 @enderror"
                           placeholder="Min. 8 characters">
                    @error('password') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="mt-1 block w-full admin-input">
                </div>
            </div>

            {{-- Role --}}
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                <select name="role" id="role_create" required {{-- Ensure unique ID if create & edit forms are complex --}}
                        class="mt-1 block w-full admin-input @error('role') border-red-500 dark:border-red-400 @enderror">
                    <option value="user" {{ old('role', 'user') == 'user' ? 'selected' : '' }}>Student</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
                @error('role') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Class Assignment (Conditional for Students) --}}
            <div id="class_assignment_div_create" class="{{ old('role', 'user') == 'user' ? '' : 'hidden' }}">
                <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Assign to Class (Required for Students) <span class="text-red-500">*</span>
                </label>
                <select name="class_id" id="class_id_create" {{-- Potentially make required via JS --}}
                        class="mt-1 block w-full admin-input @error('class_id') border-red-500 dark:border-red-400 @enderror">
                    <option value="">-- Select Class --</option>
                    @foreach($classes as $class) {{-- $classes must be passed from UserController@create --}}
                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }} ({{ $class->level_group }})
                        </option>
                    @endforeach
                </select>
                @error('class_id') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            
            {{-- Registration Number (Conditional for Students) --}}
            <div id="registration_number_div_create" class="{{ old('role', 'user') == 'user' ? '' : 'hidden' }}">
                <label for="registration_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Registration Number (Auto-generated for students if empty & class selected)
                </label>
                <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number') }}" 
                       class="mt-1 block w-full admin-input @error('registration_number') border-red-500 dark:border-red-400 @enderror"
                       placeholder="e.g., 50001 or 20001">
                @error('registration_number') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- School Passcode (unique_id) --}}
            <div>
                <label for="unique_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    School Passcode (Optional Unique ID)
                </label>
                <input type="text" name="unique_id" id="unique_id" value="{{ old('unique_id') }}" 
                       class="mt-1 block w-full admin-input @error('unique_id') border-red-500 dark:border-red-400 @enderror"
                       placeholder="e.g., SCHPASS001">
                @error('unique_id') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary">Cancel</a>
                <button type="submit" class="admin-btn-primary">
                    <i class="fas fa-plus mr-2"></i>Create User
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role_create');
        const classAssignmentDiv = document.getElementById('class_assignment_div_create');
        const classIdSelect = document.getElementById('class_id_create');
        const registrationNumberDiv = document.getElementById('registration_number_div_create');

        function toggleStudentFields() {
            const isStudent = roleSelect.value === 'user';
            classAssignmentDiv.classList.toggle('hidden', !isStudent);
            registrationNumberDiv.classList.toggle('hidden', !isStudent);
            
            if (classIdSelect) {
                 classIdSelect.required = isStudent; // Make class_id required only if role is student
                 if(!isStudent) classIdSelect.value = ''; // Clear if not student
            }
            const regNoInput = document.getElementById('registration_number');
            if (regNoInput && !isStudent) {
                regNoInput.value = ''; // Clear if not student
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', toggleStudentFields);
            // Initial call to set visibility based on old() value or default 'user'
            toggleStudentFields(); 
        }
    });
</script>
@endpush
