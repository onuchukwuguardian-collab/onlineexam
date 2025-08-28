<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SecurityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Security API Routes
Route::prefix('security')->group(function () {
    Route::post('/violation', [SecurityController::class, 'reportViolation'])
        ->name('api.security.violation');
        
    Route::get('/check-ban', [SecurityController::class, 'checkBanStatus'])
        ->name('api.security.check-ban');
        
    Route::post('/request-reactivation', [SecurityController::class, 'requestReactivation'])
        ->name('api.security.request-reactivation');
});

// Exam Progress API Routes
Route::prefix('exam')->group(function () {
    Route::post('/save-progress', function (Request $request) {
        // This is a placeholder endpoint
        // In a real implementation, this would save the exam progress
        return response()->json([
            'success' => true,
            'message' => 'Progress saved successfully.',
            'expired' => false
        ]);
    })->name('api.exam.save-progress');
    
    Route::post('/submit', function (Request $request) {
        // This is a placeholder endpoint
        // In a real implementation, this would submit the exam
        return response()->json([
            'success' => true,
            'message' => 'Exam submitted successfully.'
        ]);
    })->name('api.exam.submit');
});