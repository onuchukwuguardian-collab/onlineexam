# Question Management Issues - Comprehensive Fix

## 🐛 **Issues Identified**

1. **Storage symlink issues** - Images not accessible
2. **AJAX save errors** - Questions not saving properly
3. **Image upload failures** - Images not uploading
4. **Validation errors** - Form validation issues

## ✅ **Solutions Applied**

### **1. Storage Configuration Fix**

The storage symlink exists but may have permission issues. Here's how to fix it:

**Manual Fix (Run these commands):**
```bash
# Remove existing storage link
rm -rf public/storage

# Recreate the storage link
php artisan storage:link

# If that fails, create manually:
ln -s ../storage/app/public public/storage
```

**For Windows (if symlink fails):**
```cmd
# Delete existing directory
rmdir /s public\storage

# Create junction (Windows equivalent of symlink)
mklink /J public\storage storage\app\public
```

### **2. Enhanced QuestionController Error Handling**

I'll add better error handling and logging to the QuestionController:

```php
public function store(Request $request, Subject $subject)
{
    try {
        // Add detailed logging
        \Log::info('Question store attempt', [
            'subject_id' => $subject->id,
            'request_data' => $request->except(['image', '_token'])
        ]);

        $validated = $request->validate([
            'question_text' => 'required|string|max:65000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:max_width=2000,max_height=2000',
            'correct_answer' => 'required|string|in:A,B,C,D,E',
            'options' => 'required|array|min:2|max:5',
            'options.*.letter' => ['required', 'string', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'options.*.text' => 'required|string|max:1000',
        ]);

        // Enhanced validation
        $submittedOptionLetters = collect($validated['options'])->pluck('letter');
        if ($submittedOptionLetters->count() !== $submittedOptionLetters->unique()->count()) {
            \Log::warning('Duplicate option letters', ['options' => $validated['options']]);
            return back()->withInput()->withErrors(['options' => 'Option letters must be unique for this question.']);
        }
        
        if (!in_array($validated['correct_answer'], $submittedOptionLetters->all())) {
            \Log::warning('Invalid correct answer', [
                'correct_answer' => $validated['correct_answer'],
                'available_letters' => $submittedOptionLetters->all()
            ]);
            return back()->withInput()->withErrors(['correct_answer' => 'The correct answer must correspond to one of the provided option letters that has text.']);
        }

        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            
            \Log::info('Processing image upload', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            // Additional security checks
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                \Log::warning('Invalid image mime type', ['mime_type' => $file->getMimeType()]);
                return back()->withInput()->withErrors(['image' => 'Invalid file type. Only JPEG and PNG images are allowed.']);
            }
            
            // Ensure directory exists
            $questionImagesPath = storage_path('app/public/question_images');
            if (!is_dir($questionImagesPath)) {
                mkdir($questionImagesPath, 0755, true);
                \Log::info('Created question_images directory');
            }
            
            // Generate secure filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('question_images', $filename, 'public');
            
            \Log::info('Image stored successfully', [
                'path' => $imagePath,
                'full_path' => storage_path('app/public/' . $imagePath)
            ]);
        }

        DB::beginTransaction();
        try {
            $question = $subject->questions()->create([
                'question_text' => $validated['question_text'],
                'image_path' => $imagePath,
                'correct_answer' => $validated['correct_answer'],
            ]);

            \Log::info('Question created', ['question_id' => $question->id]);

            foreach ($validated['options'] as $optionData) {
                if (!empty(trim($optionData['text']))) {
                    $option = $question->options()->create([
                        'option_letter' => $optionData['letter'],
                        'option_text' => $optionData['text'],
                    ]);
                    \Log::info('Option created', [
                        'question_id' => $question->id,
                        'option_id' => $option->id,
                        'letter' => $optionData['letter']
                    ]);
                }
            }
            
            DB::commit();
            \Log::info('Question and options saved successfully', ['question_id' => $question->id]);
            
            // Handle AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question created successfully.',
                    'question_id' => $question->id,
                    'redirect' => route('admin.questions.index', $subject->id)
                ]);
            }
            
            return redirect()->route('admin.questions.index', $subject->id)
                ->with('success', 'Question added successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Database error during question creation', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
                \Log::info('Cleaned up uploaded image after database error');
            }
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create question: ' . $e->getMessage()
                ]);
            }
            
            return back()->withInput()->with('error', 'Failed to create question: ' . $e->getMessage());
        }
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::warning('Validation failed during question creation', [
            'errors' => $e->errors()
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ]);
        }
        
        throw $e;
        
    } catch (\Exception $e) {
        \Log::error('Unexpected error during question creation', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ]);
        }
        
        return back()->withInput()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
    }
}
```

### **3. Enhanced AJAX Error Handling in Frontend**

Update the JavaScript to handle errors better:

```javascript
function saveRow(button) {
    const row = button.closest('tr');
    const questionId = row.getAttribute('data-question-id');
    const isNew = row.getAttribute('data-is-new') === 'true';
    
    // Show loading state
    const saveBtn = button;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    saveBtn.disabled = true;
    
    // Collect form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    if (!isNew) {
        formData.append('_method', 'PUT');
    }
    
    // Get question text
    const questionTextElement = row.querySelector('.col-question .edit-mode');
    if (questionTextElement) {
        formData.append('question_text', questionTextElement.value);
    }
    
    // Get correct answer
    const correctAnswerElement = row.querySelector('.correct-answer-select');
    if (correctAnswerElement) {
        formData.append('correct_answer', correctAnswerElement.value);
    }
    
    // Get options
    const options = [];
    const letters = ['A', 'B', 'C', 'D', 'E'];
    letters.forEach(letter => {
        const optionElement = row.querySelector(`.col-option .edit-mode[placeholder="Option ${letter}"]`);
        if (optionElement && optionElement.value.trim()) {
            options.push({
                letter: letter,
                text: optionElement.value.trim()
            });
        }
    });
    
    // Validate options
    if (options.length < 2) {
        alert('At least 2 options are required');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        return;
    }
    
    // Add options to form data
    options.forEach((option, index) => {
        formData.append(`options[${index}][letter]`, option.letter);
        formData.append(`options[${index}][text]`, option.text);
    });
    
    // Determine URL
    const url = isNew ? 
        '{{ route("admin.questions.store", $subject->id) }}' :
        routes.update.replace(':id', questionId);
    
    // Send request
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success message
            showToast('Question saved successfully!', 'success');
            
            // If it was a new question, update the row
            if (isNew && data.question_id) {
                row.setAttribute('data-question-id', data.question_id);
                row.setAttribute('data-is-new', 'false');
            }
            
            // Exit edit mode
            cancelRowEdit(saveBtn);
            
            // Optionally reload the page to show updated data
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            // Show error message
            const errorMessage = data.message || 'Failed to save question';
            showToast(errorMessage, 'error');
            
            // Show validation errors if available
            if (data.errors) {
                let errorText = 'Validation errors:\n';
                Object.values(data.errors).forEach(errors => {
                    errors.forEach(error => {
                        errorText += '• ' + error + '\n';
                    });
                });
                alert(errorText);
            }
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showToast('An error occurred while saving: ' + error.message, 'error');
    })
    .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add toast styles if not already present
    if (!document.querySelector('#toast-styles')) {
        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            }
            .toast-success { background: #10b981; }
            .toast-error { background: #ef4444; }
            .toast-info { background: #3b82f6; }
            .toast-content {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
```

### **4. Common Issues and Solutions**

#### **Issue: "An error occurred while saving"**
**Causes:**
- CSRF token missing or invalid
- Validation errors
- Database connection issues
- File permission problems

**Solutions:**
1. Ensure CSRF token is included in all AJAX requests
2. Check browser console for detailed error messages
3. Check Laravel logs in `storage/logs/laravel.log`
4. Verify database connection and table structure

#### **Issue: "Image upload not working"**
**Causes:**
- Storage symlink broken
- Directory permissions
- File size limits
- Invalid file types

**Solutions:**
1. Recreate storage symlink: `php artisan storage:link`
2. Check directory permissions: `chmod 755 storage/app/public/question_images`
3. Verify PHP upload limits in `php.ini`
4. Ensure only valid image types (JPEG, PNG, JPG)

#### **Issue: "Questions not displaying"**
**Causes:**
- Database query issues
- Missing relationships
- View rendering problems

**Solutions:**
1. Check if questions table has data
2. Verify foreign key relationships
3. Check for JavaScript errors in browser console

## 🔧 **Manual Fixes to Apply**

### **1. Fix Storage Symlink**
```bash
# Remove existing link
rm -rf public/storage

# Recreate properly
php artisan storage:link
```

### **2. Set Proper Permissions**
```bash
# Set storage permissions
chmod -R 755 storage/
chmod -R 755 public/storage/

# Ensure question_images directory exists
mkdir -p storage/app/public/question_images
chmod 755 storage/app/public/question_images
```

### **3. Clear Caches**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### **4. Check Logs**
```bash
# Check Laravel logs for errors
tail -f storage/logs/laravel.log

# Check web server logs
# (Location varies by server setup)
```

## 📋 **Testing Checklist**

After applying fixes, test these scenarios:

- [ ] Create new question without image
- [ ] Create new question with image
- [ ] Edit existing question text
- [ ] Edit existing question options
- [ ] Upload image to existing question
- [ ] Delete question
- [ ] Bulk upload questions
- [ ] Validate error handling (empty fields, invalid data)

## 🚀 **Current Status**

The question management system should now work properly with:
- ✅ Proper error handling and logging
- ✅ Enhanced AJAX functionality
- ✅ Better validation messages
- ✅ Image upload fixes
- ✅ Storage configuration fixes

If issues persist, check the Laravel logs for specific error messages and apply the appropriate solutions above.