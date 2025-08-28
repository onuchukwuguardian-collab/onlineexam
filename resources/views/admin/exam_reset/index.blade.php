@extends('layouts.admin')

@section('title', 'Exam Reset Management')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                        <i class="fas fa-redo-alt text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Exam Reset Management</h1>
                        <p class="text-blue-100 mt-1">Reset student exam progress and allow retakes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div id="success-alert" class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg shadow-sm max-w-full">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-green-800 font-medium break-words">{{ session('success') }}</p>
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        <button onclick="document.getElementById('success-alert').remove()" class="text-green-400 hover:text-green-600 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div id="error-alert" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg shadow-sm max-w-full">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-red-800 font-medium break-words">{{ session('error') }}</p>
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        <button onclick="document.getElementById('error-alert').remove()" class="text-red-400 hover:text-red-600 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Reset Type Selection -->
        <div id="reset_type_selection" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Individual Reset Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1" onclick="selectResetType('individual')">
                <div class="p-8 text-center">
                    <div class="bg-blue-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Individual Reset</h3>
                    <p class="text-gray-600 mb-6">Reset exam progress for a specific student and subject</p>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-user-edit mr-2"></i>
                        Select Individual
                    </button>
                </div>
            </div>

            <!-- Bulk Reset Card -->
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1" onclick="selectResetType('bulk')">
                <div class="p-8 text-center">
                    <div class="bg-orange-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-3xl text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Bulk Reset</h3>
                    <p class="text-gray-600 mb-6">Reset exam progress for multiple students or entire classes</p>
                    <button class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-users-cog mr-2"></i>
                        Select Bulk
                    </button>
                </div>
            </div>
        </div>

        <!-- Individual Reset Section -->
        <div id="individual_reset_section" class="hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg">
                        <div class="bg-blue-600 text-white p-4 rounded-t-xl">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold">
                                    <i class="fas fa-user-edit mr-2"></i>
                                    Individual Student Reset
                                </h2>
                                <button onclick="goBackToSelection()" class="bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Back
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('admin.exam.reset.process') }}" method="POST" id="resetForm">
                                @csrf
                                
                                <!-- Step 1: Student Search -->
                                <div id="step1" class="reset-step">
                                    <div class="flex items-center mb-6">
                                        <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                                            <span class="font-bold">1</span>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900">Find Student</h3>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-search mr-1"></i>
                                            Search for Student
                                        </label>
                                        <div class="relative">
                                            <input type="text" 
                                                   id="student_search" 
                                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" 
                                                   placeholder="Enter student ID, registration number, email, or name..."
                                                   autocomplete="off">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Type at least 2 characters to search by student ID, registration number, email, or name
                                        </p>
                                    </div>
                                    
                                    <!-- Search Results -->
                                    <div id="search_results" class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            <i class="fas fa-list mr-1"></i>
                                            Search Results
                                        </label>
                                        <div id="student_cards" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
                                    </div>
                                </div>

                                <!-- Step 2: Student Details -->
                                <div id="step2" class="reset-step hidden">
                                    <div class="flex items-center mb-6">
                                        <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                                            <span class="font-bold">2</span>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900">Student Details</h3>
                                    </div>
                                    <div id="selected_student_info"></div>
                                    <input type="hidden" name="student_id" id="selected_student_id">
                                </div>

                                <!-- Step 3: Subject Selection -->
                                <div id="step3" class="reset-step hidden">
                                    <div class="flex items-center mb-6">
                                        <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                                            <span class="font-bold">3</span>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900">Select Subject</h3>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-book mr-1"></i>
                                            Choose Subject to Reset
                                        </label>
                                        <select name="subject_id" id="subject_id" class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" required>
                                            <option value="">Select a subject...</option>
                                        </select>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Only subjects available for the student's class are shown
                                        </p>
                                    </div>
                                </div>

                                <!-- Step 4: Confirmation -->
                                <div id="step4" class="reset-step hidden">
                                    <div class="flex items-center mb-6">
                                        <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3">
                                            <span class="font-bold">4</span>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900">Confirm Reset</h3>
                                    </div>
                                    
                                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="text-yellow-800 font-medium">Please Review Before Proceeding</h4>
                                                <div id="reset_summary" class="mt-2 text-yellow-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-6">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="confirm_reset" id="confirm_reset" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" required>
                                            <span class="ml-2 text-sm font-medium text-red-600">
                                                <i class="fas fa-check-square mr-1"></i>
                                                I understand this action cannot be undone and confirm the reset
                                            </span>
                                        </label>
                                    </div>

                                    <button type="submit" id="final_submit_btn" disabled class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                                        <i class="fas fa-redo-alt mr-2"></i>
                                        Reset Exam Progress
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Information Panel -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg">
                        <div class="bg-blue-100 p-4 rounded-t-xl">
                            <h3 class="text-lg font-semibold text-blue-900">
                                <i class="fas fa-info-circle mr-2"></i>
                                Information
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="bg-blue-50 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-blue-900 mb-2">
                                    <i class="fas fa-question-circle mr-1"></i>
                                    What happens when you reset?
                                </h4>
                                <ul class="text-sm text-blue-800 space-y-1">
                                    <li>• Student's exam score will be permanently deleted</li>
                                    <li>• All submitted answers will be removed</li>
                                    <li>• Any active exam session will be terminated</li>
                                    <li>• Student can retake the exam immediately</li>
                                    <li class="text-red-600 font-semibold">• This action cannot be undone</li>
                                </ul>
                            </div>
                            
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <h4 class="font-semibold text-yellow-900 mb-2">
                                    <i class="fas fa-search mr-1"></i>
                                    How to find students:
                                </h4>
                                <ul class="text-sm text-yellow-800 space-y-1">
                                    <li>• Enter student ID (e.g., STU001)</li>
                                    <li>• Enter registration number (e.g., REG2024001)</li>
                                    <li>• Enter email address</li>
                                    <li>• Enter full name or part of name</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Reset Section -->
        <div id="bulk_reset_section" class="hidden">
            <div class="bg-white rounded-xl shadow-lg">
                <div class="bg-orange-600 text-white p-4 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold">
                            <i class="fas fa-users-cog mr-2"></i>
                            Bulk Student Reset
                        </h2>
                        <button onclick="goBackToSelection()" class="bg-orange-500 hover:bg-orange-400 px-4 py-2 rounded-lg transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.exam.reset.bulk') }}" method="POST" id="bulkResetForm">
                        @csrf
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-4">
                                <i class="fas fa-list mr-1"></i>
                                Bulk Reset Type
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="relative">
                                    <input type="radio" name="bulk_type" value="class" class="sr-only" onchange="updateBulkOptions()">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-colors duration-200 radio-card">
                                        <div class="text-center">
                                            <i class="fas fa-school text-2xl text-gray-600 mb-2"></i>
                                            <h4 class="font-semibold text-gray-900">By Class</h4>
                                            <p class="text-sm text-gray-600 mt-1">Reset subjects for entire class</p>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative">
                                    <input type="radio" name="bulk_type" value="subject" class="sr-only" onchange="updateBulkOptions()">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-colors duration-200 radio-card">
                                        <div class="text-center">
                                            <i class="fas fa-book text-2xl text-gray-600 mb-2"></i>
                                            <h4 class="font-semibold text-gray-900">By Subject</h4>
                                            <p class="text-sm text-gray-600 mt-1">Reset subject across all classes</p>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative">
                                    <input type="radio" name="bulk_type" value="custom" class="sr-only" onchange="updateBulkOptions()">
                                    <div class="border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 transition-colors duration-200 radio-card">
                                        <div class="text-center">
                                            <i class="fas fa-cog text-2xl text-gray-600 mb-2"></i>
                                            <h4 class="font-semibold text-gray-900">Custom</h4>
                                            <p class="text-sm text-gray-600 mt-1">Select specific students and subjects</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Bulk options will be populated here -->
                        <div id="bulk_options"></div>

                        <!-- Bulk Confirmation -->
                        <div id="bulk_confirmation_section" class="hidden mt-6">
                            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-red-800 font-medium">Bulk Reset Warning</h4>
                                        <p class="text-red-700 mt-1">This action will permanently delete exam data for multiple students and cannot be undone.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="flex items-center">
                                    <input type="checkbox" name="confirm_bulk_reset" id="confirm_bulk_reset" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded" required>
                                    <span class="ml-2 text-sm font-medium text-red-600">
                                        I understand this will permanently delete exam data for multiple students and confirm this bulk reset
                                    </span>
                                </label>
                            </div>

                            <button type="submit" id="bulk_submit_btn" disabled class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                                <i class="fas fa-users-slash mr-2"></i>
                                Execute Bulk Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let currentStep = 1;
let selectedStudent = null;

// Reset type selection
function selectResetType(type) {
    document.getElementById('reset_type_selection').classList.add('hidden');
    
    if (type === 'individual') {
        document.getElementById('individual_reset_section').classList.remove('hidden');
        document.getElementById('bulk_reset_section').classList.add('hidden');
        showStep(1);
    } else if (type === 'bulk') {
        document.getElementById('individual_reset_section').classList.add('hidden');
        document.getElementById('bulk_reset_section').classList.remove('hidden');
    }
}

// Go back to selection
function goBackToSelection() {
    document.getElementById('reset_type_selection').classList.remove('hidden');
    document.getElementById('individual_reset_section').classList.add('hidden');
    document.getElementById('bulk_reset_section').classList.add('hidden');
    
    // Reset forms
    document.getElementById('resetForm').reset();
    document.getElementById('bulkResetForm').reset();
    
    currentStep = 1;
    showStep(1);
}

// Show step for individual reset
function showStep(step) {
    // Hide all steps
    for (let i = 1; i <= 4; i++) {
        const stepElement = document.getElementById('step' + i);
        if (stepElement) {
            stepElement.classList.add('hidden');
        }
    }
    
    // Show current step
    const currentStepElement = document.getElementById('step' + step);
    if (currentStepElement) {
        currentStepElement.classList.remove('hidden');
    }
    
    currentStep = step;
}

// Student search functionality
function searchStudents(query) {
    fetch(`{{ route('admin.reset.search.students') }}?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.students);
            } else {
                console.error('Search failed:', data.message);
                showNotification('Search failed: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showNotification('Search error occurred', 'error');
        });
}

function displaySearchResults(students) {
    const searchResults = document.getElementById('search_results');
    const studentCards = document.getElementById('student_cards');
    
    if (students.length === 0) {
        studentCards.innerHTML = '<div class="col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center"><p class="text-blue-600">No students found matching your search.</p></div>';
    } else {
        let cardsHtml = '';
        students.forEach(student => {
            cardsHtml += `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors duration-200" onclick="selectStudent(${student.id}, ${JSON.stringify(student).replace(/"/g, '&quot;')})">
                    <h4 class="font-semibold text-gray-900">${student.name}</h4>
                    <p class="text-sm text-gray-600">ID: ${student.unique_id || 'N/A'}</p>
                    <p class="text-sm text-gray-600">Reg No: ${student.registration_number || 'N/A'}</p>
                    <p class="text-sm text-gray-600">Class: ${student.class_name}</p>
                    <p class="text-sm text-gray-500">${student.email}</p>
                </div>
            `;
        });
        studentCards.innerHTML = cardsHtml;
    }
    
    searchResults.classList.remove('hidden');
}

function selectStudent(studentId, studentData) {
    selectedStudent = studentData;
    document.getElementById('selected_student_id').value = studentId;
    
    // Populate student info
    document.getElementById('selected_student_info').innerHTML = `
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="text-green-800 font-semibold mb-2">
                <i class="fas fa-user-check mr-1"></i>
                Selected Student
            </h4>
            <div class="text-green-700">
                <p><strong>Name:</strong> ${studentData.name}</p>
                <p><strong>ID:</strong> ${studentData.unique_id}</p>
                <p><strong>Email:</strong> ${studentData.email}</p>
                <p><strong>Class:</strong> ${studentData.class_name}</p>
            </div>
        </div>
    `;
    
    // Load subjects for this student's class
    loadSubjectsForClass(studentData.class_id);
    
    // Move to next step
    showStep(2);
    setTimeout(() => showStep(3), 1000);
}

function loadSubjectsForClass(classId) {
    fetch(`{{ route('admin.reset.student.subjects') }}?user_id=${selectedStudent.id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateSubjectDropdown(data.subjects);
            } else {
                console.error('Failed to load subjects:', data.message);
                showNotification('Failed to load subjects: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Subject loading error:', error);
            showNotification('Error loading subjects', 'error');
        });
}

function populateSubjectDropdown(subjects) {
    const subjectSelect = document.getElementById('subject_id');
    let optionsHtml = '<option value="">Select a subject...</option>';
    
    subjects.forEach(subject => {
        optionsHtml += `<option value="${subject.id}">${subject.name}</option>`;
    });
    
    subjectSelect.innerHTML = optionsHtml;
}

function updateBulkOptions() {
    const bulkType = document.querySelector('input[name="bulk_type"]:checked');
    const bulkOptions = document.getElementById('bulk_options');
    
    // Update radio card styling
    document.querySelectorAll('.radio-card').forEach(card => {
        card.classList.remove('border-blue-500', 'bg-blue-50');
        card.classList.add('border-gray-200');
    });
    
    if (bulkType) {
        const selectedCard = bulkType.parentElement.querySelector('.radio-card');
        selectedCard.classList.remove('border-gray-200');
        selectedCard.classList.add('border-blue-500', 'bg-blue-50');
        
        // Show confirmation section
        document.getElementById('bulk_confirmation_section').classList.remove('hidden');
        
        // Load appropriate options based on type
        switch (bulkType.value) {
            case 'class':
                loadClassOptions();
                break;
            case 'subject':
                loadSubjectOptions();
                break;
            case 'custom':
                loadCustomOptions();
                break;
        }
    }
}

function loadClassOptions() {
    // Implementation for class-based reset options
    document.getElementById('bulk_options').innerHTML = `
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
            <select name="class_id" class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Choose a class...</option>
                @foreach(\App\Models\ClassModel::orderBy('name')->get() as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
    `;
}

function loadSubjectOptions() {
    // Implementation for subject-based reset options
    document.getElementById('bulk_options').innerHTML = `
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Subject</label>
            <select name="subject_id" class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Choose a subject...</option>
                @if(isset($subjects))
                    @foreach($subjects as $subject)
                        <option value="{{ $subject['id'] }}">{{ $subject['name'] }} (Class {{ $subject['class']['name'] ?? 'N/A' }})</option>
                    @endforeach
                @endif
            </select>
        </div>
    `;
}

function loadCustomOptions() {
    // Implementation for custom reset options
    document.getElementById('bulk_options').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Students</label>
                <div class="border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto">
                    <p class="text-gray-500 text-sm">Loading students...</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Subjects</label>
                <div class="border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto">
                    <p class="text-gray-500 text-sm">Loading subjects...</p>
                </div>
            </div>
        </div>
    `;
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'bg-red-50 border-red-400 text-red-800' : 'bg-blue-50 border-blue-400 text-blue-800';
    const iconClass = type === 'error' ? 'fas fa-exclamation-triangle text-red-400' : 'fas fa-info-circle text-blue-400';
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${alertClass} border-l-4 p-4 rounded-r-lg shadow-lg z-50 max-w-md`;
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="${iconClass}"></i>
            </div>
            <div class="ml-3">
                <p class="font-medium">${message}</p>
            </div>
            <div class="ml-auto">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Student search
    const studentSearch = document.getElementById('student_search');
    if (studentSearch) {
        studentSearch.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                searchStudents(query);
            } else {
                document.getElementById('search_results').classList.add('hidden');
            }
        });
    }
    
    // Confirmation checkboxes
    const confirmReset = document.getElementById('confirm_reset');
    const finalSubmitBtn = document.getElementById('final_submit_btn');
    
    if (confirmReset && finalSubmitBtn) {
        confirmReset.addEventListener('change', function() {
            finalSubmitBtn.disabled = !this.checked;
        });
    }
    
    const confirmBulkReset = document.getElementById('confirm_bulk_reset');
    const bulkSubmitBtn = document.getElementById('bulk_submit_btn');
    
    if (confirmBulkReset && bulkSubmitBtn) {
        confirmBulkReset.addEventListener('change', function() {
            bulkSubmitBtn.disabled = !this.checked;
        });
    }
    
    // Subject selection handler
    const subjectSelect = document.getElementById('subject_id');
    if (subjectSelect) {
        subjectSelect.addEventListener('change', function() {
            if (this.value && selectedStudent) {
                // Populate reset summary
                document.getElementById('reset_summary').innerHTML = `
                    <p><strong>Student:</strong> ${selectedStudent.name} (${selectedStudent.unique_id})</p>
                    <p><strong>Subject:</strong> ${this.options[this.selectedIndex].text}</p>
                    <p><strong>Class:</strong> ${selectedStudent.class_name}</p>
                `;
                showStep(4);
            }
        });
    }
});
</script>
@endsection