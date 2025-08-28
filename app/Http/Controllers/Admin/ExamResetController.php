<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\ExamSession;
use App\Models\Reset;

class ExamResetController extends Controller
{
    /**
     * Display the exam reset page
     */
    public function index()
    {
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::with('classModel')->orderBy('name')->get();
        
        // Get recent resets for display
        $recentResets = Reset::with(['user', 'subject', 'resetByAdmin'])
            ->orderBy('reset_time', 'desc')
            ->limit(10)
            ->get();
        
        // Get statistics
        $stats = [
            'total_resets_today' => Reset::whereDate('reset_time', today())->count(),
            'total_resets_week' => Reset::where('reset_time', '>=', now()->subWeek())->count(),
            'total_students' => User::whereIn('role', ['user', 'student'])->count(),
            'total_subjects' => Subject::count(),
        ];
        
        return view('admin.exam-reset.index', compact('classes', 'subjects', 'recentResets', 'stats'));
    }
    
    /**
     * Reset individual student exam
     */
    public function resetStudent(Request $request)
    {
        $searchType = $request->input('search_type', 'registration');
        
        if ($searchType === 'registration') {
            $request->validate([
                'registration_number' => 'required|string',
                'class_id' => 'required|exists:classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'reason' => 'required|string|max:500'
            ]);
            
            // Find student by registration number and class
            $student = User::where('registration_number', $request->registration_number)
                ->where('class_id', $request->class_id)
                ->whereIn('role', ['user', 'student'])
                ->first();
                
            $notFoundMessage = 'Student not found with registration number ' . $request->registration_number . ' in the selected class.';
        } else if ($searchType === 'email') {
            $request->validate([
                'email' => 'required|email',
                'subject_id' => 'required|exists:subjects,id',
                'reason' => 'required|string|max:500'
            ]);
            
            // Find student by email
            $student = User::where('email', $request->email)
                ->whereIn('role', ['user', 'student'])
                ->first();
                
            $notFoundMessage = 'Student not found with email: ' . $request->email;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search type'
            ], 400);
        }
        
        $admin = auth()->user();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => $notFoundMessage
            ], 404);
        }
        
        DB::beginTransaction();
        try {
            // Delete user's score for this subject
            UserScore::where('user_id', $student->id)
                ->where('subject_id', $request->subject_id)
                ->delete();
            
            // Delete user's answers for this subject
            UserAnswer::whereHas('question', function($query) use ($request) {
                $query->where('subject_id', $request->subject_id);
            })->where('user_id', $student->id)->delete();
            
            // Delete/deactivate exam sessions for this subject
            ExamSession::where('user_id', $student->id)
                ->where('subject_id', $request->subject_id)
                ->delete();
            
            // Record the reset
            Reset::create([
                'user_id' => $student->id,
                'subject_id' => $request->subject_id,
                'reset_by_admin_id' => $admin->id,
                'reset_time' => now(),
                'reason' => $request->reason
            ]);
            
            // Log the reset action
            Log::info('Admin reset student exam', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_registration' => $student->registration_number,
                'subject_id' => $request->subject_id,
                'reason' => $request->reason,
                'reset_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully reset exam for {$student->name} (Reg: {$student->registration_number})"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset student exam: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset exam. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Bulk reset - reset subject for entire class
     */
    public function bulkReset(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'reason' => 'required|string|max:500'
        ]);
        
        $admin = auth()->user();
        $class = ClassModel::findOrFail($request->class_id);
        $subject = Subject::findOrFail($request->subject_id);
        
        // Get all students in the class
        $students = User::where('class_id', $request->class_id)
            ->whereIn('role', ['user', 'student'])
            ->get();
        
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No students found in the selected class.'
            ], 404);
        }
        
        DB::beginTransaction();
        try {
            $resetCount = 0;
            
            foreach ($students as $student) {
                // Check if student has taken this exam
                $hasScore = UserScore::where('user_id', $student->id)
                    ->where('subject_id', $request->subject_id)
                    ->exists();
                
                if ($hasScore) {
                    // Delete user's score for this subject
                    UserScore::where('user_id', $student->id)
                        ->where('subject_id', $request->subject_id)
                        ->delete();
                    
                    // Delete user's answers for this subject
                    UserAnswer::whereHas('question', function($query) use ($request) {
                        $query->where('subject_id', $request->subject_id);
                    })->where('user_id', $student->id)->delete();
                    
                    // Delete/deactivate exam sessions for this subject
                    ExamSession::where('user_id', $student->id)
                        ->where('subject_id', $request->subject_id)
                        ->delete();
                    
                    // Record the reset
                    Reset::create([
                        'user_id' => $student->id,
                        'subject_id' => $request->subject_id,
                        'reset_by_admin_id' => $admin->id,
                        'reset_time' => now(),
                        'reason' => $request->reason . ' (Bulk Reset)'
                    ]);
                    
                    $resetCount++;
                }
            }
            
            // Log the bulk reset action
            Log::info('Admin performed bulk reset', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'class_id' => $request->class_id,
                'class_name' => $class->name,
                'subject_id' => $request->subject_id,
                'subject_name' => $subject->name,
                'students_reset' => $resetCount,
                'total_students_in_class' => $students->count(),
                'reason' => $request->reason,
                'reset_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully reset {$subject->name} for {$resetCount} students in {$class->name}"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to perform bulk reset: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk reset. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get subjects for a specific class
     */
    public function getSubjectsForClass($classId)
    {
        $subjects = Subject::where('class_id', $classId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json($subjects);
    }
    
    /**
     * Search student by registration number or email
     */
    public function searchStudent(Request $request)
    {
        $searchType = $request->input('search_type', 'registration');
        
        if ($searchType === 'registration') {
            $request->validate([
                'registration_number' => 'required|string',
                'class_id' => 'required|exists:classes,id'
            ]);
            
            $student = User::where('registration_number', $request->registration_number)
                ->where('class_id', $request->class_id)
                ->whereIn('role', ['user', 'student'])
                ->with(['userScores.subject'])
                ->first();
                
            $notFoundMessage = 'Student not found with registration number: ' . $request->registration_number;
        } else if ($searchType === 'email') {
            $request->validate([
                'email' => 'required|email'
            ]);
            
            $student = User::where('email', $request->email)
                ->whereIn('role', ['user', 'student'])
                ->with(['userScores.subject', 'classModel'])
                ->first();
                
            $notFoundMessage = 'Student not found with email: ' . $request->email;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search type'
            ], 400);
        }
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => $notFoundMessage
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'registration_number' => $student->registration_number,
                'class_name' => $student->classModel->name ?? 'N/A',
                'class_id' => $student->class_id,
                'completed_exams' => $student->userScores->map(function($score) {
                    return [
                        'subject_name' => $score->subject->name,
                        'score' => $score->score,
                        'total_questions' => $score->total_questions,
                        'percentage' => round(($score->score / $score->total_questions) * 100, 1),
                        'completed_at' => $score->submission_time->format('M d, Y H:i')
                    ];
                })
            ]
        ]);
    }
}