/**
 * Exam Navigation System
 * Handles question navigation, auto-advance, and submission functionality
 */

class ExamNavigationSystem {
    constructor() {
        // Configuration
        this.config = {
            autoAdvanceDelay: 1000,      // 1 second delay before auto-advancing
            autoAdvanceEnabled: false,   // Auto-advance disabled by default
            apiEndpoints: {
                saveProgress: '/api/exam/save-progress',
                submitExam: '/api/exam/submit'
            }
        };

        // State
        this.state = {
            questions: [],
            currentQuestionIndex: 0,
            answers: {},
            flaggedQuestions: new Set(),
            examSessionId: null,
            remainingTime: 0,
            timerInterval: null
        };

        // Initialize
        this.init();
    }

    /**
     * Initialize the navigation system
     */
    init() {
        console.log('Initializing Exam Navigation System...');
        this.loadExamContext();
        this.setupEventListeners();
        this.renderQuestionNavigation();
        this.loadCurrentQuestion();
        console.log('Exam Navigation System activated.');
    }

    /**
     * Load exam context from page
     */
    loadExamContext() {
        // Load questions data
        const questionsData = document.getElementById('exam-questions-data');
        if (questionsData) {
            try {
                this.state.questions = JSON.parse(questionsData.textContent);
            } catch (error) {
                console.error('Failed to parse questions data:', error);
            }
        }

        // Load session ID and time
        const sessionEl = document.getElementById('exam-session-data');
        if (sessionEl) {
            this.state.examSessionId = sessionEl.dataset.sessionId;
            this.state.remainingTime = parseInt(sessionEl.dataset.remainingTime) || 0;
        }

        // Load saved progress
        const progressEl = document.getElementById('exam-progress-data');
        if (progressEl) {
            try {
                const progress = JSON.parse(progressEl.textContent);
                if (progress.answers) {
                    this.state.answers = progress.answers;
                }
                if (progress.flaggedQuestions) {
                    this.state.flaggedQuestions = new Set(progress.flaggedQuestions);
                }
                if (progress.currentQuestionIndex !== undefined) {
                    this.state.currentQuestionIndex = progress.currentQuestionIndex;
                }
            } catch (error) {
                console.error('Failed to parse progress data:', error);
            }
        }

        // Load auto-advance preference from local storage
        const savedAutoAdvance = localStorage.getItem('autoAdvanceEnabled');
        if (savedAutoAdvance !== null) {
            this.config.autoAdvanceEnabled = savedAutoAdvance === 'true';
        }
    }

    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Navigation buttons
        document.getElementById('prevBtn')?.addEventListener('click', () => this.previousQuestion());
        document.getElementById('nextBtn')?.addEventListener('click', () => this.nextQuestion());
        document.getElementById('finishBtn')?.addEventListener('click', () => this.showSubmitModal());
        
        // Question options
        document.addEventListener('click', (e) => {
            if (e.target.closest('.option-item')) {
                this.handleOptionClick(e.target.closest('.option-item'));
            }
        });
        
        // Flag button
        document.getElementById('flagBtn')?.addEventListener('click', () => this.toggleFlag());
        
        // Auto-advance toggle
        document.getElementById('toggleAutoAdvance')?.addEventListener('click', () => this.toggleAutoAdvance());
        
        // Sidebar toggle
        document.getElementById('showProgressBtn')?.addEventListener('click', () => this.toggleSidebar());
        document.getElementById('closeSidebarBtn')?.addEventListener('click', () => this.closeSidebar());
        
        // Question navigation
        document.addEventListener('click', (e) => {
            const navBtn = e.target.closest('.question-nav-btn');
            if (navBtn) {
                const questionIndex = parseInt(navBtn.dataset.question);
                this.goToQuestion(questionIndex);
            }
        });
        
        // Submit confirmation
        document.getElementById('confirmSubmitBtn')?.addEventListener('click', () => this.submitExam());
        document.getElementById('goBackBtn')?.addEventListener('click', () => {
            document.getElementById('submitModal').style.display = 'none';
        });
    }

    /**
     * Render question navigation buttons
     */
    renderQuestionNavigation() {
        const navContainer = document.getElementById('questionNav');
        if (!navContainer) return;
        
        navContainer.innerHTML = '';
        
        this.state.questions.forEach((question, index) => {
            const btn = document.createElement('button');
            btn.className = 'question-nav-btn';
            btn.dataset.question = index;
            btn.textContent = index + 1;
            navContainer.appendChild(btn);
        });
        
        this.updateQuestionNavigation();
    }

    /**
     * Update question navigation to reflect current state
     */
    updateQuestionNavigation() {
        const navBtns = document.querySelectorAll('.question-nav-btn');
        
        navBtns.forEach((btn, index) => {
            const questionId = this.state.questions[index]?.id;
            
            // Remove all status classes
            btn.classList.remove('current', 'answered', 'flagged');
            
            // Add appropriate classes
            if (index === this.state.currentQuestionIndex) {
                btn.classList.add('current');
            }
            
            if (questionId && this.state.answers[questionId]) {
                btn.classList.add('answered');
            }
            
            if (questionId && this.state.flaggedQuestions.has(questionId)) {
                btn.classList.add('flagged');
            }
        });
    }

    /**
     * Load current question into the UI
     */
    loadCurrentQuestion() {
        const question = this.state.questions[this.state.currentQuestionIndex];
        if (!question) return;
        
        // Update question number
        document.getElementById('questionNumber').textContent = this.state.currentQuestionIndex + 1;
        
        // Load question text
        document.getElementById('questionText').innerHTML = question.text;
        
        // Handle question image
        const questionImage = document.getElementById('questionImage');
        const questionImg = document.getElementById('questionImg');
        
        if (question.image_path) {
            questionImg.src = question.image_path;
            questionImage.style.display = 'block';
        } else {
            questionImage.style.display = 'none';
        }
        
        // Load options
        this.loadQuestionOptions(question);
        
        // Update navigation buttons
        this.updateNavigationButtons();
        
        // Update question status
        this.updateQuestionStatus();
    }

    /**
     * Load options for the current question
     */
    loadQuestionOptions(question) {
        const container = document.getElementById('optionsContainer');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!question.options || !Array.isArray(question.options)) {
            console.error('Question options not found or invalid:', question);
            container.innerHTML = '<div class="alert alert-danger">Error: Question options not loaded properly.</div>';
            return;
        }
        
        question.options.forEach(option => {
            const isSelected = this.state.answers[question.id] === option.letter;
            
            const optionEl = document.createElement('div');
            optionEl.className = `option-item ${isSelected ? 'selected' : ''}`;
            optionEl.dataset.option = option.letter;
            optionEl.dataset.questionId = question.id;
            
            optionEl.innerHTML = `
                <input type="radio" 
                       class="option-radio" 
                       name="question_${question.id}" 
                       value="${option.letter}" 
                       ${isSelected ? 'checked' : ''}>
                <span class="option-letter">${option.letter}.</span>
                <span class="option-text">${option.text}</span>
            `;
            
            container.appendChild(optionEl);
        });
    }

    /**
     * Update navigation buttons (prev/next/finish)
     */
    updateNavigationButtons() {
        // Previous button
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) {
            prevBtn.disabled = this.state.currentQuestionIndex === 0;
        }
        
        // Next/Finish button
        const nextBtn = document.getElementById('nextBtn');
        const finishBtn = document.getElementById('finishBtn');
        
        if (nextBtn && finishBtn) {
            if (this.state.currentQuestionIndex === this.state.questions.length - 1) {
                nextBtn.style.display = 'none';
                finishBtn.style.display = 'block';
            } else {
                nextBtn.style.display = 'block';
                finishBtn.style.display = 'none';
            }
        }
    }

    /**
     * Update question status (answered/flagged)
     */
    updateQuestionStatus() {
        const question = this.state.questions[this.state.currentQuestionIndex];
        if (!question) return;
        
        const questionStatus = document.getElementById('questionStatus');
        if (!questionStatus) return;
        
        const isAnswered = this.state.answers.hasOwnProperty(question.id);
        const isFlagged = this.state.flaggedQuestions.has(question.id);
        
        let statusText = 'Not Answered';
        let statusClass = 'status-indicator';
        
        if (isAnswered && isFlagged) {
            statusText = 'Answered & Flagged';
            statusClass += ' status-flagged status-answered';
        } else if (isAnswered) {
            statusText = 'Answered';
            statusClass += ' status-answered';
        } else if (isFlagged) {
            statusText = 'Flagged for Review';
            statusClass += ' status-flagged';
        }
        
        questionStatus.className = statusClass;
        questionStatus.textContent = statusText;
        
        // Update flag button
        const flagBtn = document.getElementById('flagBtn');
        const flagText = document.getElementById('flagText');
        
        if (flagBtn && flagText) {
            if (isFlagged) {
                flagBtn.classList.remove('btn-outline-warning');
                flagBtn.classList.add('btn-warning');
                flagText.textContent = 'Remove Flag';
            } else {
                flagBtn.classList.remove('btn-warning');
                flagBtn.classList.add('btn-outline-warning');
                flagText.textContent = 'Flag for Review';
            }
        }
    }

    /**
     * Handle option click
     */
    handleOptionClick(optionEl) {
        const option = optionEl.dataset.option;
        const questionId = optionEl.dataset.questionId;
        
        // Remove selection from all options
        const allOptions = document.querySelectorAll('.option-item');
        allOptions.forEach(opt => opt.classList.remove('selected'));
        
        // Add selection to clicked option
        optionEl.classList.add('selected');
        
        // Save answer
        this.state.answers[questionId] = option;
        
        // Update UI
        this.updateQuestionStatus();
        this.updateQuestionNavigation();
        this.updateProgress();
        
        // Save progress
        this.saveProgress();
        
        // Auto-advance if enabled
        if (this.config.autoAdvanceEnabled && this.state.currentQuestionIndex < this.state.questions.length - 1) {
            setTimeout(() => {
                this.nextQuestion();
            }, this.config.autoAdvanceDelay);
        }
    }

    /**
     * Toggle flagged status for current question
     */
    toggleFlag() {
        const question = this.state.questions[this.state.currentQuestionIndex];
        if (!question) return;
        
        if (this.state.flaggedQuestions.has(question.id)) {
            this.state.flaggedQuestions.delete(question.id);
        } else {
            this.state.flaggedQuestions.add(question.id);
        }
        
        this.updateQuestionStatus();
        this.updateQuestionNavigation();
        this.saveProgress();
    }

    /**
     * Toggle auto-advance feature
     */
    toggleAutoAdvance() {
        this.config.autoAdvanceEnabled = !this.config.autoAdvanceEnabled;
        localStorage.setItem('autoAdvanceEnabled', this.config.autoAdvanceEnabled);
        this.updateAutoAdvanceButton();
    }

    /**
     * Update auto-advance button appearance
     */
    updateAutoAdvanceButton() {
        const btn = document.getElementById('toggleAutoAdvance');
        const text = document.getElementById('autoAdvanceText');
        
        if (!btn || !text) return;
        
        if (this.config.autoAdvanceEnabled) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
            text.textContent = 'Auto-Advance ON';
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
            text.textContent = 'Enable Auto-Advance';
        }
    }

    /**
     * Navigate to previous question
     */
    previousQuestion() {
        if (this.state.currentQuestionIndex > 0) {
            this.state.currentQuestionIndex--;
            this.loadCurrentQuestion();
            this.saveProgress();
        }
    }

    /**
     * Navigate to next question
     */
    nextQuestion() {
        if (this.state.currentQuestionIndex < this.state.questions.length - 1) {
            this.state.currentQuestionIndex++;
            this.loadCurrentQuestion();
            this.saveProgress();
        }
    }

    /**
     * Go to specific question
     */
    goToQuestion(index) {
        if (index >= 0 && index < this.state.questions.length) {
            this.state.currentQuestionIndex = index;
            this.loadCurrentQuestion();
            this.saveProgress();
            this.closeSidebar();
        }
    }

    /**
     * Toggle sidebar visibility
     */
    toggleSidebar() {
        const sidebar = document.getElementById('examSidebar');
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
    }

    /**
     * Close sidebar
     */
    closeSidebar() {
        const sidebar = document.getElementById('examSidebar');
        if (sidebar) {
            sidebar.classList.remove('open');
        }
    }

    /**
     * Update progress indicators
     */
    updateProgress() {
        const answeredCount = Object.keys(this.state.answers).length;
        const totalQuestions = this.state.questions.length;
        const progressPercent = Math.round((answeredCount / totalQuestions) * 100);
        
        // Update progress bar
        const progressBar = document.getElementById('examProgress');
        if (progressBar) {
            progressBar.style.width = `${progressPercent}%`;
        }
        
        // Update percentage
        const percentEl = document.getElementById('progressPercent');
        if (percentEl) {
            percentEl.textContent = progressPercent;
        }
        
        // Update question count
        const answeredNumEl = document.getElementById('answeredNum');
        if (answeredNumEl) {
            answeredNumEl.textContent = answeredCount;
        }
        
        // Update sidebar stats
        document.getElementById('sidebarAnswered')?.textContent = answeredCount;
        document.getElementById('sidebarRemaining')?.textContent = totalQuestions - answeredCount;
        document.getElementById('sidebarFlagged')?.textContent = this.state.flaggedQuestions.size;
    }

    /**
     * Save progress to server
     */
    saveProgress() {
        // Prepare data
        const data = {
            session_id: this.state.examSessionId,
            answers: this.state.answers,
            current_question_index: this.state.currentQuestionIndex,
            flagged_questions: Array.from(this.state.flaggedQuestions)
        };
        
        // Send to server
        fetch(this.config.apiEndpoints.saveProgress, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.expired) {
                alert(data.message);
                window.location.href = '/security/critical-warning';
            }
        })
        .catch(error => {
            console.error('Failed to save progress:', error);
        });
        
        // Update UI to reflect saved progress
        this.updateQuestionNavigation();
        this.updateProgress();
    }

    /**
     * Show submit confirmation modal
     */
    showSubmitModal() {
        const totalQuestions = this.state.questions.length;
        const answeredCount = Object.keys(this.state.answers).length;
        const flaggedCount = this.state.flaggedQuestions.size;
        const unansweredCount = totalQuestions - answeredCount;
        
        // Update modal content
        document.getElementById('submitTotal').textContent = totalQuestions;
        document.getElementById('submitAnswered').textContent = answeredCount;
        document.getElementById('submitFlagged').textContent = flaggedCount;
        document.getElementById('submitUnanswered').textContent = unansweredCount;
        
        // Show modal
        const modal = document.getElementById('submitModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    /**
     * Submit exam
     */
    submitExam() {
        // Hide modal
        document.getElementById('submitModal').style.display = 'none';
        
        // Prepare form data
        document.getElementById('answersInput').value = JSON.stringify(this.state.answers);
        document.getElementById('autoSubmittedInput').value = '0';
        
        // Submit form
        document.getElementById('examForm').submit();
    }
}

// Initialize the navigation system when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.examNavigationSystem = new ExamNavigationSystem();
});