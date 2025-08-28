@extends('layouts.admin') {{-- Assuming you have an admin layout --}}

@section('title', '- Bulk Upload Users')

@section('headerContent')
    <h3 class="font-bold pl-2 text-2xl text-white">Bulk Upload Users</h3>
@endsection

@section('content')
    <div class="admin-card max-w-2xl mx-auto">
        <div class="mb-6 p-4 border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900 dark:border-blue-700">
            <h4 class="font-semibold text-blue-700 dark:text-blue-300">Instructions:</h4>
            <ul class="list-disc list-inside mt-2 text-sm text-gray-700 dark:text-gray-400 space-y-1">
                <li>Upload a CSV (Comma Separated Values) file with <strong>exactly these headers</strong>:
                    <br><code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">name,email,password,role,class_name,registration_number,unique_id</code>
                </li>
                <li><strong>Registration Number Rules:</strong>
                    <ul class="list-disc list-inside ml-4 mt-1">
                        <li>Must be exactly <strong>10 digits</strong></li>
                        <li>JSS classes: Must start with <strong>5</strong> (e.g., 5000000001)</li>
                        <li>SS classes: Must start with <strong>2</strong> (e.g., 2000000001)</li>
                        <li>Leave blank to auto-generate</li>
                    </ul>
                </li>
                <li><strong>Email Rules:</strong>
                    <ul class="list-disc list-inside ml-4 mt-1">
                        <li><strong>Students:</strong> Email is <strong>optional</strong> - leave blank to auto-generate</li>
                        <li><strong>Admins:</strong> Email is <strong>required</strong></li>
                        <li>Auto-generated format: name.registrationnumber@student.school.com</li>
                    </ul>
                </li>
                <li><strong>Role:</strong> Use "user" for students, "admin" for administrators</li>
                <li><strong>Class Name:</strong> Must match existing classes (JSS1, JSS2, JSS3, SS1, SS2, SS3)</li>
                <li><strong>Password:</strong> Minimum 6 characters</li>
                <li><strong>Registration Numbers:</strong> Must be unique (no duplicates)</li>
                <li><strong>For Admins:</strong> Leave class_name, registration_number, and unique_id blank</li>
            </ul>
            <p class="mt-3">
                <a href="{{ asset('samples/sample-users-upload.csv') }}" download 
                   class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                    <i class="fas fa-download mr-1"></i> Download Sample CSV Template
                </a>
            </p>
        </div>

        <form action="{{ route('admin.users.processBulkUpload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="user_csv" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Select CSV File <span class="text-red-500">*</span>
                </label>
                <input type="file" name="user_csv" id="user_csv" required accept=".csv, text/csv"
                       class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 dark:file:bg-indigo-700 file:text-indigo-700 dark:file:text-indigo-200
                              hover:file:bg-indigo-100 dark:hover:file:bg-indigo-600
                              border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer
                              focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                              @error('user_csv') border-red-500 dark:border-red-400 @enderror">
                @error('user_csv') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="mt-8 flex justify-between items-center">
                 <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Users List
                </a>
                <button type="submit" class="admin-btn-primary">
                    <i class="fas fa-upload mr-2"></i>Upload and Process Users
                </button>
            </div>
        </form>

        @if(session('bulk_upload_errors_detailed'))
            <div class="mt-8 p-4 bg-red-50 dark:bg-red-900 border border-red-300 dark:border-red-600 rounded-lg">
                <h4 class="font-semibold text-lg text-red-700 dark:text-red-200">Errors During Last Bulk Upload:</h4>
                <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-300 max-h-60 overflow-y-auto mt-2 space-y-1">
                    @foreach(session('bulk_upload_errors_detailed') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
