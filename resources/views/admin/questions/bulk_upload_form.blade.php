@extends('layouts.admin')
@section('title', "- Bulk Upload Questions for {$subject->name}")
@section('headerContent')
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Bulk Upload Questions</h2>
            <p class="text-sm opacity-75">Subject: {{ $subject->name }}</p>
        </div>
        <div class="text-right">
            <span class="text-sm opacity-75">Current Questions:</span>
            <span class="text-xl font-bold">{{ $subject->questions()->count() }}</span>
        </div>
    </div>
@endsection

@section('content')
<div class="grid grid-2 gap-6">
    <!-- Upload Form -->
    <div class="card">
        <h3 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-upload mr-2 text-blue-500"></i>
            Upload CSV File
        </h3>
        
        <!-- Download Templates -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="font-semibold text-blue-800 mb-2">📥 Download Templates & Instructions</h4>
            <div class="flex flex-wrap gap-2">
                <a href="{{ asset('templates/questions_bulk_upload_template.csv') }}" download 
                   class="btn btn-primary btn-sm">
                    <i class="fas fa-download"></i> CSV Template
                </a>
                <a href="{{ asset('templates/BULK_UPLOAD_INSTRUCTIONS.md') }}" download 
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-file-text"></i> Instructions
                </a>
            </div>
            <p class="text-sm text-blue-700 mt-2">
                <strong>Tip:</strong> Download the template first to see the exact format required.
            </p>
        </div>

        <form action="{{ route('admin.questions.processBulkUpload', $subject->id) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <div class="mb-4">
                <label for="questions_csv" class="block text-sm font-medium text-gray-700 mb-2">
                    Select CSV File <span class="text-red-500">*</span>
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                    <input type="file" name="questions_csv" id="questions_csv" required accept=".csv,text/csv,.txt"
                           class="hidden" onchange="handleFileSelect(this)">
                    <label for="questions_csv" class="cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                        <p class="text-gray-600">Click to select CSV file or drag and drop</p>
                        <p class="text-sm text-gray-500 mt-1">Maximum file size: 2MB</p>
                    </label>
                    <div id="fileInfo" class="mt-3 hidden">
                        <p class="text-sm text-green-600">
                            <i class="fas fa-file-csv"></i> <span id="fileName"></span>
                        </p>
                    </div>
                </div>
                @error('questions_csv') 
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p> 
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.questions.index', $subject->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-upload"></i> Upload Questions
                </button>
            </div>
        </form>
    </div>

    <!-- Instructions Panel -->
    <div class="card">
        <h3 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-info-circle mr-2 text-green-500"></i>
            Quick Instructions
        </h3>
        
        <div class="space-y-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="font-semibold text-green-800 mb-2">✅ Required Columns</h4>
                <ul class="text-sm text-green-700 space-y-1">
                    <li><code class="bg-green-100 px-1 rounded">question_text</code> - The question</li>
                    <li><code class="bg-green-100 px-1 rounded">correct_answer</code> - A, B, C, D, or E</li>
                    <li><code class="bg-green-100 px-1 rounded">option_a_text</code> - Option A text</li>
                    <li><code class="bg-green-100 px-1 rounded">option_b_text</code> - Option B text</li>
                </ul>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2">📝 Optional Columns</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li><code class="bg-blue-100 px-1 rounded">option_c_text</code> - Option C text</li>
                    <li><code class="bg-blue-100 px-1 rounded">option_d_text</code> - Option D text</li>
                    <li><code class="bg-blue-100 px-1 rounded">option_e_text</code> - Option E text</li>
                </ul>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 mb-2">⚠️ Important Rules</h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• Headers are case-insensitive</li>
                    <li>• At least 2 options (A & B) required</li>
                    <li>• Correct answer must match an option</li>
                    <li>• Wrap text in quotes if it contains commas</li>
                    <li>• Maximum 65,000 characters per question</li>
                </ul>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">📋 Example Format</h4>
                <pre class="text-xs text-gray-600 bg-white p-2 rounded border overflow-x-auto">question_text,correct_answer,option_a_text,option_b_text
"What is 2+2?",A,"4","3"
"Capital of France?",A,"Paris","London"</pre>
            </div>
        </div>
    </div>
</div>

@if(session('bulk_upload_errors_detailed'))
    <div class="card mt-6" style="background: #fef2f2; border-left: 4px solid #ef4444;">
        <h4 class="font-semibold text-red-700 mb-3">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Upload Errors Encountered
        </h4>
        <div class="max-h-60 overflow-y-auto">
            <ul class="space-y-1">
                @foreach(session('bulk_upload_errors_detailed') as $error)
                    <li class="text-sm text-red-600 flex items-start">
                        <i class="fas fa-times-circle mr-2 mt-0.5 text-red-500 flex-shrink-0"></i>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-4 p-3 bg-red-100 rounded">
            <p class="text-sm text-red-700">
                <strong>Tip:</strong> Fix the errors above and try uploading again. 
                Each error shows the specific row number and issue.
            </p>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
function handleFileSelect(input) {
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
        fileInfo.classList.remove('hidden');
        
        // Validate file type
        const validTypes = ['text/csv', 'application/csv', 'text/plain'];
        if (!validTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.csv')) {
            alert('Please select a CSV file (.csv extension)');
            input.value = '';
            fileInfo.classList.add('hidden');
            return;
        }
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            input.value = '';
            fileInfo.classList.add('hidden');
            return;
        }
    } else {
        fileInfo.classList.add('hidden');
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Handle form submission
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const fileInput = document.getElementById('questions_csv');
    
    if (!fileInput.files || !fileInput.files[0]) {
        e.preventDefault();
        alert('Please select a CSV file to upload');
        return;
    }
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    // Re-enable button after 30 seconds as fallback
    setTimeout(() => {
        submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Questions';
        submitBtn.disabled = false;
    }, 30000);
});

// Drag and drop functionality
const dropZone = document.querySelector('.border-dashed');
const fileInput = document.getElementById('questions_csv');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-blue-400', 'bg-blue-50');
}

function unhighlight(e) {
    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(fileInput);
    }
}
</script>
@endpush
