<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Option;
// Potentially other models if needed for context
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index(Subject $subject)
    {
        $questions = Question::with('options')
            ->where('subject_id', $subject->id)
            ->orderBy('id')
            ->paginate(10);
        return view('admin.questions.index', compact('subject', 'questions'));
    }

    public function create(Subject $subject)
    {
        $formOptions = [
            ['id' => null, 'letter' => 'A', 'text' => ''],
            ['id' => null, 'letter' => 'B', 'text' => '']
        ];
        return view('admin.questions.create', compact('subject', 'formOptions'));
    }

    public function store(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:65000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:max_width=2000,max_height=2000',
            'correct_answer' => 'required|string|in:A,B,C,D,E',
            'options' => 'required|array|min:2|max:5',
            'options.*.letter' => ['required', 'string', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'options.*.text' => 'required|string|max:1000',
        ]);

        $submittedOptionLetters = collect($validated['options'])->pluck('letter');
        if ($submittedOptionLetters->count() !== $submittedOptionLetters->unique()->count()) {
            return back()->withInput()->withErrors(['options' => 'Option letters must be unique for this question.']);
        }
        if (!in_array($validated['correct_answer'], $submittedOptionLetters->all())) {
            return back()->withInput()->withErrors(['correct_answer' => 'The correct answer must correspond to one of the provided option letters that has text.']);
        }

        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            
            // Additional security checks
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return back()->withInput()->withErrors(['image' => 'Invalid file type. Only JPEG and PNG images are allowed.']);
            }
            
            // Generate secure filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('question_images', $filename, 'public');
        }

        DB::beginTransaction();
        try {
            $question = $subject->questions()->create([
                'question_text' => $validated['question_text'],
                'image_path' => $imagePath,
                'correct_answer' => $validated['correct_answer'],
            ]);

            foreach ($validated['options'] as $optionData) {
                if (!empty(trim($optionData['text']))) {
                    $question->options()->create([
                        'option_letter' => $optionData['letter'],
                        'option_text' => $optionData['text'],
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return back()->withInput()->with('error', 'Failed to create question: ' . $e->getMessage());
        }
        return redirect()->route('admin.questions.index', $subject->id)->with('success', 'Question added successfully.');
    }

    // THIS IS THE METHOD THAT IS LIKELY MISSING OR MISNAMED
    public function edit(Question $question)
    {
        $subject = $question->subject; // Get the parent subject
        $question->load('options');   // Eager load the options for this question

        // Prepare $formOptions to pass to the view, mapping existing options
        // This ensures the view has a consistent structure to loop through
        $formOptions = $question->options->map(function ($opt, $index) {
            return ['id' => $opt->id, 'letter' => $opt->option_letter, 'text' => $opt->option_text];
        })->toArray();

        // If you want to ensure there are always, e.g., at least 2 blank or up to 5 total fields for JS to work with
        // This part might be more for the `create` view or handled purely by JS for edit.
        // For edit, usually, you just show the existing options. JS handles adding more if < 5.
        // $minDisplayOptions = $question->options->count(); // Show at least existing
        // $maxTotalOptions = 5;
        // while(count($formOptions) < $maxTotalOptions && count($formOptions) < $minDisplayOptions + (5 - $minDisplayOptions) ) {
        //    // This logic for padding empty options might be too complex here and better handled in JS if always showing 5 slots.
        //    // For now, just pass what exists.
        // }

        return view('admin.questions.edit', compact('question', 'subject', 'formOptions'));
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:65000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:max_width=2000,max_height=2000',
            'remove_image' => 'nullable|boolean',
            'correct_answer' => 'required|string|in:A,B,C,D,E',
            'options' => 'required|array|min:2|max:5',
            'options.*.letter' => ['required', 'string', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'options.*.text' => 'required|string|max:1000',
            'option_ids.*' => 'nullable|integer',
        ]);
        
        // Handle AJAX requests
        if ($request->ajax()) {
            return $this->updateAjax($request, $question, $validated);
        }

        $submittedOptionLetters = collect($validated['options'])->pluck('letter');
        if ($submittedOptionLetters->count() !== $submittedOptionLetters->unique()->count()) {
            return back()->withInput()->withErrors(['options' => 'Option letters must be unique.']);
        }
        if (!in_array($validated['correct_answer'], $submittedOptionLetters->all())) {
            return back()->withInput()->withErrors(['correct_answer' => 'Correct answer must be one of the provided option letters.']);
        }

        $imagePath = $question->image_path;
        if ($request->boolean('remove_image') && $imagePath) {
            if (Storage::disk('public')->exists($imagePath))
                Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            
            // Additional security checks
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return back()->withInput()->withErrors(['image' => 'Invalid file type. Only JPEG and PNG images are allowed.']);
            }
            
            if ($imagePath && Storage::disk('public')->exists($imagePath))
                Storage::disk('public')->delete($imagePath);
                
            // Generate secure filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('question_images', $filename, 'public');
        }

        DB::beginTransaction();
        try {
            $question->update([
                'question_text' => $validated['question_text'],
                'image_path' => $imagePath,
                'correct_answer' => $validated['correct_answer'],
            ]);

            $currentOptionIdsInDb = $question->options()->pluck('id')->toArray();
            $submittedOptionIdsFromForm = [];

            foreach ($request->input('options', []) as $index => $optionData) {
                if (empty(trim($optionData['text'])))
                    continue;

                $existingOptionId = $request->input("option_ids.{$index}");

                if ($existingOptionId) {
                    $option = Option::find($existingOptionId);
                    if ($option && $option->question_id == $question->id) {
                        $option->update([
                            'option_letter' => $optionData['letter'],
                            'option_text' => $optionData['text']
                        ]);
                        $submittedOptionIdsFromForm[] = (int) $existingOptionId;
                    }
                } else {
                    $newOption = $question->options()->create([
                        'option_letter' => $optionData['letter'],
                        'option_text' => $optionData['text']
                    ]);
                    $submittedOptionIdsFromForm[] = $newOption->id;
                }
            }
            $optionsToDelete = array_diff($currentOptionIdsInDb, $submittedOptionIdsFromForm);
            if (!empty($optionsToDelete))
                Option::destroy($optionsToDelete);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update question: ' . $e->getMessage());
        }
        return redirect()->route('admin.questions.index', $question->subject_id)->with('success', 'Question updated.');
    }
    
    private function updateAjax(Request $request, Question $question, array $validated)
    {
        $submittedOptionLetters = collect($validated['options'])->pluck('letter');
        if ($submittedOptionLetters->count() !== $submittedOptionLetters->unique()->count()) {
            return response()->json(['success' => false, 'message' => 'Option letters must be unique.']);
        }
        if (!in_array($validated['correct_answer'], $submittedOptionLetters->all())) {
            return response()->json(['success' => false, 'message' => 'Correct answer must be one of the provided option letters.']);
        }

        $imagePath = $question->image_path;
        if ($request->boolean('remove_image') && $imagePath) {
            if (Storage::disk('public')->exists($imagePath))
                Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            
            // Additional security checks
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return response()->json(['success' => false, 'message' => 'Invalid file type. Only JPEG and PNG images are allowed.']);
            }
            
            if ($imagePath && Storage::disk('public')->exists($imagePath))
                Storage::disk('public')->delete($imagePath);
                
            // Generate secure filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('question_images', $filename, 'public');
        }

        DB::beginTransaction();
        try {
            $question->update([
                'question_text' => $validated['question_text'],
                'image_path' => $imagePath,
                'correct_answer' => $validated['correct_answer'],
            ]);

            $currentOptionIdsInDb = $question->options()->pluck('id')->toArray();
            $submittedOptionIdsFromForm = [];

            foreach ($request->input('options', []) as $index => $optionData) {
                if (empty(trim($optionData['text'])))
                    continue;

                $existingOptionId = $request->input("option_ids.{$index}");

                if ($existingOptionId) {
                    $option = Option::find($existingOptionId);
                    if ($option && $option->question_id == $question->id) {
                        $option->update([
                            'option_letter' => $optionData['letter'],
                            'option_text' => $optionData['text']
                        ]);
                        $submittedOptionIdsFromForm[] = (int) $existingOptionId;
                    }
                } else {
                    $newOption = $question->options()->create([
                        'option_letter' => $optionData['letter'],
                        'option_text' => $optionData['text']
                    ]);
                    $submittedOptionIdsFromForm[] = $newOption->id;
                }
            }
            $optionsToDelete = array_diff($currentOptionIdsInDb, $submittedOptionIdsFromForm);
            if (!empty($optionsToDelete))
                Option::destroy($optionsToDelete);

            DB::commit();
            
            // Return updated question data
            $question->load('options');
            return response()->json([
                'success' => true, 
                'message' => 'Question updated successfully.',
                'question' => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'image_path' => $question->image_path,
                    'correct_answer' => $question->correct_answer,
                    'options' => $question->options->map(function($option) {
                        return [
                            'letter' => $option->option_letter,
                            'text' => $option->option_text
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update question: ' . $e->getMessage()]);
        }
    }


    public function destroy(Question $question)
    {
        $subjectId = $question->subject_id; // Store subject_id before deletion
        
        DB::beginTransaction();
        try {
            if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
                Storage::disk('public')->delete($question->image_path);
            }
            $question->options()->delete();
            $question->delete();
            DB::commit();
            
            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Question deleted successfully.']);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error deleting question: ' . $e->getMessage()]);
            }
            
            return redirect()->route('admin.questions.index', $subjectId)->with('error', 'Error deleting question: ' . $e->getMessage());
        }
        
        return redirect()->route('admin.questions.index', $subjectId)->with('success', 'Question deleted successfully.');
    }

    public function bulkDestroy(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'integer|exists:questions,id', // Ensure IDs are valid questions
        ]);

        $questionIdsToDelete = $validated['question_ids'];
        $deletedCount = 0;

        if (empty($questionIdsToDelete)) {
            return response()->json(['success' => false, 'message' => 'No questions were selected for deletion.']);
        }

        DB::beginTransaction();
        try {
            // Ensure questions belong to the specified subject for security/consistency
            $questions = Question::whereIn('id', $questionIdsToDelete)
                ->where('subject_id', $subject->id)
                ->get();

            foreach ($questions as $question) {
                if ($question->image_path) {
                    Storage::disk('public')->delete($question->image_path);
                }
                $question->options()->delete(); // Delete related options
                $question->delete();            // Delete the question itself
                $deletedCount++;
            }
            DB::commit();

            if ($deletedCount > 0) {
                return response()->json(['success' => true, 'message' => $deletedCount . ' question(s) deleted successfully.']);
            } else {
                return response()->json(['success' => false, 'message' => 'No matching questions found for deletion under this subject, or they were already deleted.']);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Bulk question deletion error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while deleting questions: ' . $e->getMessage()], 500);
        }
    }

    public function showBulkUploadForm(Subject $subject)
    {
        return view('admin.questions.bulk_upload_form', compact('subject'));
    }

    public function processBulkUpload(Request $request, Subject $subject)
    {
        // Validation for the uploaded file itself
        $request->validate([
            'questions_csv' => 'required|file|mimes:csv,txt|max:2048', // Max 2MB for security
        ], [
            'questions_csv.required' => 'The CSV file field is required.',
            'questions_csv.mimes' => 'The file must be a CSV or TXT file.',
            'questions_csv.max' => 'The CSV file may not be greater than 2MB.',
        ]);

        $file = $request->file('questions_csv');
        
        // Additional security checks
        $allowedMimes = ['text/csv', 'text/plain', 'application/csv'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return redirect()->route('admin.questions.bulkUploadForm', $subject->id)
                ->with('error', 'Invalid file type. Only CSV files are allowed.');
        }

        $filePath = $file->getRealPath();

        if (!($fileHandle = fopen($filePath, "r"))) {
            return redirect()->route('admin.questions.bulkUploadForm', $subject->id)->with('error', 'Could not open the uploaded CSV file.');
        }

        $csvHeader = fgetcsv($fileHandle); // Read the first row as header
        if (!$csvHeader) {
            fclose($fileHandle);
            return redirect()->route('admin.questions.bulkUploadForm', $subject->id)->with('error', 'CSV file is empty or header row is missing.');
        }
        $header = array_map('trim', array_map('strtolower', $csvHeader));

        // Define expected base headers (case-insensitive check)
        $expectedBaseHeaders = ['question_text', 'correct_answer'];
        // Check for at least two option text columns (e.g., option_a_text, option_b_text)
        $optionTextColumnCount = 0;
        foreach ($header as $h) {
            if (Str::startsWith($h, 'option_') && Str::endsWith($h, '_text')) {
                $optionTextColumnCount++;
            }
        }

        $missingBaseHeaders = array_diff($expectedBaseHeaders, $header);
        if (!empty($missingBaseHeaders) || $optionTextColumnCount < 2) {
            fclose($fileHandle);
            $missingStr = !empty($missingBaseHeaders) ? " Missing base headers: " . implode(', ', $missingBaseHeaders) . "." : "";
            $optionStr = $optionTextColumnCount < 2 ? " At least two option text columns (e.g., option_a_text, option_b_text) are required." : "";
            return redirect()->route('admin.questions.bulkUploadForm', $subject->id)
                ->with('error', 'CSV header mismatch.' . $missingStr . $optionStr . ' Found headers: ' . implode(', ', $header));
        }

        $importedCount = 0;
        $errors = [];
        $rowNumber = 1; // Header is row 1

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($fileHandle)) !== FALSE) {
                $rowNumber++;
                if (count($row) !== count($header)) {
                    $errors[] = "Row {$rowNumber}: Column count mismatch. Expected " . count($header) . ", got " . count($row) . ". Data: [" . implode(', ', $row) . "]";
                    continue; // Skip this malformed row
                }
                $rowData = array_combine($header, $row); // Create associative array

                $questionText = trim($rowData['question_text'] ?? '');
                $correctAnswerLetter = strtoupper(trim($rowData['correct_answer'] ?? ''));
                $imageFilename = trim($rowData['image_filename'] ?? null); // Optional image filename

                $currentOptions = [];
                $possibleOptionLetters = ['A', 'B', 'C', 'D', 'E'];
                foreach ($possibleOptionLetters as $letter) {
                    $optionTextKey = 'option_' . strtolower($letter) . '_text';
                    if (isset($rowData[$optionTextKey]) && trim($rowData[$optionTextKey]) !== '') {
                        $currentOptions[] = [
                            'letter' => $letter,
                            'text' => trim($rowData[$optionTextKey])
                        ];
                    }
                }

                // Validate parsed row data
                $rowValidator = Validator::make([
                    'question_text' => $questionText,
                    'correct_answer' => $correctAnswerLetter,
                    'options' => $currentOptions,
                    'image_filename' => $imageFilename
                ], [
                    'question_text' => 'required|string|max:65000',
                    'correct_answer' => ['required', Rule::in(array_column($currentOptions, 'letter'))], // Correct answer must be one of the provided option letters
                    'options' => 'required|array|min:2|max:5', // Ensure at least 2 options were parsed
                    'options.*.text' => 'required|string|max:1000', // Validate text of each parsed option
                    'image_filename' => 'nullable|string|max:255' // Example validation if you handle images
                ], [
                    'correct_answer.in' => "Correct answer letter '{$correctAnswerLetter}' is not among the provided option letters for this question.",
                    'options.min' => 'At least two valid options (with text) are required for the question.'
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = "Row {$rowNumber} (Question: '" . Str::limit($questionText, 30) . "'): " . implode('; ', $rowValidator->errors()->all());
                    continue; // Skip this row if validation fails
                }

                $imagePath = null;
                if ($imageFilename) {
                    // Implement logic to find/move image based on $imageFilename
                    // For example, if images are pre-uploaded to 'public/uploads/bulk_question_images/'
                    // $potentialPath = 'bulk_question_images/' . $imageFilename;
                    // if (Storage::disk('public')->exists($potentialPath)) {
                    //    $imagePath = $potentialPath;
                    // } else {
                    //    $errors[] = "Row {$rowNumber}: Image '{$imageFilename}' not found in designated upload folder.";
                    //    // Decide if to skip or proceed without image
                    // }
                }

                $question = $subject->questions()->create([
                    'question_text' => $questionText,
                    'image_path' => $imagePath,
                    'correct_answer' => $correctAnswerLetter,
                ]);

                foreach ($currentOptions as $optData) {
                    $question->options()->create([
                        'option_letter' => $optData['letter'],
                        'option_text' => $optData['text'],
                    ]);
                }
                $importedCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($fileHandle))
                fclose($fileHandle);
            // Log::error("Bulk question upload error for subject {$subject->id}: " . $e->getMessage() . " at line " . $e->getLine() . " for CSV row approx " . $rowNumber);
            return redirect()->route('admin.questions.bulkUploadForm', $subject->id)
                ->with('error', 'An error occurred during bulk upload (around CSV row ' . $rowNumber . '). Please check your file and try again. Details: ' . $e->getMessage());
        } finally {
            if (is_resource($fileHandle))
                fclose($fileHandle);
        }

        $feedbackMessage = "Bulk question upload for '{$subject->name}' processed. Successfully imported: {$importedCount} questions.";
        if (!empty($errors)) {
            $feedbackMessage .= " Some rows had errors and were skipped.";
            session()->flash('bulk_upload_errors_detailed', $errors); // Store detailed errors in session
        }
        
        // Handle AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'success' => true, 
                'message' => $feedbackMessage,
                'imported_count' => $importedCount,
                'errors' => $errors
            ]);
        }
        
        return redirect()->route('admin.questions.index', $subject->id)->with('success', $feedbackMessage);
    }

    public function updateField(Request $request, Question $question)
    {
        $field = $request->input('field');
        $value = $request->input('value');
        
        try {
            if ($field === 'question_text') {
                $question->update(['question_text' => $value]);
            } elseif (str_starts_with($field, 'option_')) {
                $letter = strtoupper(substr($field, -1));
                $option = $question->options()->where('option_letter', $letter)->first();
                
                if ($option) {
                    $option->update(['option_text' => $value]);
                } else {
                    $question->options()->create([
                        'option_letter' => $letter,
                        'option_text' => $value
                    ]);
                }
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateImage(Request $request, Question $question)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            // Log the request for debugging
            \Log::info('Image upload request', [
                'question_id' => $question->id,
                'file_name' => $request->file('image')->getClientOriginalName(),
                'file_size' => $request->file('image')->getSize(),
                'mime_type' => $request->file('image')->getMimeType()
            ]);

            // Delete old image if exists
            if ($question->image_path) {
                if (Storage::disk('public')->exists($question->image_path)) {
                    Storage::disk('public')->delete($question->image_path);
                    \Log::info('Deleted old image', ['path' => $question->image_path]);
                }
            }

            // Store new image with secure filename
            $file = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('question_images', $filename, 'public');
            
            \Log::info('Stored new image', ['path' => $imagePath]);
            
            // Update the question with the new image path
            $question->update(['image_path' => $imagePath]);
            
            // Verify the update was successful
            $question->refresh();
            
            \Log::info('Updated question', [
                'question_id' => $question->id,
                'new_image_path' => $question->image_path
            ]);

            $imageUrl = asset('storage/' . $imagePath);
            $fileExists = Storage::disk('public')->exists($imagePath);
            
            \Log::info('Image upload complete', [
                'image_url' => $imageUrl,
                'file_exists' => $fileExists
            ]);

            // Double-check that the database was actually updated
            $freshQuestion = Question::find($question->id);
            
            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'image_path' => $imagePath,
                'debug' => [
                    'question_id' => $question->id,
                    'saved_path' => $question->image_path,
                    'fresh_saved_path' => $freshQuestion->image_path,
                    'file_exists' => $fileExists,
                    'storage_path' => storage_path('app/public/' . $imagePath),
                    'timestamp' => now()->toDateTimeString(),
                    'paths_match' => $question->image_path === $freshQuestion->image_path
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Image upload failed', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteImage(Question $question)
    {
        try {
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
                $question->update(['image_path' => null]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function testImage(Question $question)
    {
        return response()->json([
            'question_id' => $question->id,
            'current_image_path' => $question->image_path,
            'image_exists' => $question->image_path ? Storage::disk('public')->exists($question->image_path) : false,
            'image_url' => $question->image_path ? asset('storage/' . $question->image_path) : null,
            'storage_directory_exists' => Storage::disk('public')->exists('question_images'),
            'storage_directory_writable' => is_writable(storage_path('app/public/question_images')),
            'all_images_in_storage' => Storage::disk('public')->files('question_images')
        ]);
    }
}
