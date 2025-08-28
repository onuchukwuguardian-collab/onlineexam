@extends('layouts.admin')

@section('title', "- Questions for {$subject->name}")

@section('headerContent')
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Questions: {{ $subject->name }}</h2>
            <p class="text-blue-100 mt-1">
                <i class="fas fa-graduation-cap mr-2"></i>
                Class: {{ $subject->classModel->name ?? 'N/A' }} | 
                <i class="fas fa-book mr-2"></i>
                Subject: {{ $subject->name }}
            </p>
        </div>
        <div class="text-right">
            <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                <span class="text-blue-100 text-sm">Total Questions:</span>
                <span class="text-white text-xl font-bold ml-2">{{ $questions->total() }}</span>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <!-- Action Bar -->
    <div class="card mb-6 questions-container">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.subjects.index') }}" class="action-btn edit-btn">
                    <i class="fas fa-arrow-left"></i> Back to Subjects
                </a>
                <span class="text-lg">{{ $questions->total() }} Questions</span>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Bulk Upload -->
                <a href="{{ route('admin.questions.bulkUploadForm', $subject->id) }}" class="action-btn info-btn">
                    <i class="fas fa-file-upload"></i> Bulk Upload
                </a>
                
                <!-- Add Single Question -->
                <button type="button" onclick="addNewQuestion()" class="action-btn view-btn">
                    <i class="fas fa-plus-circle"></i> Add Question
                </button>
                
                <!-- Save All Changes -->
                <button type="button" onclick="saveAllChanges()" class="action-btn warning-btn" id="saveAllBtn" style="display: none;">
                    <i class="fas fa-save"></i> Save All Changes
                </button>
                
                <!-- Delete Selected -->
                <button type="button" onclick="deleteSelected()" class="action-btn delete-btn" id="deleteSelectedBtn" style="display: none;">
                    <i class="fas fa-trash-alt"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Upload Modal -->
    <div id="bulkUploadModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-upload mr-2"></i>Bulk Upload Questions</h3>
                <button type="button" onclick="closeBulkUpload()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Download Templates -->
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                    <h4 class="font-semibold text-blue-800 mb-2">📥 Download Templates First</h4>
                    <div class="flex gap-2 mb-2">
                        <a href="{{ asset('templates/questions_bulk_upload_template.csv') }}" download 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i> CSV Template
                        </a>
                        <a href="{{ asset('templates/BULK_UPLOAD_INSTRUCTIONS.md') }}" download 
                           class="btn btn-secondary btn-sm">
                            <i class="fas fa-file-text"></i> Instructions
                        </a>
                    </div>
                    <p class="text-sm text-blue-700">Download the template to see the exact format required.</p>
                </div>

                <!-- Quick Format Guide -->
                <div class="mb-4 p-3 bg-gray-50 border rounded">
                    <h4 class="font-semibold mb-2">Required CSV Format:</h4>
                    <pre class="text-xs bg-white p-2 rounded border overflow-x-auto">question_text,correct_answer,option_a_text,option_b_text,option_c_text
"What is 2+2?",A,"4","3","5"
"Capital of France?",A,"Paris","London","Berlin"</pre>
                    <ul class="text-sm mt-2 space-y-1">
                        <li>• <strong>Required:</strong> question_text, correct_answer, option_a_text, option_b_text</li>
                        <li>• <strong>Optional:</strong> option_c_text, option_d_text, option_e_text</li>
                        <li>• Correct answer must be A, B, C, D, or E</li>
                        <li>• At least 2 options required</li>
                    </ul>
                </div>

                <form id="bulkUploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Select CSV File</label>
                        <div class="border-2 border-dashed border-gray-300 rounded p-4 text-center">
                            <input type="file" name="questions_csv" accept=".csv,.txt" required class="form-input">
                            <p class="text-sm text-gray-500 mt-1">Maximum file size: 2MB</p>
                        </div>
                    </div>
                    <div class="form-group flex gap-2">
                        <button type="button" onclick="closeBulkUpload()" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(session('bulk_upload_errors_detailed'))
        <div class="card mb-6" style="background: #fef2f2; border-left: 4px solid #ef4444;">
            <h4 style="color: #991b1b;">Bulk Upload Errors:</h4>
            <ul style="max-height: 200px; overflow-y: auto; margin-top: 0.5rem;">
                @foreach(session('bulk_upload_errors_detailed') as $error)
                    <li style="color: #dc2626; font-size: 0.875rem;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Excel-like Questions Grid -->
    <div class="excel-grid-container">
        <!-- Grid Header -->
        <div class="grid-header">
            <div class="grid-controls">
                <label class="flex items-center">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="mr-2">
                    <span>Select All</span>
                </label>
                <button type="button" onclick="addNewRow()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add Row
                </button>
                <button type="button" onclick="saveAllChanges()" class="btn btn-primary btn-sm" id="saveAllChangesBtn" style="display: none;">
                    <i class="fas fa-save"></i> Save All
                </button>
            </div>
        </div>

        <!-- Excel-like Table -->
        <div class="excel-table-wrapper">
            <table class="excel-table" id="questionsTable">
                <thead>
                    <tr>
                        <th class="col-select">
                            <input type="checkbox" id="headerSelectAll">
                        </th>
                        <th class="col-number">#</th>
                        <th class="col-question">Question Text</th>
                        <th class="col-image">Image</th>
                        <th class="col-option">Option A</th>
                        <th class="col-option">Option B</th>
                        <th class="col-option">Option C</th>
                        <th class="col-option">Option D</th>
                        <th class="col-option">Option E</th>
                        <th class="col-correct">Correct</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="questionsTableBody">
                    @if($questions->isEmpty())
                        <tr class="empty-row">
                            <td colspan="11" class="text-center" style="padding: 3rem;">
                                <i class="fas fa-table fa-3x mb-3" style="color: #9ca3af;"></i>
                                <p style="color: #6b7280;">No questions yet. Click "Bulk Upload" to import or "Add Row" to create manually.</p>
                            </td>
                        </tr>
                    @else
                        @foreach($questions as $index => $question)
                            <tr class="question-row" data-question-id="{{ $question->id }}" data-is-new="false">
                                <td class="col-select">
                                    <input type="checkbox" class="question-select" value="{{ $question->id }}">
                                </td>
                                <td class="col-number">{{ $questions->firstItem() + $index }}</td>
                                <td class="col-question">
                                    <div class="cell-content" onclick="enableInlineEdit(this, 'question_text', {{ $question->id }})">
                                        <div class="display-mode">{{ Str::limit($question->question_text, 100) }}</div>
                                        <textarea class="edit-mode" rows="3" style="display: none;" onblur="saveInlineEdit(this, 'question_text', {{ $question->id }})" onkeydown="handleInlineKeydown(event, this)">{{ $question->question_text }}</textarea>
                                    </div>
                                </td>
                                <td class="col-image">
                                    <div class="image-cell">
                                        @if($question->image_path)
                                            @php
                                                $imageUrl = asset('storage/' . $question->image_path) . '?t=' . time();
                                            @endphp
                                            <img src="{{ $imageUrl }}" alt="Question Image" class="cell-image" onclick="openImageModal({{ $question->id }}, '{{ $imageUrl }}')">
                                        @else
                                            <div class="no-image-placeholder" onclick="triggerImageUpload({{ $question->id }})">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                        <input type="file" class="image-input" accept="image/*" style="display: none;" onchange="handleImageUpload(this, {{ $question->id }})">
                                        <button type="button" class="image-btn" onclick="triggerImageUpload({{ $question->id }})">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                </td>
                                @php $letters = ['A', 'B', 'C', 'D', 'E']; @endphp
                                @foreach($letters as $letter)
                                    @php $option = $question->options->where('option_letter', $letter)->first(); @endphp
                                    <td class="col-option">
                                        <div class="cell-content" onclick="enableInlineEdit(this, 'option_{{ strtolower($letter) }}', {{ $question->id }})">
                                            <div class="display-mode">{{ $option ? Str::limit($option->option_text, 50) : '' }}</div>
                                            <input type="text" class="edit-mode" value="{{ $option ? $option->option_text : '' }}" placeholder="Option {{ $letter }}" style="display: none;" onblur="saveInlineEdit(this, 'option_{{ strtolower($letter) }}', {{ $question->id }})" onkeydown="handleInlineKeydown(event, this)">
                                        </div>
                                    </td>
                                @endforeach
                                <td class="col-correct">
                                    <select class="correct-answer-select">
                                        @foreach($letters as $letter)
                                            <option value="{{ $letter }}" {{ $question->correct_answer == $letter ? 'selected' : '' }}>{{ $letter }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <button type="button" class="action-btn edit-btn action-btn-icon" onclick="toggleRowEdit(this)" title="Edit Question">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="action-btn view-btn action-btn-icon" onclick="saveRow(this)" title="Save Changes" style="display: none;">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="action-btn warning-btn action-btn-icon" onclick="cancelRowEdit(this)" title="Cancel Edit" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button type="button" class="action-btn delete-btn action-btn-icon" onclick="deleteRow(this)" title="Delete Question">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(!$questions->isEmpty())
            <div class="grid-pagination">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
@endsection

@push('styles')
<style>
    /* Page spacing */
    .questions-container {
        margin-top: 1rem !important;
    }
    
    /* Ensure proper spacing from header */
    .admin-content {
        padding-top: 3rem !important;
    }
    
    /* Header content spacing */
    .admin-header {
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: white;
        border-radius: 0.5rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .modal-body {
        padding: 1rem;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
    }
    
    /* Excel-like Grid Styles */
    .excel-grid-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .grid-header {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem;
    }
    
    .grid-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .excel-table-wrapper {
        overflow-x: auto;
        max-height: 70vh;
        border: 1px solid #e2e8f0;
    }
    
    .excel-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.875rem;
    }
    
    .excel-table th {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        padding: 0.75rem 0.5rem;
        font-weight: 600;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .excel-table td {
        border: 1px solid #e2e8f0;
        padding: 0.5rem;
        vertical-align: top;
        background: white;
        position: relative;
    }
    
    .excel-table tr:hover td {
        background: #f8fafc;
    }
    
    .excel-table tr.editing td {
        background: #fef3c7;
        border-color: #f59e0b;
    }
    
    /* Column Widths */
    .col-select { width: 40px; text-align: center; }
    .col-number { width: 60px; text-align: center; }
    .col-question { width: 300px; }
    .col-image { width: 100px; text-align: center; }
    .col-option { width: 150px; }
    .col-correct { width: 80px; text-align: center; }
    .col-actions { width: 120px; text-align: center; }
    
    /* Cell Content */
    .cell-content {
        position: relative;
        min-height: 2rem;
    }
    
    .display-mode {
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.25rem;
        min-height: 1.5rem;
        word-wrap: break-word;
    }
    
    .display-mode:hover {
        background: #f1f5f9;
    }
    
    .edit-mode {
        width: 100%;
        border: 2px solid #3b82f6;
        border-radius: 0.25rem;
        padding: 0.25rem;
        font-size: 0.875rem;
        resize: vertical;
    }
    
    .edit-mode:focus {
        outline: none;
        border-color: #1d4ed8;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Image Cell */
    .image-cell {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .cell-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 0.25rem;
        border: 1px solid #e2e8f0;
    }
    
    .no-image-placeholder {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        border: 2px dashed #cbd5e1;
        border-radius: 0.25rem;
        color: #64748b;
    }
    
    .image-btn {
        padding: 0.25rem 0.5rem;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        cursor: pointer;
    }
    
    .image-btn:hover {
        background: #2563eb;
    }
    
    /* Select Dropdown */
    .correct-answer-select {
        width: 100%;
        padding: 0.25rem;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    
    .correct-answer-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.25rem;
        justify-content: center;
    }
    
    .btn-edit, .btn-save, .btn-cancel, .btn-delete {
        padding: 0.25rem;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        font-size: 0.75rem;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-edit { background: #f3f4f6; color: #374151; }
    .btn-edit:hover { background: #e5e7eb; }
    
    .btn-save { background: #10b981; color: white; }
    .btn-save:hover { background: #059669; }
    
    .btn-cancel { background: #6b7280; color: white; }
    .btn-cancel:hover { background: #4b5563; }
    
    .btn-delete { background: #ef4444; color: white; }
    .btn-delete:hover { background: #dc2626; }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    /* Grid Pagination */
    .grid-pagination {
        padding: 1rem;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    
    /* Button Styles */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-primary { background: #3b82f6; color: white; }
    .btn-primary:hover { background: #2563eb; }
    .btn-success { background: #10b981; color: white; }
    .btn-success:hover { background: #059669; }
    .btn-secondary { background: #6b7280; color: white; }
    .btn-secondary:hover { background: #4b5563; }
    .btn-warning { background: #f59e0b; color: white; }
    .btn-warning:hover { background: #d97706; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }
    
    .btn-edit, .btn-delete, .btn-upload {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }
    .btn-edit { background: #f3f4f6; color: #374151; }
    .btn-edit:hover { background: #e5e7eb; }
    .btn-delete { background: #fee2e2; color: #dc2626; }
    .btn-delete:hover { background: #fecaca; }
    .btn-upload { background: #dbeafe; color: #2563eb; }
    .btn-upload:hover { background: #bfdbfe; }
    
    /* Form Styles */
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: #374151;
    }
    .form-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Option Styles */
    .option-row {
        padding: 0.5rem;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }
    .option-row:hover {
        background: #f9fafb;
    }
    .option-letter {
        font-weight: bold;
        margin-right: 0.5rem;
        min-width: 1.5rem;
    }
    .option-text-display {
        padding: 0.25rem 0;
    }
    
    /* Image Styles */
    .image-container {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
        background: #f9fafb;
    }
    .question-image {
        max-width: 200px;
        max-height: 150px;
        border-radius: 0.375rem;
        object-fit: contain;
    }
    .no-image {
        color: #6b7280;
        font-style: italic;
    }
    
    /* Loading Spinner */
    .loading-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 60px;
        color: #3b82f6;
    }
    
    /* Toast Animations */
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Enhanced Cell Editing */
    .cell-content:hover .display-mode {
        background: #f1f5f9;
        cursor: text;
    }
    
    .cell-content .edit-mode:focus {
        outline: none;
        border-color: #1d4ed8;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .question-header {
            flex-direction: column;
            gap: 1rem;
        }
        .modal-content {
            width: 95%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
let editingQuestions = new Set();
let changedQuestions = new Set();

// Route templates for AJAX calls
const routes = {
    updateField: '{{ route("admin.questions.updateField", ":id") }}',
    updateImage: '{{ route("admin.questions.updateImage", ":id") }}',
    deleteImage: '{{ route("admin.questions.deleteImage", ":id") }}',
    update: '{{ route("admin.questions.update", ":id") }}',
    destroy: '{{ route("admin.questions.destroy", ":id") }}'
};

// Bulk Upload Functions
function showBulkUpload() {
    document.getElementById('bulkUploadModal').style.display = 'flex';
}

function closeBulkUpload() {
    document.getElementById('bulkUploadModal').style.display = 'none';
}

// Handle bulk upload form submission
document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    submitBtn.disabled = true;
    
    fetch('{{ route("admin.questions.processBulkUpload", $subject->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Questions uploaded successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Upload failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during upload');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Selection Functions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.question-select');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.question-select:checked');
    const count = selected.length;
    
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('deleteSelectedBtn').style.display = count > 0 ? 'inline-flex' : 'none';
    
    // Update select all checkbox
    const total = document.querySelectorAll('.question-select').length;
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = count === total && total > 0;
    selectAll.indeterminate = count > 0 && count < total;
}

// Edit Functions
function toggleEdit(questionId) {
    const card = document.querySelector(`[data-question-id="${questionId}"]`);
    const isEditing = editingQuestions.has(questionId);
    
    if (isEditing) {
        cancelEdit(questionId);
    } else {
        startEdit(questionId);
    }
}

function startEdit(questionId) {
    const card = document.querySelector(`[data-question-id="${questionId}"]`);
    
    // Toggle display elements
    card.querySelectorAll('.question-text-display').forEach(el => el.style.display = 'none');
    card.querySelectorAll('.question-text-edit').forEach(el => el.style.display = 'block');
    card.querySelectorAll('.option-text-display').forEach(el => el.style.display = 'none');
    card.querySelectorAll('.option-text-edit').forEach(el => el.style.display = 'block');
    card.querySelectorAll('.correct-answer-radio').forEach(el => el.disabled = false);
    card.querySelectorAll('.btn-upload').forEach(el => el.style.display = 'inline-flex');
    card.querySelectorAll('.question-actions').forEach(el => el.style.display = 'flex');
    
    // Add editing class
    card.classList.add('editing');
    editingQuestions.add(questionId);
    
    updateSaveAllButton();
}

function cancelEdit(questionId) {
    const card = document.querySelector(`[data-question-id="${questionId}"]`);
    
    // Revert changes by reloading original values
    location.reload(); // Simple approach - you could implement more sophisticated state management
}

function saveQuestion(questionId) {
    const card = document.querySelector(`[data-question-id="${questionId}"]`);
    const formData = new FormData();
    
    // Collect form data
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('_method', 'PUT');
    formData.append('question_text', card.querySelector('.question-text-edit').value);
    formData.append('correct_answer', card.querySelector(`input[name="correct_answer_${questionId}"]:checked`).value);
    
    // Collect options
    const options = [];
    const letters = ['A', 'B', 'C', 'D', 'E'];
    letters.forEach(letter => {
        const optionText = card.querySelector(`.option-text-edit[placeholder="Option ${letter}"]`).value;
        if (optionText.trim()) {
            options.push({
                letter: letter,
                text: optionText
            });
        }
    });
    
    options.forEach((option, index) => {
        formData.append(`options[${index}][letter]`, option.letter);
        formData.append(`options[${index}][text]`, option.text);
    });
    
    // Handle image upload
    const imageInput = card.querySelector('.image-upload');
    if (imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    // Save via AJAX
    fetch(routes.update.replace(':id', questionId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Exit edit mode
            exitEditMode(questionId);
            // Update display with new values
            updateQuestionDisplay(questionId, data.question);
            alert('Question saved successfully!');
        } else {
            alert(data.message || 'Save failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving');
    });
}

function exitEditMode(questionId) {
    const card = document.querySelector(`[data-question-id="${questionId}"]`);
    
    // Toggle display elements back
    card.querySelectorAll('.question-text-display').forEach(el => el.style.display = 'block');
    card.querySelectorAll('.question-text-edit').forEach(el => el.style.display = 'none');
    card.querySelectorAll('.option-text-display').forEach(el => el.style.display = 'block');
    card.querySelectorAll('.option-text-edit').forEach(el => el.style.display = 'none');
    card.querySelectorAll('.correct-answer-radio').forEach(el => el.disabled = true);
    card.querySelectorAll('.btn-upload').forEach(el => el.style.display = 'none');
    card.querySelectorAll('.question-actions').forEach(el => el.style.display = 'none');
    
    // Remove editing class
    card.classList.remove('editing');
    editingQuestions.delete(questionId);
    changedQuestions.delete(questionId);
    
    updateSaveAllButton();
}

function updateSaveAllButton() {
    const saveAllBtn = document.getElementById('saveAllBtn');
    saveAllBtn.style.display = editingQuestions.size > 0 ? 'inline-flex' : 'none';
}

// Inline Editing Functions
function enableInlineEdit(cellContent, field, questionId) {
    const displayMode = cellContent.querySelector('.display-mode');
    const editMode = cellContent.querySelector('.edit-mode');
    
    if (editMode.style.display === 'none') {
        displayMode.style.display = 'none';
        editMode.style.display = 'block';
        editMode.focus();
        
        // Select all text for easy editing
        if (editMode.tagName === 'TEXTAREA') {
            editMode.select();
        } else {
            editMode.select();
        }
    }
}

function saveInlineEdit(editElement, field, questionId) {
    const cellContent = editElement.parentElement;
    const displayMode = cellContent.querySelector('.display-mode');
    const newValue = editElement.value.trim();
    
    if (newValue !== displayMode.textContent.trim()) {
        // Save to server
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('field', field);
        formData.append('value', newValue);
        
        fetch(routes.updateField.replace(':id', questionId), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMode.textContent = newValue;
                showSuccessMessage('Field updated successfully');
            } else {
                showErrorMessage(data.message || 'Update failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('An error occurred while updating');
        });
    }
    
    // Switch back to display mode
    displayMode.style.display = 'block';
    editElement.style.display = 'none';
}

function handleInlineKeydown(event, element) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        element.blur(); // This will trigger the save
    } else if (event.key === 'Escape') {
        // Cancel edit
        const cellContent = element.parentElement;
        const displayMode = cellContent.querySelector('.display-mode');
        displayMode.style.display = 'block';
        element.style.display = 'none';
        // Reset value
        element.value = displayMode.textContent;
    }
}

// Image Upload Functions - Removed duplicates, using enhanced versions below

// Utility Functions
function showSuccessMessage(message) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-success';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showErrorMessage(message) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-error';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Add New Question
function addNewQuestion() {
    window.location.href = '{{ route("admin.questions.create", $subject->id) }}';
}

// Delete Functions
function deleteQuestion(questionId) {
    if (!confirm('Are you sure you want to delete this question?')) return;
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('_method', 'DELETE');
    
    fetch(routes.destroy.replace(':id', questionId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-question-id="${questionId}"]`).remove();
            alert('Question deleted successfully!');
        } else {
            alert(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting');
    });
}

function deleteSelected() {
    const selected = Array.from(document.querySelectorAll('.question-select:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Please select questions to delete');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${selected.length} question(s)?`)) return;
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    selected.forEach(id => formData.append('question_ids[]', id));
    
    fetch('{{ route("admin.questions.bulkDestroy", $subject->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selected.forEach(id => {
                document.querySelector(`[data-question-id="${id}"]`).remove();
            });
            updateSelectedCount();
            alert(data.message);
        } else {
            alert(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting');
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    
    // Close modal when clicking outside
    document.getElementById('bulkUploadModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeBulkUpload();
        }
    });
});

// Add New Row Function
function addNewRow() {
    const tableBody = document.getElementById('questionsTableBody');
    const newRowId = 'new_' + Date.now();
    
    // Remove empty row if it exists
    const emptyRow = tableBody.querySelector('.empty-row');
    if (emptyRow) {
        emptyRow.remove();
    }
    
    const newRow = document.createElement('tr');
    newRow.className = 'question-row';
    newRow.setAttribute('data-question-id', newRowId);
    newRow.setAttribute('data-is-new', 'true');
    
    newRow.innerHTML = `
        <td class="col-select">
            <input type="checkbox" class="question-select" value="${newRowId}">
        </td>
        <td class="col-number">New</td>
        <td class="col-question">
            <div class="cell-content">
                <textarea class="edit-mode" rows="3" placeholder="Enter question text..." style="display: block;"></textarea>
            </div>
        </td>
        <td class="col-image">
            <div class="image-cell">
                <div class="no-image-placeholder">
                    <i class="fas fa-image"></i>
                </div>
                <input type="file" class="image-input" accept="image/*" style="display: none;">
                <button type="button" class="image-btn" onclick="triggerImageUpload('${newRowId}')">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
        </td>
        <td class="col-option">
            <div class="cell-content">
                <input type="text" class="edit-mode" placeholder="Option A" style="display: block;">
            </div>
        </td>
        <td class="col-option">
            <div class="cell-content">
                <input type="text" class="edit-mode" placeholder="Option B" style="display: block;">
            </div>
        </td>
        <td class="col-option">
            <div class="cell-content">
                <input type="text" class="edit-mode" placeholder="Option C" style="display: block;">
            </div>
        </td>
        <td class="col-option">
            <div class="cell-content">
                <input type="text" class="edit-mode" placeholder="Option D" style="display: block;">
            </div>
        </td>
        <td class="col-option">
            <div class="cell-content">
                <input type="text" class="edit-mode" placeholder="Option E" style="display: block;">
            </div>
        </td>
        <td class="col-correct">
            <select class="correct-answer-select">
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
            </select>
        </td>
        <td class="col-actions">
            <div class="action-buttons">
                <button type="button" class="btn-save" onclick="saveNewRow(this)" title="Save">
                    <i class="fas fa-save"></i>
                </button>
                <button type="button" class="btn-cancel" onclick="cancelNewRow(this)" title="Cancel">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </td>
    `;
    
    tableBody.appendChild(newRow);
    newRow.classList.add('editing');
    
    // Focus on question text
    const questionTextarea = newRow.querySelector('textarea');
    questionTextarea.focus();
    
    updateSaveAllButton();
}

// Save New Row Function
function saveNewRow(button) {
    const row = button.closest('tr');
    const questionText = row.querySelector('textarea').value.trim();
    const correctAnswer = row.querySelector('.correct-answer-select').value;
    
    // Get options
    const optionInputs = row.querySelectorAll('.col-option input');
    const options = [];
    const letters = ['A', 'B', 'C', 'D', 'E'];
    
    optionInputs.forEach((input, index) => {
        const text = input.value.trim();
        if (text) {
            options.push({
                letter: letters[index],
                text: text
            });
        }
    });
    
    // Validation
    if (!questionText) {
        alert('Question text is required');
        return;
    }
    
    if (options.length < 2) {
        alert('At least 2 options are required');
        return;
    }
    
    const correctOptionExists = options.some(opt => opt.letter === correctAnswer);
    if (!correctOptionExists) {
        alert('Correct answer must correspond to an option with text');
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('question_text', questionText);
    formData.append('correct_answer', correctAnswer);
    
    options.forEach((option, index) => {
        formData.append(`options[${index}][letter]`, option.letter);
        formData.append(`options[${index}][text]`, option.text);
    });
    
    // Handle image if uploaded
    const imageInput = row.querySelector('.image-input');
    if (imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    // Save via AJAX
    const saveBtn = button;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    saveBtn.disabled = true;
    
    fetch('{{ route("admin.questions.store", $subject->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Question created successfully!');
            window.location.reload(); // Reload to show the new question properly
        } else {
            alert(data.message || 'Save failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Cancel New Row Function
function cancelNewRow(button) {
    const row = button.closest('tr');
    row.remove();
    
    // Check if table is empty and add empty row
    const tableBody = document.getElementById('questionsTableBody');
    if (tableBody.children.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.className = 'empty-row';
        emptyRow.innerHTML = `
            <td colspan="11" class="text-center" style="padding: 3rem;">
                <i class="fas fa-table fa-3x mb-3" style="color: #9ca3af;"></i>
                <p style="color: #6b7280;">No questions yet. Click "Bulk Upload" to import or "Add Row" to create manually.</p>
            </td>
        `;
        tableBody.appendChild(emptyRow);
    }
    
    updateSaveAllButton();
}

// Add New Question Function (for the main Add Question button)
function addNewQuestion() {
    addNewRow();
}

// Save All Changes Function
function saveAllChanges() {
    const editingRows = document.querySelectorAll('.question-row.editing');
    if (editingRows.length === 0) {
        alert('No changes to save');
        return;
    }
    
    let savedCount = 0;
    const totalRows = editingRows.length;
    
    editingRows.forEach(row => {
        const isNew = row.getAttribute('data-is-new') === 'true';
        if (isNew) {
            const saveBtn = row.querySelector('.btn-save');
            if (saveBtn) {
                saveNewRow(saveBtn);
            }
        } else {
            const saveBtn = row.querySelector('.btn-save');
            if (saveBtn) {
                saveRow(saveBtn);
            }
        }
    });
}

// Trigger Image Upload Function
function triggerImageUpload(questionId) {
    const row = document.querySelector(`[data-question-id="${questionId}"]`);
    const imageInput = row.querySelector('.image-input');
    imageInput.click();
}

// Handle Image Upload Function (Enhanced with validation)
function handleImageUpload(input, questionId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, JPG, GIF)');
            input.value = '';
            return;
        }
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Image file size must be less than 2MB');
            input.value = '';
            return;
        }
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('image', file);
        
        // Show loading state
        const imageCell = input.closest('.image-cell');
        const originalContent = imageCell.innerHTML;
        imageCell.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
        
        fetch(routes.updateImage.replace(':id', questionId), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update image display with cache busting
                const imageUrl = data.image_url + '?t=' + Date.now();
                imageCell.innerHTML = `
                    <img src="${imageUrl}" alt="Question Image" class="cell-image" onclick="openImageModal(${questionId}, '${imageUrl}')">
                    <input type="file" class="image-input" accept="image/*" style="display: none;" onchange="handleImageUpload(this, ${questionId})">
                    <button type="button" class="image-btn" onclick="triggerImageUpload(${questionId})">
                        <i class="fas fa-camera"></i>
                    </button>
                `;
                showSuccessMessage('Image updated successfully');
                console.log('Image upload debug:', data.debug);
                
                // Clear the file input to allow re-uploading the same file if needed
                input.value = '';
            } else {
                imageCell.innerHTML = originalContent;
                showErrorMessage(data.message || 'Image upload failed');
                console.error('Image upload failed:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            imageCell.innerHTML = originalContent;
            showErrorMessage('An error occurred while uploading image');
        });
    }
}

// Update Save All Button visibility
function updateSaveAllButton() {
    const editingRows = document.querySelectorAll('.question-row.editing');
    const saveAllBtn = document.getElementById('saveAllChangesBtn');
    if (saveAllBtn) {
        saveAllBtn.style.display = editingRows.length > 0 ? 'inline-flex' : 'none';
    }
}

// Missing functions for row editing
function toggleRowEdit(button) {
    const row = button.closest('tr');
    const questionId = row.getAttribute('data-question-id');
    
    // Toggle edit mode
    row.classList.add('editing');
    
    // Show/hide buttons
    row.querySelector('.btn-edit').style.display = 'none';
    row.querySelector('.btn-save').style.display = 'inline-flex';
    row.querySelector('.btn-cancel').style.display = 'inline-flex';
    
    // Enable inline editing for all cells
    row.querySelectorAll('.cell-content').forEach(cell => {
        const displayMode = cell.querySelector('.display-mode');
        const editMode = cell.querySelector('.edit-mode');
        if (displayMode && editMode) {
            displayMode.style.display = 'none';
            editMode.style.display = 'block';
            if (editMode.tagName === 'TEXTAREA') {
                editMode.focus();
            }
        }
    });
    
    updateSaveAllButton();
}

function saveRow(button) {
    const row = button.closest('tr');
    const questionId = row.getAttribute('data-question-id');
    
    // Collect data from the row
    const questionText = row.querySelector('.col-question .edit-mode').value;
    const correctAnswer = row.querySelector('.correct-answer-select').value;
    
    // Get options
    const optionInputs = row.querySelectorAll('.col-option .edit-mode');
    const options = [];
    const letters = ['A', 'B', 'C', 'D', 'E'];
    
    optionInputs.forEach((input, index) => {
        const text = input.value.trim();
        if (text) {
            options.push({
                letter: letters[index],
                text: text
            });
        }
    });
    
    // Validation
    if (!questionText.trim()) {
        alert('Question text is required');
        return;
    }
    
    if (options.length < 2) {
        alert('At least 2 options are required');
        return;
    }
    
    const correctOptionExists = options.some(opt => opt.letter === correctAnswer);
    if (!correctOptionExists) {
        alert('Correct answer must correspond to an option with text');
        return;
    }
    
    // Save via AJAX (using existing update logic)
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('_method', 'PUT');
    formData.append('question_text', questionText);
    formData.append('correct_answer', correctAnswer);
    
    options.forEach((option, index) => {
        formData.append(`options[${index}][letter]`, option.letter);
        formData.append(`options[${index}][text]`, option.text);
    });
    
    const saveBtn = button;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    saveBtn.disabled = true;
    
    fetch(routes.update.replace(':id', questionId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Exit edit mode
            cancelRowEdit(button, false);
            // Update display values
            updateRowDisplay(row, data.question);
            alert('Question updated successfully!');
        } else {
            alert(data.message || 'Save failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving');
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function cancelRowEdit(button, confirm = true) {
    if (confirm && !window.confirm('Cancel changes? Any unsaved changes will be lost.')) {
        return;
    }
    
    const row = button.closest('tr');
    
    // Exit edit mode
    row.classList.remove('editing');
    
    // Show/hide buttons
    row.querySelector('.btn-edit').style.display = 'inline-flex';
    row.querySelector('.btn-save').style.display = 'none';
    row.querySelector('.btn-cancel').style.display = 'none';
    
    // Restore display mode
    row.querySelectorAll('.cell-content').forEach(cell => {
        const displayMode = cell.querySelector('.display-mode');
        const editMode = cell.querySelector('.edit-mode');
        if (displayMode && editMode) {
            displayMode.style.display = 'block';
            editMode.style.display = 'none';
        }
    });
    
    updateSaveAllButton();
}

function deleteRow(button) {
    const row = button.closest('tr');
    const questionId = row.getAttribute('data-question-id');
    
    if (!confirm('Are you sure you want to delete this question?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('_method', 'DELETE');
    
    fetch(routes.destroy.replace(':id', questionId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            row.remove();
            alert('Question deleted successfully!');
            
            // Check if table is empty
            const tableBody = document.getElementById('questionsTableBody');
            if (tableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.className = 'empty-row';
                emptyRow.innerHTML = `
                    <td colspan="11" class="text-center" style="padding: 3rem;">
                        <i class="fas fa-table fa-3x mb-3" style="color: #9ca3af;"></i>
                        <p style="color: #6b7280;">No questions yet. Click "Bulk Upload" to import or "Add Row" to create manually.</p>
                    </td>
                `;
                tableBody.appendChild(emptyRow);
            }
        } else {
            alert(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting');
    });
}

function updateRowDisplay(row, questionData) {
    // Update question text
    const questionDisplay = row.querySelector('.col-question .display-mode');
    if (questionDisplay) {
        questionDisplay.textContent = questionData.question_text.substring(0, 100) + (questionData.question_text.length > 100 ? '...' : '');
    }
    
    // Update options
    const letters = ['A', 'B', 'C', 'D', 'E'];
    letters.forEach(letter => {
        const optionDisplay = row.querySelector(`.col-option .display-mode`);
        const option = questionData.options.find(opt => opt.letter === letter);
        if (optionDisplay) {
            optionDisplay.textContent = option ? (option.text.substring(0, 50) + (option.text.length > 50 ? '...' : '')) : '';
        }
    });
}

function openImageModal(questionId, imageUrl) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Question Image</h3>
                <button type="button" onclick="this.closest('.modal').remove()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <img src="${imageUrl}" alt="Question Image" style="max-width: 100%; max-height: 400px; border-radius: 0.5rem;">
                <div style="margin-top: 1rem;">
                    <button type="button" onclick="triggerImageUpload(${questionId}); this.closest('.modal').remove();" class="btn btn-primary">
                        <i class="fas fa-camera"></i> Change Image
                    </button>
                    <button type="button" onclick="deleteImage(${questionId}); this.closest('.modal').remove();" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Image
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function deleteImage(questionId) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch(routes.deleteImage.replace(':id', questionId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update image display
            const imageCell = document.querySelector(`[data-question-id="${questionId}"] .image-cell`);
            imageCell.innerHTML = `
                <div class="no-image-placeholder" onclick="triggerImageUpload(${questionId})">
                    <i class="fas fa-image"></i>
                </div>
                <input type="file" class="image-input" accept="image/*" style="display: none;" onchange="handleImageUpload(this, ${questionId})">
                <button type="button" class="image-btn" onclick="triggerImageUpload(${questionId})">
                    <i class="fas fa-camera"></i>
                </button>
            `;
            alert('Image deleted successfully!');
        } else {
            alert(data.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting image');
    });
}

// Initialize event listeners for checkboxes
document.addEventListener('DOMContentLoaded', function() {
    // Update selected count when checkboxes change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-select')) {
            updateSelectedCount();
        }
    });
    
    // Initialize selected count
    updateSelectedCount();
});
</script>
@endpush
