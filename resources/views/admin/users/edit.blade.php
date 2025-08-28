@extends('layouts.admin')
@section('title', '- Edit User')
@section('headerContent')<h3 class="font-bold pl-2 text-2xl text-white">Edit User: {{ $user->name }}</h3>@endsection

@section('content')
    <div class="admin-card max-w-2xl mx-auto">
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            {{-- Name --}}
            <div>
                <label for="name" class="admin-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required 
                       class="mt-1 block w-full admin-input @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="admin-label">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required 
                       class="mt-1 block w-full admin-input @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="admin-label">New Password (leave blank to keep current)</label>
                <input type="password" name="password" id="password" 
                       class="mt-1 block w-full admin-input @error('password') border-red-500 @enderror">
                @error('password') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="admin-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" 
                       class="mt-1 block w-full admin-input">
            </div>

            {{-- Role --}}
            <div>
                <label for="role" class="admin-label">Role <span class="text-red-500">*</span></label>
                <select name="role" id="role_edit" required {{ $user->id === Auth::id() || ($user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1 && $user->id !== Auth::id()) ? 'disabled' : '' }}
                        class="mt-1 block w-full admin-input @error('role') border-red-500 @enderror {{ $user->id === Auth::id() || ($user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1 && $user->id !== Auth::id()) ? 'bg-gray-200 dark:bg-gray-600 cursor-not-allowed' : '' }}">
                    <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>Student</option>
                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
                @if($user->id === Auth::id() || ($user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1 && $user->id !== Auth::id()))
                    <input type="hidden" name="role" value="{{$user->role}}"> {{-- Submit current role if disabled --}}
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cannot change role for self or the last admin.</p>
                @endif
                @error('role') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Class Assignment --}}
            <div id="class_assignment_div_edit" class="{{ old('role', $user->role) == 'user' ? '' : 'hidden' }}">
                <label for="class_id" class="admin-label">Assign to Class (Required for Students) <span class="text-red-500">*</span></label>
                <select name="class_id" id="class_id_edit" 
                        class="mt-1 block w-full admin-input @error('class_id') border-red-500 @enderror">
                    <option value="">-- Select Class --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ old('class_id', $user->class_id) == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
                @error('class_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            
            {{-- Registration Number --}}
            <div id="registration_number_div_edit" class="{{ old('role', $user->role) == 'user' ? '' : 'hidden' }}">
                <label for="registration_number" class="admin-label">Registration Number (Auto-updated for students if class changes and field is empty)</label>
                <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $user->registration_number) }}" 
                       class="mt-1 block w-full admin-input @error('registration_number') border-red-500 @enderror">
                @error('registration_number') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- School Passcode --}}
            <div>
                <label for="unique_id" class="admin-label">School Passcode (Optional Unique ID)</label>
                <input type="text" name="unique_id" id="unique_id" value="{{ old('unique_id', $user->unique_id) }}" 
                       class="mt-1 block w-full admin-input @error('unique_id') border-red-500 @enderror">
                @error('unique_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary">Cancel</a>
                <button type="submit" class="admin-btn-primary">Update User</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role_edit'); // Unique ID for edit form
        const classAssignmentDiv = document.getElementById('class_assignment_div_edit');
        const classIdSelect = document.getElementById('class_id_edit');
        const registrationNumberDiv = document.getElementById('registration_number_div_edit');

        function toggleStudentFieldsEdit() {
            const isStudent = roleSelect.value === 'user';
            classAssignmentDiv.classList.toggle('hidden', !isStudent);
            registrationNumberDiv.classList.toggle('hidden', !isStudent);
            
            if (classIdSelect) {
                classIdSelect.required = isStudent;
                if (!isStudent) classIdSelect.value = ''; 
            }
            // Do not clear registration_number here, admin might be changing role from student to admin
            // The controller handles nullifying it if role is no longer student.
        }

        if (roleSelect && !roleSelect.disabled) { // Only add listener if not disabled
            roleSelect.addEventListener('change', toggleStudentFieldsEdit);
        }
        toggleStudentFieldsEdit(); // Initial call
    });
</script>
@endpush
