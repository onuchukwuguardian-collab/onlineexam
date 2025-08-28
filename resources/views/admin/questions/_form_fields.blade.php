<div class="mb-4">
    <label for="question_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Question Text <span class="text-red-500">*</span></label>
    <textarea name="question_text" id="question_text" rows="4" required
              class="admin-input mt-1 block w-full @error('question_text') border-red-500 @enderror"
              placeholder="Enter the question text">{{ old('question_text', $question->question_text ?? '') }}</textarea>
    @error('question_text') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Question Image (Optional)</label>
    <input type="file" name="image" id="image" accept="image/*"
           class="admin-file-input mt-1 block w-full @error('image') border-red-500 @enderror">
    @error('image') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

    @if(isset($question) && $question->image_path)
        <div class="mt-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">Current Image:</p>
            <img src="{{ asset('storage/' . $question->image_path) }}" alt="Current Question Image" class="max-w-xs max-h-32 rounded border dark:border-gray-600">
            <label for="remove_image" class="inline-flex items-center mt-2">
                <input type="checkbox" name="remove_image" id="remove_image" value="1" class="form-checkbox admin-input-checkbox h-4 w-4 text-red-600">
                <span class="ml-2 text-sm text-red-600 dark:text-red-400">Remove current image</span>
            </label>
        </div>
    @endif
</div>

<div id="options_container" class="space-y-4 pt-3">
    <h4 class="text-md font-semibold dark:text-gray-200">Options (Minimum 2, Max 5) <span class="text-red-500">*</span></h4>
    @php
        // Use the $formOptions prepared in the controller for edit, or initialize for create
        // $formOptions should be an array of arrays, each with 'id', 'letter', 'text'
        $optionsToDisplay = old('options', $formOptions ?? [
            ['id'=>null, 'letter' => 'A', 'text' => ''], 
            ['id'=>null, 'letter' => 'B', 'text' => '']
        ]);
        $allPossibleLetters = ['A', 'B', 'C', 'D', 'E']; // Make sure this is consistent with JS
    @endphp

    {{-- Loop through existing/old options --}}
    @foreach($optionsToDisplay as $index => $optionData)
    <div class="option-group flex items-center space-x-2 p-3 border dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700">
        {{-- Hidden input for existing option ID, only if it's an existing option --}}
        @if(!empty($optionData['id']))
            <input type="hidden" name="option_ids[{{ $index }}]" value="{{ $optionData['id'] }}">
        @endif
        {{-- Hidden input to help JS identify this as an already rendered option group by PHP --}}
        <input type="hidden" class="php-rendered-option-flag" value="1">


        <div class="w-1/6">
            <label for="options_{{ $index }}_letter" class="sr-only">Letter</label>
            <select name="options[{{ $index }}][letter]" id="options_{{ $index }}_letter" class="admin-input w-full text-center option-letter-select" required>
                @foreach($allPossibleLetters as $letterChoice)
                <option value="{{ $letterChoice }}" 
                    {{ (isset($optionData['letter']) && $optionData['letter'] == $letterChoice) ? 'selected' : '' }}>
                    {{ $letterChoice }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="flex-grow">
            <label for="options_{{ $index }}_text" class="sr-only">Text</label>
            <input type="text" name="options[{{ $index }}][text]" id="options_{{ $index }}_text" 
                   value="{{ $optionData['text'] ?? '' }}" placeholder="Option text" required 
                   class="admin-input w-full">
        </div>
        {{-- Allow removing any option dynamically, JS will enforce min 2 --}}
        <button type="button" class="admin-btn-red remove-option-btn text-xs p-1.5 leading-none" title="Remove this option"><i class="fas fa-times"></i></button>
    </div>
    @error("options.{$index}.letter") <p class="text-sm text-red-500 -mt-3 mb-2 ml-2">{{ $message }}</p> @enderror
    @error("options.{$index}.text") <p class="text-sm text-red-500 -mt-3 mb-2 ml-2">{{ $message }}</p> @enderror
    @endforeach
</div>
<button type="button" id="add_option_btn" class="admin-btn-secondary text-sm mt-2 {{ count($optionsToDisplay) >= 5 ? 'hidden' : '' }}"><i class="fas fa-plus mr-1"></i>Add Option</button>
@error('options') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

<div class="mt-4">
    <label for="correct_answer" class="block text-sm font-medium dark:text-gray-300">Correct Answer Letter <span class="text-red-500">*</span></label>
    <select name="correct_answer" id="correct_answer" required 
            class="mt-1 block w-full md:w-1/3 admin-input @error('correct_answer') border-red-500 @enderror">
        <option value="">-- Select --</option>
        {{-- Dynamically populated by JS --}}
        @php
            // Pre-select correct answer if editing or old input exists
            $selectedCorrectAnswer = old('correct_answer', $question->correct_answer ?? '');
        @endphp
        @foreach($optionsToDisplay as $optionData)
            @if(!empty($optionData['letter']))
            <option value="{{ $optionData['letter'] }}" {{ $selectedCorrectAnswer == $optionData['letter'] ? 'selected' : '' }}>
                {{ $optionData['letter'] }}
            </option>
            @endif
        @endforeach
    </select>
    @error('correct_answer') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
</div>
