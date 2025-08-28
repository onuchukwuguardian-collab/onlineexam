<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Subject;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\ExamSession;
use App\Models\Reset;
use App\Models\ClassModel;

class AdminExamResetController extends Controller
{
    /**
     * Original Exam Reset Dashboard - Bootstrap Style
     */
    public function index()
    {
        // Get statistics
        $stats = [
            'total_students' => User::whereIn('role', ['user', 'student'])->count(),
            'total_resets' => Reset::count(),
            'resets_today' => Reset::whereDate('reset_time', today())->count(),
            'total_subjects' => Subject::count(),
        ];
        
        // Get all classes for dropdown
        $classes = ClassModel::orderBy('name')->get();
        
        // Get all subjects for dropdown
        $subjects = Subject::with('classModel')->orderBy('name')->get();
        
        // Get recent resets with details
        $recentResets = Reset::with(['user', 'subject', 'resetByAdmin'])
            ->orderBy('reset_time', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.exam-reset.index', compact('stats', 'classes', 'subjects', 'recentResets'));
    }
    
    /**
     * Reset student exam by registration number
     */
    public function resetByRegistration(Request $request)
    {
        $request->validate([
            'registration_number' => 'required|string',
            'class_id' => 'required|exists:class_models,id',
            'subject_id' => 'required|exists:subjects,id',
            'reason' => 'required|string|max:500'
        ]);
        
        $admin = auth()->user();
        
        // Find student by registration number and class
        $student = User::where('registration_number', $request->registration_number)
            ->where('class_id', $request->class_id)
            ->whereIn('role', ['user', 'student'])
            ->first();
            
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with registration number: ' . $request->registration_number
            ], 404);
        }
        
        $subject = Subject::findOrFail($request->subject_id);
        
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
            
            // Record the reset in resets table
            Reset::create([
                'user_id' => $student->id,
                'subject_id' => $request->subject_id,
                'reset_by_admin_id' => $admin->id,
                'reset_time' => now(),
                'reason' => $request->reason
            ]);
            
            // Log the reset action
            Log::info('Admin reset student exam by registration', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_registration' => $student->registration_number,
                'subject_id' => $request->subject_id,
                'subject_name' => $subject->name,
                'reason' => $request->reason,
                'reset_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully reset {$student->name}'s exam for {$subject->name}",
                'student' => [
                    'name' => $student->name,
                    'registration' => $student->registration_number,
                    'email' => $student->email
                ]
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
     * Bulk reset - Reset subject for entire class
     */
    public function bulkResetClassSubject(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_models,id',
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
                $hasExam = UserScore::where('user_id', $student->id)
                    ->where('subject_id', $request->subject_id)
                    ->exists();
                    
                if ($hasExam) {
                    // Delete user's score for this subject
                    UserScore::where('user_id', $student->id)
                        ->where('subject_id', $request->subject_id)
                        ->delete();
                    
                    // Delete user's answers for this subject
                    UserAnswer::whereHas('question', function($query) use ($request) {
                        $query->where('subject_id', $request->subject_id);
                    })->where('user_id', $student->id)->delete();
                    
                    // Delete exam sessions for this subject
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
                'message' => "Successfully reset {$subject->name} for {$resetCount} students in {$class->name}",
                'details' => [
                    'class' => $class->name,
                    'subject' => $subject->name,
                    'students_reset' => $resetCount,
                    'total_students' => $students->count()
                ]
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
     * Reset subject for all students
     */
    public function resetSubjectForAll(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'reason' => 'required|string|max:500'
        ]);
        
        $admin = auth()->user();
        $subject = Subject::findOrFail($request->subject_id);
        
        DB::beginTransaction();
        try {
            // Get count of students affected
            $studentCount = UserScore::where('subject_id', $request->subject_id)->distinct('user_id')->count();
            
            // Delete all scores for this subject
            UserScore::where('subject_id', $request->subject_id)->delete();
            
            // Delete all answers for this subject
            UserAnswer::whereHas('question', function($query) use ($request) {
                $query->where('subject_id', $request->subject_id);
            })->delete();
            
            // Delete all exam sessions for this subject
            ExamSession::where('subject_id', $request->subject_id)->delete();
            
            // Log the reset action
            Log::info('Admin reset subject for all students', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'subject_id' => $request->subject_id,
                'subject_name' => $subject->name,
                'students_affected' => $studentCount,
                'reason' => $request->reason,
                'reset_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully reset {$subject->name} for {$studentCount} students"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset subject for all: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset subject. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get student's exam progress
     */
    public function getStudentProgress($studentId)
    {
        $student = User::with(['userScores.subject:id,name'])
            ->findOrFail($studentId);
        
        $progress = $student->userScores->map(function($score) {
            return [
                'subject_id' => $score->subject_id,
                'subject_name' => $score->subject->name,
                'score' => $score->score,
                'total_questions' => $score->total_questions,
                'percentage' => round(($score->score / $score->total_questions) * 100, 1),
                'completed_at' => $score->submission_time->format('M d, Y H:i')
            ];
        });
        
        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email
            ],
            'progress' => $progress
        ]);
    }
    
    /**
     * Get subjects by class
     */
    public function getSubjectsByClass(Request $request)
    {
        $classId = $request->get('class_id');
        
        $subjects = Subject::where('class_id', $classId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json($subjects);
    }
    
    /**
     * Find student by registration number
     */
    public function findStudentByRegistration(Request $request)
    {
        $request->validate([
            'registration_number' => 'required|string',
            'class_id' => 'required|exists:class_models,id'
        ]);
        
        $student = User::where('registration_number', $request->registration_number)
            ->where('class_id', $request->class_id)
            ->whereIn('role', ['user', 'student'])
            ->with('classModel:id,name')
            ->first(['id', 'name', 'email', 'registration_number', 'class_id']);
            
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with registration number: ' . $request->registration_number
            ], 404);
        }
        
        // Get student's exam history
        $examHistory = UserScore::where('user_id', $student->id)
            ->with('subject:id,name')
            ->get(['subject_id', 'score', 'total_questions', 'submission_time']);
        
        return response()->json([
            'success' => true,
            'student' => $student,
            'exam_history' => $examHistory
        ]);
    }
}