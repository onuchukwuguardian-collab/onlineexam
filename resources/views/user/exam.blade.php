@extends('layouts.student_app')

@section('title', 'Exam: ' . $subject->name)

@push('styles')
<style>
    /* Hide navbar during exam */
    .navbar {
        display: none !important;
    }
    
    /* Adjust main wrapper to remove top margin since navbar is hidden */
    .main-wrapper {
        margin-top: 0 !important;
    }
    
    /* Simple, Clean Exam Interface */
    .exam-container {
        min-height: auto;
        background: #f8f9fa;
        padding: 20px;
        font-family: Arial, sans-serif;
    }
    
    .exam-content {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
    }
    
    /* Simple Header */
    .exam-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 20px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .exam-info h1 {
        margin: 0;
        color: #333;
        font-size: 24px;
        font-weight: bold;
    }
    
    .exam-meta {
        display: flex;
        gap: 20px;
        align-items: center;
        font-size: 14px;
        color: #666;
    }
    
    .timer {
        background: #007bff;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 16px;
    }
    
    .timer.warning {
        background: #ffc107;
        color: #000;
    }
    
    .timer.critical {
        background: #dc3545;
        color: white;
    }
    
    /* Question Container */
    .question-block {
        margin-bottom: 40px;
    }
    
    .question-header {
        margin-bottom: 20px;
    }
    
    .question-number {
        font-size: 18px;
        font-weight: bold;
        color: #007bff;
        margin: 0 0 10px 0;
    }
    
    .question-text {
        font-size: 16px;
        line-height: 1.6;
        color: #333;
        margin-bottom: 20px;
    }
    
    /* Simple Image Styling */
    .question-image {
        text-align: center;
        margin: 20px 0;
    }
    
    .question-image img {
        max-width: 100%;
        max-height: 400px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    /* Simple Options */
    .options-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .option-item {
        margin-bottom: 12px;
    }
    
    .option-label {
        display: block;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }
    
    .option-label:hover {
        border-color: #007bff;
        background: #f8f9ff;
    }
    
    .option-label.selected {
        border-color: #007bff;
        background: #e7f3ff;
    }
    
    .option-input {
        margin-right: 10px;
    }
    
    .option-text {
        font-size: 15px;
        color: #333;
    }
    
    /* Simple Navigation */
    .navigation-section {
        border-top: 2px solid #e9ecef;
        padding-top: 30px;
        margin-top: 30px;
    }
    
    .nav-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        gap: 15px;
    }
    
    .nav-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .nav-btn-prev {
        background: #6c757d;
        color: white;
    }
    
    .nav-btn-prev:hover {
        background: #5a6268;
    }
    
    .nav-btn-next {
        background: #007bff;
        color: white;
    }
    
    .nav-btn-next:hover {
        background: #0056b3;
    }
    
    .nav-btn-submit {
        background: #28a745;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
    }
    
    .nav-btn-submit:hover {
        background: #218838;
    }
    
    /* Simple Question Grid */
    .question-grid {
        text-align: center;
    }
    
    .grid-title {
        font-size: 16px;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }
    
    .question-numbers {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
        gap: 8px;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .question-num-btn {
        width: 40px;
        height: 40px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .question-num-btn:hover {
        border-color: #007bff;
    }
    
    .question-num-btn.current {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    
    .question-num-btn.answered {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .exam-container {
            padding: 10px;
        }
        
        .exam-content {
            padding: 20px;
        }
        
        .exam-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .nav-buttons {
            flex-direction: column;
            gap: 10px;
        }
        
        .nav-btn {
            width: 100%;
        }
        
        .question-numbers {
            grid-template-columns: repeat(6, 1fr);
        }
    }
</style>
@endpush

@section('content')
<div class="exam-container">
    <div class="exam-content">
        <!-- Simple Header -->
        <div class="exam-header">
            <div class="exam-info">
                <h1>{{ $subject->name }}</h1>
                <div class="exam-meta">
                    <span>Question <span id="current-question">1</span> of {{ count($questionsList) }}</span>
                    <span>Answered: <span id="answered-count">0</span></span>
                </div>
            </div>
            <div class="timer" id="exam-timer">{{ $subject->exam_duration_minutes }}:00</div>
        </div>

        <form id="examForm" action="{{ route('user.exam.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
            <input type="hidden" name="session_id" value="{{ $examSession->id }}">
            <input type="hidden" name="auto_submitted" id="autoSubmitted" value="0">

            <!-- Question Blocks -->
            @foreach($questionsList as $index => $questionData)
                <div class="question-block" 
                     id="question-{{ $index }}" 
                     data-question-id="{{ $questionData['id'] }}"
                     style="display: {{ $index === 0 ? 'block' : 'none' }};">
                    
                    <div class="question-header">
                        <h2 class="question-number">Question {{ $index + 1 }}</h2>
                    </div>
                    
                    <div class="question-text">
                        {!! nl2br(e($questionData['text'])) !!}
                    </div>
                    
                    @if(!empty($questionData['image_path']))
                        <div class="question-image">
                            <img src="{{ $questionData['image_path'] }}?t={{ time() }}" alt="Question Image">
                        </div>
                    @endif

                    <ul class="options-list">
                        @foreach($questionData['options'] as $option)
                            <li class="option-item">
                                <label class="option-label" for="option_{{ $option['id'] }}_{{ $index }}">
                                    <input class="option-input"
                                           type="radio" 
                                           name="answers[{{ $questionData['id'] }}]" 
                                           id="option_{{ $option['id'] }}_{{ $index }}" 
                                           value="{{ $option['letter'] }}"
                                           data-question-index="{{ $index }}"
                                           data-question-id="{{ $questionData['id'] }}">
                                    <span class="option-text">
                                        <strong>{{ $option['letter'] }})</strong> {{ $option['text'] }}
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <!-- Simple Navigation -->
            <div class="navigation-section">
                <div class="nav-buttons">
                    <button type="button" id="prevBtn" class="nav-btn nav-btn-prev" onclick="previousQuestion()" style="display: none;">
                        ← Previous
                    </button>
                    
                    <div id="nextSubmitContainer">
                        <button type="button" id="nextBtn" class="nav-btn nav-btn-next" onclick="nextQuestion()">
                            Next →
                        </button>
                        <button type="submit" id="submitBtn" class="nav-btn nav-btn-submit" style="display: none;">
                            Submit Exam
                        </button>
                    </div>
                </div>
                
                <!-- Question Grid -->
                <div class="question-grid">
                    <div class="grid-title">Jump to Question:</div>
                    <div class="question-numbers">
                        @foreach($questionsList as $navIndex => $navQuestion)
                            <button type="button" 
                                    class="question-num-btn" 
                                    data-question="{{ $navIndex }}"
                                    onclick="goToQuestion({{ $navIndex }})">
                                {{ $navIndex + 1 }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const totalQuestions = {{ count($questionsList) }};
    const sessionId = {{ $examSession->id }};
    let currentQuestion = {{ $examSession->current_question_index ?? 0 }};
    let answeredQuestions = new Array(totalQuestions).fill(false);
    let timeRemaining = {{ $examSession->remaining_time }};
    let timerInterval;
    let progressSaveInterval;
    let timerCheckInterval;
    let examAnswers = @json($examSession->answers ?? []);

    // Initialize exam
    document.addEventListener('DOMContentLoaded', function() {
        initializeExam();
        startTimer();
        startProgressSaving();
        startTimerChecking();
        updateNavigation();
        updateQuestionGrid();
        loadSavedAnswers();
        
        // Add event listeners for radio buttons
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const questionIndex = parseInt(this.dataset.questionIndex);
                const questionId = this.dataset.questionId;
                
                console.log('Answer selected:', {
                    questionIndex: questionIndex,
                    questionId: questionId,
                    answer: this.value,
                    currentQuestion: currentQuestion
                });
                
                // Only process if this is the current question
                if (questionIndex === currentQuestion) {
                    answeredQuestions[questionIndex] = true;
                    examAnswers[questionId] = this.value;
                    
                    updateAnsweredCount();
                    updateQuestionGrid();
                    
                    // Visual feedback - highlight selected option
                    document.querySelectorAll(`input[name="answers[${questionId}]"]`).forEach(r => {
                        r.closest('.option-label').classList.remove('selected');
                    });
                    this.closest('.option-label').classList.add('selected');
                }
            });
        });

        // Add submit confirmation
        document.getElementById('examForm').addEventListener('submit', function(e) {
            const autoSubmitted = document.getElementById('autoSubmitted').value === '1';
            
            if (!autoSubmitted) {
                e.preventDefault();
                if (confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.')) {
                    this.submit();
                }
            }
        });
    });

    function initializeExam() {
        // Check if exam has expired on load
        if (timeRemaining <= 0) {
            autoSubmitExam();
            return;
        }
        
        // Store exam start time in localStorage for persistence
        const examKey = `exam_${sessionId}`;
        const storedData = localStorage.getItem(examKey);
        
        if (storedData) {
            try {
                const data = JSON.parse(storedData);
                examAnswers = data.answers || {};
                // Only restore question position if there are saved answers
                if (Object.keys(examAnswers).length > 0) {
                    currentQuestion = data.currentQuestion || 0;
                } else {
                    currentQuestion = 0; // Start from first question if no answers saved
                }
            } catch (e) {
                console.warn('Failed to load stored exam data');
                currentQuestion = 0; // Fallback to first question
            }
        } else {
            currentQuestion = 0; // Start from first question for new exam
        }
        
        // Ensure we show the correct question on load
        showCurrentQuestion();
        
        // Save exam data to localStorage
        const saveToStorage = () => {
            localStorage.setItem(examKey, JSON.stringify({
                answers: examAnswers,
                currentQuestion: currentQuestion,
                lastSaved: Date.now()
            }));
        };
        
        // Save to localStorage every 10 seconds (local only, no server calls)
        setInterval(saveToStorage, 10000);
    }

    function loadSavedAnswers() {
        console.log('Loading saved answers:', examAnswers);
        
        // Load previously saved answers
        for (const [questionId, answer] of Object.entries(examAnswers)) {
            const radio = document.querySelector(`input[name="answers[${questionId}]"][value="${answer}"]`);
            if (radio) {
                radio.checked = true;
                radio.closest('.option-label').classList.add('selected');
                const questionIndex = parseInt(radio.dataset.questionIndex);
                answeredQuestions[questionIndex] = true;
            }
        }
        updateAnsweredCount();
        updateQuestionGrid();
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            timeRemaining--;
            updateTimerDisplay();
            
            if (timeRemaining <= 0) {
                clearAllIntervals();
                autoSubmitExam();
            }
        }, 1000);
    }

    function startProgressSaving() {
        // Save progress every 2 minutes (less frequent)
        progressSaveInterval = setInterval(() => {
            saveProgressNow();
        }, 120000);
    }

    function startTimerChecking() {
        // Check server timer every 5 minutes (much less frequent)
        timerCheckInterval = setInterval(() => {
            checkServerTimer();
        }, 300000);
    }

    function saveProgressNow() {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('session_id', sessionId);
        formData.append('current_question_index', currentQuestion);
        
        // Add answers
        for (const [questionId, answer] of Object.entries(examAnswers)) {
            formData.append(`answers[${questionId}]`, answer);
        }

        fetch('{{ route("user.exam.save.progress") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.status === 429) {
                console.warn('Rate limited - progress save skipped');
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.expired) {
                clearAllIntervals();
                alert(data.message);
                autoSubmitExam();
            } else if (data && data.remaining_time !== undefined) {
                // Sync timer with server
                timeRemaining = data.remaining_time;
            }
        })
        .catch(error => {
            console.warn('Progress save failed:', error);
        });
    }

    function checkServerTimer() {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('session_id', sessionId);

        fetch('{{ route("user.exam.check.timer") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.status === 429) {
                console.warn('Rate limited - timer check skipped');
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.expired) {
                clearAllIntervals();
                alert(data.message);
                autoSubmitExam();
            } else if (data && data.remaining_time !== undefined) {
                // Sync timer with server
                const serverTime = data.remaining_time;
                const timeDiff = Math.abs(timeRemaining - serverTime);
                
                // If there's a significant difference, sync with server
                if (timeDiff > 10) {
                    timeRemaining = serverTime;
                }
            }
        })
        .catch(error => {
            console.warn('Timer check failed:', error);
        });
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        const timerEl = document.getElementById('exam-timer');
        timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Change timer color based on time remaining
        if (timeRemaining <= 300) { // 5 minutes
            timerEl.className = 'timer critical';
        } else if (timeRemaining <= 600) { // 10 minutes
            timerEl.className = 'timer warning';
        } else {
            timerEl.className = 'timer';
        }
    }

    function nextQuestion() {
        if (currentQuestion < totalQuestions - 1) {
            currentQuestion++;
            showCurrentQuestion();
        }
    }

    function previousQuestion() {
        if (currentQuestion > 0) {
            currentQuestion--;
            showCurrentQuestion();
        }
    }

    function goToQuestion(questionIndex) {
        currentQuestion = questionIndex;
        showCurrentQuestion();
    }

    function showCurrentQuestion() {
        console.log('Showing question:', currentQuestion + 1, 'of', totalQuestions);
        
        // Hide all questions
        document.querySelectorAll('.question-block').forEach((block, index) => {
            block.style.display = 'none';
        });
        
        // Show current question
        const currentQuestionElement = document.getElementById(`question-${currentQuestion}`);
        if (currentQuestionElement) {
            currentQuestionElement.style.display = 'block';
            
            // Restore selected answer visual state
            const questionId = currentQuestionElement.dataset.questionId;
            if (examAnswers[questionId]) {
                const selectedRadio = currentQuestionElement.querySelector(`input[value="${examAnswers[questionId]}"]`);
                if (selectedRadio) {
                    selectedRadio.checked = true;
                    selectedRadio.closest('.option-label').classList.add('selected');
                }
            }
        } else {
            console.error('Question element not found:', `question-${currentQuestion}`);
        }
        
        // Update displays
        document.getElementById('current-question').textContent = currentQuestion + 1;
        updateNavigation();
        updateQuestionGrid();
    }

    function updateNavigation() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        // Show/hide previous button
        prevBtn.style.display = currentQuestion > 0 ? 'block' : 'none';
        
        // Show next or submit button
        if (currentQuestion === totalQuestions - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
    }

    function updateQuestionGrid() {
        document.querySelectorAll('.question-num-btn').forEach((btn, index) => {
            btn.className = 'question-num-btn';
            
            if (index === currentQuestion) {
                btn.classList.add('current');
            } else if (answeredQuestions[index]) {
                btn.classList.add('answered');
            }
        });
    }

    function updateAnsweredCount() {
        const count = answeredQuestions.filter(answered => answered).length;
        document.getElementById('answered-count').textContent = count;
    }

    function autoSubmitExam() {
        document.getElementById('autoSubmitted').value = '1';
        
        // Populate form with current answers
        for (const [questionId, answer] of Object.entries(examAnswers)) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `answers[${questionId}]`;
            hiddenInput.value = answer;
            document.getElementById('examForm').appendChild(hiddenInput);
        }
        
        alert('Time is up! Your exam will be submitted automatically.');
        document.getElementById('examForm').submit();
    }

    function clearAllIntervals() {
        if (timerInterval) clearInterval(timerInterval);
        if (progressSaveInterval) clearInterval(progressSaveInterval);
        if (timerCheckInterval) clearInterval(timerCheckInterval);
    }

    // Handle page visibility changes (user switches tabs/minimizes)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // User returned to tab, save progress (but don't check timer immediately to avoid rate limiting)
            setTimeout(() => {
                saveProgressNow();
            }, 2000);
        }
    });

    // Prevent accidental page refresh
    window.addEventListener('beforeunload', function(e) {
        // Save to localStorage immediately (no server call to avoid blocking)
        const examKey = `exam_${sessionId}`;
        localStorage.setItem(examKey, JSON.stringify({
            answers: examAnswers,
            currentQuestion: currentQuestion,
            timeRemaining: timeRemaining,
            lastSaved: Date.now()
        }));
        
        e.preventDefault();
        e.returnValue = '';
    });

    // Clean up intervals when page unloads
    window.addEventListener('unload', function() {
        clearAllIntervals();
    });
</script>
@endpush