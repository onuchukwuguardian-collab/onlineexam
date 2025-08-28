<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserScore;
use App\Models\User;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection; // For type hinting
use Illuminate\Support\Str;

class ScoreboardController extends Controller
{
    public function index(Request $request)
    {
        $classes = ClassModel::orderBy('name')->get();
        $allSubjectsForFilter = Subject::orderBy('name')->get(); // For general filter if needed

        $selectedClass = null;
        $classSubjects = collect(); // Initialize as empty Laravel Collection
        $studentsPerformance = collect(); // Initialize as empty Laravel Collection
        $classHasScores = false;

        $selectedClassId = $request->input('class_id_filter');

        if ($selectedClassId) {
            $selectedClass = ClassModel::find($selectedClassId);

            if ($selectedClass) {
                // Get subjects specifically for the selected class
                $classSubjects = Subject::where('class_id', $selectedClass->id)
                    ->orderBy('name')
                    ->get();

                // Get students belonging to the selected class with class relationship
                $studentsInClass = User::with('classModel')
                    ->where('class_id', $selectedClass->id)
                    ->whereIn('role', ['user', 'student'])
                    ->orderBy('name')
                    ->get();

                if ($studentsInClass->isNotEmpty() && $classSubjects->isNotEmpty()) {
                    // Fetch scores for these students ONLY for the subjects of THIS class
                    $scores = UserScore::whereIn('user_id', $studentsInClass->pluck('id'))
                        ->whereIn('subject_id', $classSubjects->pluck('id'))
                        ->with('subject:id,name') // Eager load for efficiency
                        ->get();

                    if ($scores->isNotEmpty()) {
                        $classHasScores = true;
                    }

                    $studentsPerformance = $studentsInClass->map(function ($student) use ($classSubjects, $scores) {
                        $studentScoresData = []; // [subject_id => ['score' => X, 'total_questions' => Y]]
                        $totalScore = 0;
                        $totalPossibleScoreAcrossTakenSubjects = 0;
                        $subjectsTakenCount = 0;

                        foreach ($classSubjects as $subject) {
                            $scoreEntry = $scores->firstWhere(function ($s) use ($student, $subject) {
                                return $s->user_id == $student->id && $s->subject_id == $subject->id;
                            });

                            if ($scoreEntry) {
                                $studentScoresData[$subject->id] = [
                                    'score' => $scoreEntry->score,
                                    'total_questions' => $scoreEntry->total_questions
                                ];
                                $totalScore += $scoreEntry->score;
                                if ($scoreEntry->total_questions > 0) { // Only count if exam had questions
                                    $totalPossibleScoreAcrossTakenSubjects += $scoreEntry->total_questions;
                                    $subjectsTakenCount++;
                                }
                            } else {
                                $studentScoresData[$subject->id] = ['score' => '-', 'total_questions' => '-'];
                            }
                        }

                        $averagePercentage = 0;
                        if ($subjectsTakenCount > 0 && $totalPossibleScoreAcrossTakenSubjects > 0) {
                            $averagePercentage = round(($totalScore / $totalPossibleScoreAcrossTakenSubjects) * 100, 1);
                        }

                        return (object) [
                            'id' => $student->id,
                            'name' => $student->name,
                            'registration_number' => $student->registration_number ?? 'N/A',
                            'unique_id' => $student->unique_id ?? 'N/A',
                            'email' => $student->email ?? 'N/A',
                            'class_name' => $student->classModel->name ?? 'N/A',
                            'scores_data' => $studentScoresData, // Now contains score and total_questions
                            'total_score' => $totalScore,
                            'total_possible_score' => $totalPossibleScoreAcrossTakenSubjects,
                            'average_percentage' => $averagePercentage,
                            'subjects_taken_count' => $subjectsTakenCount, // For reference
                            'subjects_available_count' => $classSubjects->count(),
                        ];
                    });

                    // Calculate position based on total_score
                    if ($studentsPerformance->isNotEmpty()) {
                        $studentsPerformance = $studentsPerformance->sortByDesc('total_score')->values(); // Sort and re-index
                        $rank = 0;
                        $lastScore = -1; // Initialize with a score that won't match
                        $studentsPerformance = $studentsPerformance->map(function ($student, $key) use (&$rank, &$lastScore) {
                            if ($student->total_score !== $lastScore || $key === 0) { // Also assign rank for the first student
                                $rank = $key + 1;
                            }
                            $student->position = $rank;
                            $lastScore = $student->total_score;

                            // Ordinal suffix
                            if ($rank % 100 >= 11 && $rank % 100 <= 13) {
                                $student->position_suffix = 'th';
                            } else {
                                switch ($rank % 10) {
                                    case 1:
                                        $student->position_suffix = 'st';
                                        break;
                                    case 2:
                                        $student->position_suffix = 'nd';
                                        break;
                                    case 3:
                                        $student->position_suffix = 'rd';
                                        break;
                                    default:
                                        $student->position_suffix = 'th';
                                        break;
                                }
                            }
                            return $student;
                        });
                    }
                }
            }
        }
        // dd($selectedClass, $classSubjects->toArray(), $studentsPerformance->toArray()); // DEBUG

        return view('admin.scoreboard.index', compact(
            'classes',
            'selectedClass',
            'classSubjects',
            'studentsPerformance',
            'allSubjectsForFilter', // Used for the general subject filter dropdown if needed elsewhere
            'classHasScores'
        ));
    }
    
    public function export(Request $request, $format)
    {
        $classId = $request->input('class_id');
        $selectedClass = ClassModel::find($classId);
        
        if (!$selectedClass) {
            return redirect()->back()->with('error', 'Class not found.');
        }
        
        // Get the same data as the index method
        $classSubjects = Subject::where('class_id', $selectedClass->id)->orderBy('name')->get();
        $studentsInClass = User::with('classModel')->where('class_id', $selectedClass->id)->whereIn('role', ['user', 'student'])->orderBy('name')->get();
        
        if ($studentsInClass->isEmpty() || $classSubjects->isEmpty()) {
            return redirect()->back()->with('error', 'No data available for export.');
        }
        
        $scores = UserScore::whereIn('user_id', $studentsInClass->pluck('id'))
            ->whereIn('subject_id', $classSubjects->pluck('id'))
            ->with('subject:id,name')
            ->get();
            
        $studentsPerformance = $studentsInClass->map(function ($student) use ($classSubjects, $scores) {
            $studentScoresData = [];
            $totalScore = 0;
            $totalPossibleScoreAcrossTakenSubjects = 0;
            $subjectsTakenCount = 0;

            foreach ($classSubjects as $subject) {
                $scoreEntry = $scores->firstWhere(function ($s) use ($student, $subject) {
                    return $s->user_id == $student->id && $s->subject_id == $subject->id;
                });

                if ($scoreEntry) {
                    $studentScoresData[$subject->id] = [
                        'score' => $scoreEntry->score,
                        'total_questions' => $scoreEntry->total_questions
                    ];
                    $totalScore += $scoreEntry->score;
                    if ($scoreEntry->total_questions > 0) {
                        $totalPossibleScoreAcrossTakenSubjects += $scoreEntry->total_questions;
                        $subjectsTakenCount++;
                    }
                } else {
                    $studentScoresData[$subject->id] = ['score' => '-', 'total_questions' => '-'];
                }
            }

            $averagePercentage = 0;
            if ($subjectsTakenCount > 0 && $totalPossibleScoreAcrossTakenSubjects > 0) {
                $averagePercentage = round(($totalScore / $totalPossibleScoreAcrossTakenSubjects) * 100, 1);
            }

            return (object) [
                'id' => $student->id,
                'name' => $student->name,
                'registration_number' => $student->registration_number,
                'class_name' => $student->classModel->name ?? 'N/A',
                'scores_data' => $studentScoresData,
                'total_score' => $totalScore,
                'average_percentage' => $averagePercentage,
                'subjects_taken_count' => $subjectsTakenCount,
            ];
        });

        // Sort by total score
        $studentsPerformance = $studentsPerformance->sortByDesc('total_score')->values();
        
        // Add positions
        $rank = 0;
        $lastScore = -1;
        $studentsPerformance = $studentsPerformance->map(function ($student, $key) use (&$rank, &$lastScore) {
            if ($student->total_score !== $lastScore || $key === 0) {
                $rank = $key + 1;
            }
            $student->position = $rank;
            $lastScore = $student->total_score;
            return $student;
        });
        
        $filename = 'scoreboard_' . str_replace(' ', '_', $selectedClass->name) . '_' . now()->format('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($studentsPerformance, $classSubjects, $filename);
            case 'excel':
                return $this->exportToExcel($studentsPerformance, $classSubjects, $filename);
            default:
                return redirect()->back()->with('error', 'Invalid export format.');
        }
    }
    
    private function exportToCsv($studentsPerformance, $classSubjects, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];
        
        $callback = function() use ($studentsPerformance, $classSubjects) {
            $file = fopen('php://output', 'w');
            
            // Find subjects that actually have scores (to avoid empty columns)
            $subjectsWithScores = [];
            foreach ($classSubjects as $subject) {
                $hasScores = false;
                foreach ($studentsPerformance as $student) {
                    $scoreData = $student->scores_data[$subject->id] ?? null;
                    if ($scoreData && $scoreData['score'] !== '-') {
                        $hasScores = true;
                        break;
                    }
                }
                if ($hasScores) {
                    $subjectsWithScores[] = $subject;
                }
            }
            
            // Header row - only include subjects with actual scores
            $header = ['Student Name', 'Registration Number', 'Class'];
            foreach ($subjectsWithScores as $subject) {
                $header[] = $subject->name;
            }
            $header = array_merge($header, ['Total Score', 'Average %', 'Position']);
            fputcsv($file, $header);
            
            // Data rows
            foreach ($studentsPerformance as $student) {
                $row = [$student->name, $student->registration_number ?? 'N/A', $student->class_name ?? 'N/A'];
                
                // Only include subjects with scores
                foreach ($subjectsWithScores as $subject) {
                    $scoreData = $student->scores_data[$subject->id] ?? null;
                    $score = $scoreData['score'] ?? '-';
                    
                    $row[] = $score;
                }
                
                $row = array_merge($row, [
                    $student->total_score,
                    $student->average_percentage . '%',
                    $student->position
                ]);
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function exportToExcel($studentsPerformance, $classSubjects, $filename)
    {
        // For now, return CSV format with Excel headers
        // You can integrate with PhpSpreadsheet for true Excel format
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
        ];
        
        return $this->exportToCsv($studentsPerformance, $classSubjects, $filename);
    }
    
    public function exportCsv(Request $request)
    {
        return $this->export($request, 'csv');
    }
    
    public function exportExcel(Request $request)
    {
        return $this->export($request, 'excel');
    }
    
    public function customExport(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_models,id',
            'format' => 'required|in:csv,excel,pdf',
            'columns' => 'array',
            'include_summary' => 'boolean'
        ]);
        
        return $this->export($request, $request->format);
    }
}
