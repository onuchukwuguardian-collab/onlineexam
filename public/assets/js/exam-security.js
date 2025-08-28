/**
 * Enhanced Exam Security System
 * 
 * Features:
 * - Tab switching detection and immediate ban
 * - Right-click prevention (without sending violations)
 * - Copy/paste prevention
 * - Reactivation request system for banned users
 * 
 * SECURITY POLICY:
 * - Tab switching: 3-STRIKE POLICY (Ban on 3rd violation)
 * - Right-click: BLOCK BUT NO BAN (prevention only)
 * - Clipboard operations: BLOCK BUT NO BAN (prevention only)
 */

class ExamSecuritySystem {
    constructor() {
        // Configuration
        this.config = {
            banThreshold: 3,               // Ban on the 3rd tab switch violation
            trackRightClick: false,        // Don't track right-click as violation
            preventRightClick: true,       // Still prevent right-click functionality
            preventCopyPaste: true,        // Prevent copy/paste operations
            visibilityDelay: 300,          // ms delay to confirm tab switch (avoid false positives)
            apiEndpoints: {
                reportViolation: '/api/security/violation',
                checkBanStatus: '/api/security/check-ban',
                requestReactivation: '/api/security/request-reactivation'
            }
        };

        // State
        this.state = {
            subjectId: null,
            userId: null,
            examSessionId: null,
            visibilityTimeout: null,
            banned: false,
            banDetails: null,
            reactivationRequestSent: false,
            reactivationRequestPending: false,
        };

        // Initialize
        this.init();
    }

    /**
     * Initialize the security system
     */
    init() {
        console.log('Initializing Exam Security System...');
        this.loadSecurityContext();
        this.setupEventListeners();
        this.preventBrowserShortcuts();
        this.checkInitialBanStatus();
        console.log('Exam Security System activated.');
    }

    /**
     * Load security context from data attributes
     */
    loadSecurityContext() {
        const securityEl = document.getElementById('examSecurityContext');
        if (securityEl) {
            this.state.subjectId = securityEl.dataset.subjectId;
            this.state.userId = securityEl.dataset.userId;
            this.state.examSessionId = securityEl.dataset.examSessionId;
        } else {
            console.error('Security context element not found. Security system may not function properly.');
        }
    }

    /**
     * Setup security event listeners
     */
    setupEventListeners() {
        // Tab switching detection
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));

        // Prevent right-click (but don't report as violation)
        if (this.config.preventRightClick) {
            document.addEventListener('contextmenu', this.handleRightClick.bind(this));
        }

        // Prevent copy/paste
        if (this.config.preventCopyPaste) {
            document.addEventListener('copy', this.preventClipboardOperation.bind(this));
            document.addEventListener('cut', this.preventClipboardOperation.bind(this));
            document.addEventListener('paste', this.preventClipboardOperation.bind(this));
        }
    }

    /**
     * Handle visibility change (tab switching)
     */
    handleVisibilityChange() {
        // Clear any existing timeout
        if (this.state.visibilityTimeout) {
            clearTimeout(this.state.visibilityTimeout);
        }

        // If page becomes hidden (user switched tabs or minimized window)
        if (document.hidden) {
            // Set timeout to confirm it's a real tab switch, not a brief focus change
            this.state.visibilityTimeout = setTimeout(() => {
                if (document.hidden) {
                    this.handleTabSwitchViolation();
                }
            }, this.config.visibilityDelay);
        }
    }

    /**
     * Handle tab switch violation - IMMEDIATE BAN
     */
    handleTabSwitchViolation() {
        if (this.state.banned) return; // Already banned

        console.warn('SECURITY VIOLATION: Tab switching detected. Reporting to server...');

        // Report violation to server. The server will decide the consequence.
        this.reportViolation('tab_switch', 'Student switched tabs or opened a new browser window during the exam.');
    }

    /**
     * Handle right-click attempt - PREVENT BUT DON'T REPORT
     */
    handleRightClick(event) {
        // Prevent right-click menu
        event.preventDefault();
        event.stopPropagation();

        // We don't report right-click as violation per requirements
        console.warn('Right-click attempted but blocked (not reported as violation)');
        
        // Show warning tooltip near cursor
        this.showTemporaryWarning('Right-click is disabled during the exam', event.clientX, event.clientY);
        
        return false;
    }

    /**
     * Prevent clipboard operations (copy, cut, paste)
     */
    preventClipboardOperation(event) {
        event.preventDefault();
        console.warn('Clipboard operation attempted but blocked');
        
        // Show warning tooltip
        this.showTemporaryWarning('Copy/paste operations are disabled during the exam');
        
        return false;
    }

    /**
     * Show temporary warning tooltip
     */
    showTemporaryWarning(message, x = null, y = null) {
        const tooltip = document.createElement('div');
        tooltip.className = 'security-warning-tooltip';
        tooltip.textContent = message;
        
        // Position near cursor if coordinates provided
        if (x !== null && y !== null) {
            tooltip.style.left = `${x + 15}px`;
            tooltip.style.top = `${y + 15}px`;
        }
        
        document.body.appendChild(tooltip);
        
        // Fade in
        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);
        
        // Remove after delay
        setTimeout(() => {
            tooltip.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(tooltip);
            }, 300);
        }, 2500);
    }

    /**
     * Prevent browser shortcuts that could compromise exam integrity
     */
    preventBrowserShortcuts() {
        window.addEventListener('keydown', (e) => {
            // Prevent: 
            // Ctrl+P (print), 
            // Ctrl+S (save), 
            // F12 (dev tools),
            // Alt+Tab (handled by OS, but we can detect tab switch after)
            if (
                (e.ctrlKey && (e.key === 'p' || e.key === 's')) ||
                e.key === 'F12'
            ) {
                e.preventDefault();
                this.showTemporaryWarning('This keyboard shortcut is disabled during the exam');
                return false;
            }
        });
    }

    /**
     * Report security violation to server
     */
    reportViolation(violationType, description) {
        if (!this.state.userId || !this.state.subjectId) {
            console.error('Cannot report violation: Missing user or subject ID');
            return;
        }

        const data = {
            user_id: this.state.userId,
            subject_id: this.state.subjectId,
            exam_session_id: this.state.examSessionId,
            violation_type: violationType,
            description: description
        };

        fetch(this.config.apiEndpoints.reportViolation, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.banned) {
                this.state.banned = true;
                this.state.banDetails = data.ban_details;
                this.showBanWarning(); // Final ban warning
            } else if (data.violation_count > 0) {
                // Show a strike warning if not banned yet
                this.showStrikeWarning(data.violation_count);
            }
        })
        .catch(error => {
            console.error('Error reporting violation:', error);
        });
    }

    /**
     * Check initial ban status
     */
    checkInitialBanStatus() {
        if (!this.state.userId || !this.state.subjectId) {
            return;
        }

        fetch(`${this.config.apiEndpoints.checkBanStatus}?user_id=${this.state.userId}&subject_id=${this.state.subjectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.banned) {
                this.state.banned = true;
                this.state.banDetails = data.ban_details;
                this.showBanWarning();
            }
        })
        .catch(error => {
            console.error('Error checking ban status:', error);
        });
    }

    /**
     * Show a non-banning strike warning for violations 1 and 2.
     * @param {number} count - The current violation count.
     */
    showStrikeWarning(count) {
        // Ensure no other modals are open
        const existingModal = document.querySelector('.security-violation-modal');
        if (existingModal) {
            existingModal.remove();
        }

        const warningElement = document.createElement('div');
        warningElement.className = 'security-violation-modal';
        warningElement.innerHTML = `
            <div class="security-violation-content">
                <div class="violation-icon" style="color: #ffc107;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="violation-message">
                    <h2>Security Warning (Strike ${count}/${this.config.banThreshold})</h2>
                    <p class="violation-description">Leaving the exam page is a violation of the rules.</p>
                    <p class="violation-penalty">
                        If you reach ${this.config.banThreshold} violations, your exam access will be suspended.
                    </p>
                </div>
                <div class="violation-actions">
                    <button class="btn btn-warning" id="acknowledgeWarningBtn">I Understand, Return to Exam</button>
                </div>
            </div>
        `;

        document.body.appendChild(warningElement);

        document.getElementById('acknowledgeWarningBtn').addEventListener('click', () => {
            warningElement.remove();
        });
    }

    /**
     * Show ban warning with reactivation option
     */
    showBanWarning() {
        const warningElement = document.createElement('div');
        warningElement.className = 'security-violation-modal';
        warningElement.innerHTML = `
            <div class="security-violation-content">
                <div class="violation-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="violation-message">
                    <h2>Your Access Has Been Suspended</h2>
                    <p class="violation-description">
                        Due to a security violation (Tab Switching), your access to this exam has been suspended.
                    </p>
                    <p class="violation-details">
                        ${this.state.banDetails ? this.state.banDetails.ban_reason : 'You have been banned for a security violation.'}
                    </p>
                    <div class="reactivation-section">
                        <h3>Request Reactivation</h3>
                        <p>You can request reactivation by submitting a formal request to the administrator.</p>
                        <textarea id="reactivationMessage" placeholder="Explain why you should be reactivated..."></textarea>
                        <button class="btn btn-primary" id="requestReactivationBtn">Request Reactivation</button>
                        <p id="reactivationStatus" class="reactivation-status"></p>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(warningElement);

        // Add event listener to reactivation button
        document.getElementById('requestReactivationBtn').addEventListener('click', () => {
            this.requestReactivation();
        });
    }

    /**
     * Request reactivation
     */
    requestReactivation() {
        if (this.state.reactivationRequestSent || this.state.reactivationRequestPending) {
            return;
        }

        const message = document.getElementById('reactivationMessage').value;
        const statusElement = document.getElementById('reactivationStatus');
        
        if (!message || message.trim().length < 10) {
            statusElement.textContent = 'Please provide a detailed explanation.';
            statusElement.className = 'error';
            return;
        }

        this.state.reactivationRequestPending = true;
        statusElement.textContent = 'Sending request...';
        statusElement.className = 'pending';

        const data = {
            user_id: this.state.userId,
            subject_id: this.state.subjectId,
            exam_ban_id: this.state.banDetails ? this.state.banDetails.id : null,
            message: message
        };

        fetch(this.config.apiEndpoints.requestReactivation, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            this.state.reactivationRequestPending = false;
            
            if (data.success) {
                this.state.reactivationRequestSent = true;
                statusElement.textContent = 'Your reactivation request has been sent. Please wait for administrator review.';
                statusElement.className = 'success';
                document.getElementById('requestReactivationBtn').disabled = true;
                document.getElementById('reactivationMessage').disabled = true;
            } else {
                statusElement.textContent = data.message || 'Failed to send request. Please try again.';
                statusElement.className = 'error';
            }
        })
        .catch(error => {
            this.state.reactivationRequestPending = false;
            statusElement.textContent = 'Error sending request. Please try again.';
            statusElement.className = 'error';
            console.error('Error requesting reactivation:', error);
        });
    }
}

// Initialize the security system when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.examSecuritySystem = new ExamSecuritySystem();
});

// CSS styles for security warnings
document.addEventListener('DOMContentLoaded', () => {
    const style = document.createElement('style');
    style.textContent = `
        .security-warning-tooltip {
            position: fixed;
            background-color: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-weight: bold;
        }
        
        .security-violation-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        .security-violation-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            padding: 30px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.4s ease;
        }
        
        .violation-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .violation-message h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .violation-description {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .violation-penalty {
            font-weight: bold;
            color: #dc3545;
            font-size: 16px;
            margin-bottom: 25px;
        }
        
        .violation-actions {
            margin-top: 25px;
        }
        
        .reactivation-section {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            text-align: left;
        }
        
        .reactivation-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        #reactivationMessage {
            width: 100%;
            height: 100px;
            margin: 10px 0 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
        }
        
        .reactivation-status {
            margin-top: 15px;
            font-weight: bold;
        }
        
        .reactivation-status.error {
            color: #dc3545;
        }
        
        .reactivation-status.success {
            color: #28a745;
        }
        
        .reactivation-status.pending {
            color: #ffc107;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
});