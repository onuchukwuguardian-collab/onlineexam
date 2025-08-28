@extends('layouts.admin')
@section('title', '- Reset Exam Progress')
@section('headerContent')<h3 class="font-bold pl-2 text-2xl text-white">Reset Student Exam Progress</h3>@endsection

@section('content')
    <div class="admin-card max-w-2xl mx-auto">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Search for a student by their Name, Email, Registration Number, or School Passcode to reset their progress for a specific subject.
        </p>

        {{-- Search Form --}}
        <form method="GET" action="{{ route('admin.reset.index') }}" class="mb-6">
            <label for="search_user_term" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Student</label>
            <div class="mt-1 flex flex-col sm:flex-row rounded-md shadow-sm sm:space-x-2">
                <input type="text" name="search_user_term" id="search_user_term" value="{{ $searchTerm ?? old('search_user_term') }}"
                       placeholder="Enter Name, Email, Reg No, or Passcode..." required
                       class="admin-input w-full mb-2 sm:mb-0">
                <button type="submit" class="admin-btn-primary whitespace-nowrap px-6">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
            </div>
             @if($searchTerm ?? null)
                <a href="{{ route('admin.reset.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block">Clear Search</a>
            @endif
        </form>
        
        {{-- Search Feedback --}}
        @if($searchFeedback)
            <div class="my-4 p-3 rounded-md {{ str_contains(strtolower($searchFeedback), 'no student found') ? 'bg-yellow-50 dark:bg-yellow-800 text-yellow-700 dark:text-yellow-300' : 'bg-blue-50 dark:bg-blue-800 text-blue-700 dark:text-blue-300' }}">
                <i class="fas {{ str_contains(strtolower($searchFeedback), 'no student found') ? 'fa-exclamation-circle' : 'fa-info-circle' }} mr-2"></i>
                {{ $searchFeedback }}
            </div>
        @endif

        {{-- Reset Form - Shown only if a unique student is found --}}
        @if($foundStudent)
            <div class="mt-6 pt-6 border-t dark:border-gray-700">
                <h4 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-200">Resetting for:</h4>
                <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/50 rounded-md border border-indigo-200 dark:border-indigo-700">
                    <p><strong>Name:</strong> {{ $foundStudent->name }}</p>
                    <p><strong>Email:</strong> {{ $foundStudent->email }}</p>
                    <p><strong>Registration No:</strong> 
                        @if($foundStudent->registration_number)
                            {{ substr($foundStudent->registration_number, 0, 1) . str_repeat('*', max(0, strlen($foundStudent->registration_number) - 3)) . substr($foundStudent->registration_number, -2) }}
                        @else
                            N/A
                        @endif
                    </p>
                    <p><strong>School Passcode:</strong> {{ $foundStudent->unique_id ?? 'N/A' }}</p>
                </div>

                <form action="{{ route('admin.reset.progress') }}" method="POST" class="space-y-6" onsubmit="return confirm('Are you absolutely sure you want to reset exam progress for {{ $foundStudent->name }} and the selected subject? This action cannot be undone.');">
                    @csrf
                    <input type="hidden" name="user_id_to_reset" value="{{ $foundStudent->id }}">
                    
                    <div>
                        <label for="subject_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Subject to Reset <span class="text-red-500">*</span></label>
                        <select name="subject_id" id="subject_id" required 
                                class="mt-1 admin-input @error('subject_id') border-red-500 @enderror">
                            <option value="">-- Choose Subject --</option>
                            @foreach($subjects as $className => $classSubjects)
                                @if(!$classSubjects->isEmpty())
                                <optgroup label="Class: {{ $className }}">
                                    @foreach($classSubjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            @endforeach
                        </select>
                         @if($errors->has('subject_id')) <p class="mt-1 text-sm text-red-500">{{ $errors->first('subject_id') }}</p> @endif
                    </div>

                    <div>
                        <label for="reset_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason for Reset (Optional)</label>
                        <textarea name="reset_reason" id="reset_reason" rows="3" 
                                  class="mt-1 admin-input @error('reset_reason') border-red-500 @enderror"
                                  placeholder="E.g., Student experienced technical difficulty during exam.">{{ old('reset_reason') }}</textarea>
                        @if($errors->has('reset_reason')) <p class="mt-1 text-sm text-red-500">{{ $errors->first('reset_reason') }}</p> @endif
                    </div>
                    
                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="admin-btn-red">
                            <i class="fas fa-undo mr-2"></i>Reset Progress for {{ Str::limit($foundStudent->name, 15) }}
                        </button>
                    </div>
                </form>
            </div>
        @elseif($searchTerm && !$searchFeedback) 
            {{-- This case might occur if searchTerm is set but foundStudent is null and searchFeedback somehow wasn't set --}}
            <p class="text-center text-gray-500 dark:text-gray-400 my-4">Please enter a search term to find a student.</p>
        @endif
    </div>
@endsection

@push('scripts')
    {{-- Select2 for subject dropdown can be useful if list is very long --}}
    {{-- 
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('#subject_id').length) {
                $('#subject_id').select2({
                    placeholder: "-- Choose Subject --",
                    allowClear: true,
                });
            }
        });
    </script>
    --}}
@endpush
