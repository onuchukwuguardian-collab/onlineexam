<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserScore;
use App\Models\Subject;
use App\Models\Question;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // --- General Statistics ---
        $totalRegisteredStudents = User::whereIn('role', ['user', 'student'])->count();
        $totalExamTakers = UserScore::distinct('user_id')->count('user_id');
        $totalSubjects = Subject::count();
        $totalQuestions = Question::count();
        
        // --- Additional Statistics ---
        $totalClasses = ClassModel::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $averageScore = UserScore::avg('score') ?? 0;
        $totalExamsCompleted = UserScore::count();

        // --- Overall Best Student (across all classes/subjects) ---
        $overallBestStudentQuery = UserScore::join('users', 'user_scores.user_id', '=', 'users.id')
            ->select('users.name as student_name', DB::raw('SUM(user_scores.score) as total_score_sum'))
            ->whereIn('users.role', ['user', 'student']) // Ensure only students
            ->groupBy('user_scores.user_id', 'users.name')
            ->orderByDesc('total_score_sum')
            ->orderBy('users.name') // Secondary sort for ties
            ->first();

        $overallBestStudent = $overallBestStudentQuery ?
            $overallBestStudentQuery->student_name . ' (' . $overallBestStudentQuery->total_score_sum . ' pts)' :
            'N/A';


        // --- Best Student Per Class ---
        $classes = ClassModel::whereIn('level_group', ['JSS', 'SS'])->orderBy('name')->get();
        $bestStudentPerClass = [];

        foreach ($classes as $class) {
            // Find users belonging to this class (check both 'user' and 'student' roles)
            $studentsInClassIds = User::where('class_id', $class->id)
                ->whereIn('role', ['user', 'student'])
                ->pluck('id');

            if ($studentsInClassIds->isNotEmpty()) {
                $bestInClass = UserScore::whereIn('user_id', $studentsInClassIds)
                    ->join('users', 'user_scores.user_id', '=', 'users.id')
                    ->select('users.name as student_name', DB::raw('SUM(user_scores.score) as total_score_sum'))
                    ->groupBy('user_scores.user_id', 'users.name')
                    ->orderByDesc('total_score_sum')
                    ->orderBy('users.name')
                    ->first();

                $bestStudentPerClass[$class->name] = $bestInClass ?
                    $bestInClass->student_name . ' (' . $bestInClass->total_score_sum . ' pts)' :
                    'No scores yet';
            } else {
                $bestStudentPerClass[$class->name] = 'No students';
            }
        }

        // --- Chart Data: Performance by Class ---
        $performanceByClass = [];
        $classLabels = [];
        
        foreach ($classes as $class) {
            $studentsInClassIds = User::where('class_id', $class->id)
                ->whereIn('role', ['user', 'student'])
                ->pluck('id');
            
            if ($studentsInClassIds->isNotEmpty()) {
                $classAverage = UserScore::whereIn('user_id', $studentsInClassIds)
                    ->selectRaw('AVG((user_scores.score / user_scores.total_questions) * 100) as avg_percentage')
                    ->value('avg_percentage');
                
                $performanceByClass[] = round($classAverage ?? 0, 1);
                $classLabels[] = $class->name;
            }
        }
        
        // Add overall average
        $overallAverage = UserScore::selectRaw('AVG((user_scores.score / user_scores.total_questions) * 100) as avg_percentage')
            ->value('avg_percentage');
        
        $performanceByClass[] = round($overallAverage ?? 0, 1);
        $classLabels[] = 'Overall';

        // --- Chart Data: Subject Distribution ---
        $subjectDistribution = Subject::leftJoin('questions', 'subjects.id', '=', 'questions.subject_id')
            ->select('subjects.name', DB::raw('COUNT(questions.id) as question_count'))
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc('question_count')
            ->get();

        $subjectLabels = $subjectDistribution->pluck('name')->toArray();
        $subjectData = $subjectDistribution->pluck('question_count')->toArray();

        // If no subjects, provide default data
        if (empty($subjectLabels)) {
            $subjectLabels = ['No Subjects'];
            $subjectData = [0];
        }

        // --- Chart Data: Performance Distribution ---
        $performanceDistribution = UserScore::selectRaw('
                SUM(CASE WHEN (user_scores.score / user_scores.total_questions) * 100 >= 80 THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN (user_scores.score / user_scores.total_questions) * 100 >= 70 AND (user_scores.score / user_scores.total_questions) * 100 < 80 THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN (user_scores.score / user_scores.total_questions) * 100 >= 50 AND (user_scores.score / user_scores.total_questions) * 100 < 70 THEN 1 ELSE 0 END) as average,
                SUM(CASE WHEN (user_scores.score / user_scores.total_questions) * 100 < 50 THEN 1 ELSE 0 END) as poor
            ')
            ->first();

        $performanceLabels = ['Excellent (80%+)', 'Good (70-79%)', 'Average (50-69%)', 'Poor (<50%)'];
        $performanceData = [
            $performanceDistribution->excellent ?? 0,
            $performanceDistribution->good ?? 0,
            $performanceDistribution->average ?? 0,
            $performanceDistribution->poor ?? 0
        ];

        return view('admin.dashboard', compact(
            'totalRegisteredStudents',
            'totalExamTakers',
            'totalSubjects',
            'totalQuestions',
            'totalClasses',
            'totalAdmins',
            'averageScore',
            'totalExamsCompleted',
            'overallBestStudent',
            'bestStudentPerClass',
            'performanceByClass',
            'classLabels',
            'subjectLabels',
            'subjectData',
            'performanceLabels',
            'performanceData'
        ));
    }
}
