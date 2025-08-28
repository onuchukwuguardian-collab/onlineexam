<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ClassController as AdminClassController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ScoreboardController as AdminScoreboardController;
use App\Http\Controllers\Admin\AdminExamResetController;
use App\Http\Controllers\Admin\SystemResetController as AdminSystemResetController;
use App\Http\Controllers\Admin\SecurityViolationController;
use App\Http\Controllers\SecurityViewController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('user.dashboard');
    }
    return redirect()->route('login');
})->name('home');

// --- MANUAL AUTHENTICATION ROUTES ---
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// FALLBACK: Handle any GET requests to logout (should not normally happen)
Route::get('logout', function() {
    // If someone accidentally makes a GET request to logout, redirect to login
    return redirect()->route('login')->with('warning', 'Please use the proper logout button.');
})->name('logout.get')->middleware('auth');

// CSRF Token Refresh Route
Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if (!auth()->user()->class_id && auth()->user()->isStudent()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'You are not assigned to a class. Please contact administrator.');
        }
        return redirect()->route('user.dashboard');
    })->name('dashboard');

    // Critical warning page for banned students
    Route::get('/security/critical-warning', [SecurityViolationController::class, 'showCriticalWarning'])->name('security.critical.warning');

    // Student Routes
    Route::middleware(['student'])->prefix('student')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/scores/modal', [UserDashboardController::class, 'getScoresModal'])->name('scores.modal');
        Route::get('/exam/{subject}', [ExamController::class, 'start'])->name('exam.start');
        Route::post('/exam/submit', [ExamController::class, 'submit'])->name('exam.submit')->middleware('throttle:10,1');
        Route::post('/exam/save-progress', [ExamController::class, 'saveProgress'])->name('exam.save.progress')->middleware('throttle:20,1');
        Route::post('/exam/sync-time', [ExamController::class, 'syncTime'])->name('exam.sync.time')->middleware('throttle:30,1');
        Route::post('/exam/check-timer', [ExamController::class, 'checkTimer'])->name('exam.check.timer')->middleware('throttle:10,1');
        Route::post('/exam/security-violation', [ExamController::class, 'recordSecurityViolation'])->name('exam.security.violation')->middleware('throttle:5,1');
        Route::get('/exam/score/{subject}', [ExamController::class, 'displayScore'])->name('score.display');
        Route::get('/exam/review/{examResult}', [ExamController::class, 'review'])->name('exam.review');
        
        // Student Reactivation Request Routes
        Route::prefix('reactivation')->name('student.reactivation.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Student\ReactivationController::class, 'index'])->name('index');
            Route::get('/create/{subject}', [\App\Http\Controllers\Student\ReactivationController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Student\ReactivationController::class, 'store'])->name('store');
            Route::get('/{request}', [\App\Http\Controllers\Student\ReactivationController::class, 'show'])->name('show');
            Route::get('/api/status/{subject}', [\App\Http\Controllers\Student\ReactivationController::class, 'status'])->name('status');
        });
    });

    // Keep alive endpoint for exam page
    Route::get('/session/keep-alive', [ExamController::class, 'keepAlive'])->name('session.keepalive');

    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('users/bulk-upload-form', [AdminUserController::class, 'showBulkUploadForm'])->name('users.bulkUploadForm');
        Route::post('users/bulk-upload', [AdminUserController::class, 'processBulkUpload'])->name('users.processBulkUpload')->middleware('bruteforce:2,5');
        Route::post('users/bulk-delete', [AdminUserController::class, 'bulkDelete'])->name('users.bulk-delete')->middleware('bruteforce:5,1');

        Route::resource('classes', AdminClassController::class)->except(['show']);
        Route::resource('subjects', AdminSubjectController::class)->except(['show']);

        // Question Management Routes
        Route::get('subjects/{subject}/questions', [AdminQuestionController::class, 'index'])->name('questions.index');
        Route::get('subjects/{subject}/questions/create', [AdminQuestionController::class, 'create'])->name('questions.create');
        Route::post('subjects/{subject}/questions', [AdminQuestionController::class, 'store'])->name('questions.store');
        Route::get('questions/{question}/edit', [AdminQuestionController::class, 'edit'])->name('questions.edit');
        Route::put('questions/{question}', [AdminQuestionController::class, 'update'])->name('questions.update');
        Route::delete('questions/{question}', [AdminQuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('subjects/{subject}/questions/bulk-delete', [AdminQuestionController::class, 'bulkDestroy'])->name('questions.bulkDestroy')->middleware('bruteforce:5,1');
        Route::get('subjects/{subject}/questions/bulk-upload-form', [AdminQuestionController::class, 'showBulkUploadForm'])->name('questions.bulkUploadForm');
        Route::post('subjects/{subject}/questions/bulk-upload', [AdminQuestionController::class, 'processBulkUpload'])->name('questions.processBulkUpload')->middleware('bruteforce:2,5');
        Route::post('questions/{question}/update-field', [AdminQuestionController::class, 'updateField'])->name('questions.updateField');
        Route::post('questions/{question}/update-image', [AdminQuestionController::class, 'updateImage'])->name('questions.updateImage');
        Route::post('questions/{question}/delete-image', [AdminQuestionController::class, 'deleteImage'])->name('questions.deleteImage');
        Route::get('questions/{question}/test-image', [AdminQuestionController::class, 'testImage'])->name('questions.testImage');

        Route::get('scoreboard', [AdminScoreboardController::class, 'index'])->name('scoreboard.index');
        Route::get('scoreboard/export/{format}', [AdminScoreboardController::class, 'export'])->name('scoreboard.export');
        Route::get('scoreboard/export-csv', [AdminScoreboardController::class, 'exportCsv'])->name('scoreboard.export.csv');
        Route::get('scoreboard/export-excel', [AdminScoreboardController::class, 'exportExcel'])->name('scoreboard.export.excel');
        Route::post('scoreboard/custom-export', [AdminScoreboardController::class, 'customExport'])->name('scoreboard.custom-export');
        
        // System Management Routes
        Route::get('system-reset', [AdminSystemResetController::class, 'index'])->name('system.reset.index');
        Route::post('system/reset-all', [AdminSystemResetController::class, 'resetAllExamData'])->name('system.reset');
        Route::post('system/optimize', [AdminSystemResetController::class, 'optimizeSystem'])->name('system.optimize');
        Route::post('system/cleanup', [AdminSystemResetController::class, 'cleanupSystem'])->name('system.cleanup');
        Route::post('system/backup', [AdminSystemResetController::class, 'createBackup'])->name('system.backup');
        Route::get('system/backups', [AdminSystemResetController::class, 'listBackups'])->name('system.backups');
        Route::get('system/backup/download/{filename}', [AdminSystemResetController::class, 'downloadBackup'])->name('system.backup.download');

        Route::get('exam-reset', [\App\Http\Controllers\Admin\ExamResetController::class, 'index'])->name('exam.reset.index');
        Route::post('exam-reset/student', [\App\Http\Controllers\Admin\ExamResetController::class, 'resetStudent'])->name('exam.reset.student');
        Route::post('exam-reset/bulk', [\App\Http\Controllers\Admin\ExamResetController::class, 'bulkReset'])->name('exam.reset.bulk');
        Route::get('exam-reset/subjects/{class}', [\App\Http\Controllers\Admin\ExamResetController::class, 'getSubjectsForClass'])->name('exam.reset.subjects');
        Route::post('exam-reset/search-student', [\App\Http\Controllers\Admin\ExamResetController::class, 'searchStudent'])->name('exam.reset.search');

        // Security Violation Management Routes (ADMIN ONLY)
        Route::prefix('security')->name('security.')->group(function () {
            Route::get('/', [SecurityViolationController::class, 'index'])->name('index');
            Route::get('/violations/{violation}', [SecurityViolationController::class, 'showViolation'])->name('violation-details');
            Route::post('/ban', [SecurityViolationController::class, 'banStudent'])->name('ban');
            Route::post('/bans/{ban}/unban', [SecurityViolationController::class, 'unbanStudent'])->name('unban');
            Route::get('/stats', [SecurityViolationController::class, 'getStats'])->name('stats');
            Route::get('/export', [SecurityViolationController::class, 'exportReport'])->name('export');
            Route::post('/clear', [SecurityViolationController::class, 'clearOldViolations'])->name('clear');
            
            // Reactivation Request Management
            Route::get('/reactivation-requests', [SecurityViolationController::class, 'reactivationRequests'])->name('reactivation-requests');
            Route::get('/reactivation-requests/{reactivationRequest}', [SecurityViolationController::class, 'showReactivationRequest'])->name('reactivation-requests.show');
            Route::post('/reactivation-requests/{reactivationRequest}/approve', [SecurityViolationController::class, 'approveReactivationRequest'])->name('reactivation-requests.approve');
            Route::post('/reactivation-requests/{reactivationRequest}/reject', [SecurityViolationController::class, 'rejectReactivationRequest'])->name('reactivation-requests.reject');
            Route::post('/reactivation-requests/bulk-approve', [SecurityViolationController::class, 'bulkApproveRequests'])->name('reactivation-requests.bulk-approve');
            Route::get('/api/reactivation-stats', [SecurityViolationController::class, 'reactivationStats'])->name('reactivation-stats');
        });
    });
});

// Load security routes
require __DIR__.'/security.php';

// Critical Warning Route (Outside middleware for banned students)
Route::get('/security/critical-warning', [SecurityViewController::class, 'criticalWarning'])->name('security.critical.warning');