<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // DB::table('subjects')->delete(); // Optional: Clear existing

        $jss1 = ClassModel::where('name', 'JSS1')->first();
        $jss2 = ClassModel::where('name', 'JSS2')->first();
        $ss1 = ClassModel::where('name', 'SS1')->first();
        $ss2 = ClassModel::where('name', 'SS2')->first();

        $subjectsData = [];

        if ($jss1) {
            $subjectsData[] = ['name' => 'Mathematics', 'class_id' => $jss1->id, 'exam_duration_minutes' => 45];
            $subjectsData[] = ['name' => 'English Language', 'class_id' => $jss1->id, 'exam_duration_minutes' => 45];
            $subjectsData[] = ['name' => 'Basic Science', 'class_id' => $jss1->id, 'exam_duration_minutes' => 40];
            $subjectsData[] = ['name' => 'Social Studies', 'class_id' => $jss1->id, 'exam_duration_minutes' => 30];
        }
        if ($ss1) {
            $subjectsData[] = ['name' => 'Mathematics', 'class_id' => $ss1->id, 'exam_duration_minutes' => 60];
            $subjectsData[] = ['name' => 'English Language', 'class_id' => $ss1->id, 'exam_duration_minutes' => 60];
            $subjectsData[] = ['name' => 'Physics', 'class_id' => $ss1->id, 'exam_duration_minutes' => 50];
            $subjectsData[] = ['name' => 'Chemistry', 'class_id' => $ss1->id, 'exam_duration_minutes' => 50];
            $subjectsData[] = ['name' => 'Biology', 'class_id' => $ss1->id, 'exam_duration_minutes' => 45];
        }
        if ($ss2) {
            $subjectsData[] = ['name' => 'Mathematics', 'class_id' => $ss2->id, 'exam_duration_minutes' => 60];
            $subjectsData[] = ['name' => 'English Language', 'class_id' => $ss2->id, 'exam_duration_minutes' => 60];
            $subjectsData[] = ['name' => 'Literature in English', 'class_id' => $ss2->id, 'exam_duration_minutes' => 50];
            $subjectsData[] = ['name' => 'Government', 'class_id' => $ss2->id, 'exam_duration_minutes' => 45];
        }


        foreach ($subjectsData as $subject) {
            Subject::firstOrCreate(
                ['name' => $subject['name'], 'class_id' => $subject['class_id']], // Composite key for uniqueness
                $subject
            );
        }
    }
}
