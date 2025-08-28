<script>
document.addEventListener('DOMContentLoaded', function () {
    const optionsContainer = document.getElementById('options_container');
    const addOptionBtn = document.getElementById('add_option_btn');
    const correctAnsSelect = document.getElementById('correct_answer');
    const allPossibleLetters = ['A', 'B', 'C', 'D', 'E'];
    
    // Initialize based on already rendered PHP option groups
    let nextGeneratedOptionIndex = optionsContainer.querySelectorAll('.option-group').length; 

    function updateCorrectAnswerOptions() {
        const previouslySelectedCorrectAnswer = correctAnsSelect.value;
        correctAnsSelect.innerHTML = '<option value="">-- Select Correct --</option>'; // Clear and add placeholder
        
        const uniqueLettersInForm = new Set();

        optionsContainer.querySelectorAll('.option-group').forEach(group => {
            const letterSelect = group.querySelector('select[name$="[letter]"]');
            const optionText = group.querySelector('input[name$="[text]"]');

            if (letterSelect && letterSelect.value && optionText && optionText.value.trim() !== '') {
                const currentLetter = letterSelect.value;
                if (!uniqueLettersInForm.has(currentLetter)) {
                    const opt = document.createElement('option');
                    opt.value = currentLetter;
                    opt.textContent = currentLetter;
                    correctAnsSelect.appendChild(opt);
                    uniqueLettersInForm.add(currentLetter);
                }
            }
        });
        
        // Try to re-select the previously selected correct answer if it's still valid
        if (uniqueLettersInForm.has(previouslySelectedCorrectAnswer)) {
            correctAnsSelect.value = previouslySelectedCorrectAnswer;
        } else if (correctAnsSelect.options.length > 1 && '{{ old("correct_answer", $question->correct_answer ?? "") }}' && uniqueLettersInForm.has('{{ old("correct_answer", $question->correct_answer ?? "") }}')) {
            // Fallback to old input or existing question's correct answer if still valid
             correctAnsSelect.value = '{{ old("correct_answer", $question->correct_answer ?? "") }}';
        }
    }
    
    function attachEventListenersToOptionGroup(groupElement) {
        const letterSelect = groupElement.querySelector('select[name$="[letter]"]');
        const textInput = groupElement.querySelector('input[name$="[text]"]');

        if (letterSelect) {
            letterSelect.addEventListener('change', function() {
                const currentLetter = this.value;
                let duplicateCount = 0;
                optionsContainer.querySelectorAll('.option-group select[name$="[letter]"]').forEach(otherSelect => {
                    if (otherSelect.value === currentLetter) {
                        duplicateCount++;
                    }
                });
                if (duplicateCount > 1 && currentLetter !== "") {
                    alert(`Option letter "${currentLetter}" is already in use. Please select a unique letter.`);
                    // Attempt to set to the first truly available letter to avoid cascade errors
                    this.value = getNextAvailableLetterForNewOption(Array.from(optionsContainer.querySelectorAll('.option-group select[name$="[letter]"]')).filter(s => s !== this).map(s => s.value)) || '';
                }
                updateCorrectAnswerOptions();
            });
        }
        if (textInput) {
            textInput.addEventListener('input', updateCorrectAnswerOptions); // Update if text changes
            textInput.addEventListener('blur', updateCorrectAnswerOptions); // Also on blur
        }
    }

    function getCurrentlyUsedLetters() {
        const usedLetters = new Set();
        optionsContainer.querySelectorAll('.option-group select[name$="[letter]"]').forEach(select => {
            if (select.value) usedLetters.add(select.value);
        });
        return usedLetters;
    }

    function getNextAvailableLetterForNewOption(existingLettersSet = null) {
        const usedLetters = existingLettersSet || getCurrentlyUsedLetters();
        for (const letter of allPossibleLetters) {
            if (!usedLetters.has(letter)) return letter;
        }
        return null;
    }

    function createOptionElementHtml(index, defaultLetter = '') {
        let letterOptionsHtml = '';
        allPossibleLetters.forEach(l => {
            const selected = (l === defaultLetter) ? 'selected' : '';
            letterOptionsHtml += `<option value="${l}" ${selected}>${l}</option>`;
        });

        return `
            <div class="w-1/6">
                <label for="options_${index}_letter" class="sr-only">Letter</label>
                <select name="options[${index}][letter]" id="options_${index}_letter" class="admin-input w-full text-center option-letter-select" required>
                    ${letterOptionsHtml}
                </select>
            </div>
            <div class="flex-grow">
                <label for="options_${index}_text" class="sr-only">Text</label>
                <input type="text" name="options[${index}][text]" id="options_${index}_text" value="" placeholder="Option text for ${defaultLetter}" required class="admin-input w-full">
            </div>
            <button type="button" class="admin-btn-red remove-option-btn text-xs p-1.5 leading-none flex-shrink-0" title="Remove this option"><i class="fas fa-times"></i></button>
        `;
    }

    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function () {
            const currentOptionGroups = optionsContainer.querySelectorAll('.option-group');
            if (currentOptionGroups.length < 5) {
                const nextLetter = getNextAvailableLetterForNewOption();
                if (!nextLetter) {
                    alert('All option letters (A-E) are currently in use or max options reached.');
                    this.classList.add('hidden');
                    return;
                }
                
                const newDiv = document.createElement('div');
                newDiv.className = 'option-group flex items-center space-x-2 p-3 border dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700';
                newDiv.innerHTML = createOptionElementHtml(nextGeneratedOptionIndex, nextLetter);
                
                optionsContainer.appendChild(newDiv);
                attachEventListenersToOptionGroup(newDiv); // Attach listeners to the new elements
                
                nextGeneratedOptionIndex++; // Increment for the next potential new option
                updateCorrectAnswerOptions();
            }
            // Hide button if we've reached max options
            if (optionsContainer.querySelectorAll('.option-group').length >= 5) {
                this.classList.add('hidden');
            }
        });
    }

    optionsContainer.addEventListener('click', function (e) {
        const removeButton = e.target.closest('.remove-option-btn');
        if (removeButton) {
            const currentOptionGroups = optionsContainer.querySelectorAll('.option-group');
            if (currentOptionGroups.length > 2) {
                removeButton.closest('.option-group').remove();
                // No need to decrement nextGeneratedOptionIndex, it's for unique indexing of new elements.
                // Re-indexing of names isn't strictly necessary on client-side for form submission if server handles sparse arrays.
                updateCorrectAnswerOptions();
                if (optionsContainer.querySelectorAll('.option-group').length < 5) {
                     if(addOptionBtn) addOptionBtn.classList.remove('hidden');
                }
            } else {
                alert('A minimum of 2 options are required.');
            }
        }
    });
    
    // Initial setup for existing options (on edit page or after validation fail with old input)
    optionsContainer.querySelectorAll('.option-group').forEach(group => {
        attachEventListenersToOptionGroup(group);
        const textInput = group.querySelector('input[name$="[text]"]');
        const letterSelect = group.querySelector('select[name$="[letter]"]');
        // Make sure initially rendered options (especially on create with defaults) are required
        if(textInput) textInput.required = true;
        if(letterSelect) letterSelect.required = true;
    });
    updateCorrectAnswerOptions(); 
    
    // Check if add button should be initially hidden
    if (optionsContainer.querySelectorAll('.option-group').length >= 5) {
        if(addOptionBtn) addOptionBtn.classList.add('hidden');
    }
});
</script>
