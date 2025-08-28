<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SecurityViewController;

// Security Routes
Route::prefix('security')->middleware('auth')->group(function () {
    Route::get('/violation-detected', [SecurityViewController::class, 'violationDetected'])
        ->name('security.violation-detected');
        
    Route::get('/critical-warning', [SecurityViewController::class, 'criticalWarning'])
        ->name('security.critical-warning');
    
    // Student reactivation request
    Route::post('/reactivation-request', [SecurityViewController::class, 'submitReactivationRequest'])
        ->name('student.reactivation.request');
});