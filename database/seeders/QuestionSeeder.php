<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Optional: Clear existing questions and options
        // DB::table('options')->delete();
        // DB::table('questions')->delete();

        // --- JSS1 Mathematics ---
        $jss1Math = Subject::where('name', 'Mathematics')->whereHas('classModel', function ($q) {
            $q->where('name', 'JSS1');
        })->first();

        if ($jss1Math) {
            $q1 = $jss1Math->questions()->firstOrCreate(
                ['question_text' => 'What is 2 + 2?'],
                ['correct_answer' => 'B']
            );
            $q1->options()->createMany([
                ['option_letter' => 'A', 'option_text' => '3'],
                ['option_letter' => 'B', 'option_text' => '4'],
                ['option_letter' => 'C', 'option_text' => '5'],
                ['option_letter' => 'D', 'option_text' => '22'],
            ]);

            $q2 = $jss1Math->questions()->firstOrCreate(
                ['question_text' => 'If a square has a side of 3cm, what is its perimeter?'],
                ['correct_answer' => 'C']
            );
            $q2->options()->createMany([
                ['option_letter' => 'A', 'option_text' => '6cm'],
                ['option_letter' => 'B', 'option_text' => '9cm'],
                ['option_letter' => 'C', 'option_text' => '12cm'],
                ['option_letter' => 'D', 'option_text' => '3cm'],
            ]);
        }

        // --- SS1 Physics ---
        $ss1Physics = Subject::where('name', 'Physics')->whereHas('classModel', function ($q) {
            $q->where('name', 'SS1');
        })->first();

        if ($ss1Physics) {
            $q3 = $ss1Physics->questions()->firstOrCreate(
                ['question_text' => 'Which of these is a unit of Force?'],
                ['correct_answer' => 'A']
            );
            $q3->options()->createMany([
                ['option_letter' => 'A', 'option_text' => 'Newton'],
                ['option_letter' => 'B', 'option_text' => 'Joule'],
                ['option_letter' => 'C', 'option_text' => 'Watt'],
                ['option_letter' => 'D', 'option_text' => 'Pascal'],
            ]);

            $q4 = $ss1Physics->questions()->firstOrCreate(
                ['question_text' => 'What type of energy does a stretched rubber band possess?'],
                ['correct_answer' => 'D']
            );
            $q4->options()->createMany([
                ['option_letter' => 'A', 'option_text' => 'Kinetic Energy'],
                ['option_letter' => 'B', 'option_text' => 'Heat Energy'],
                ['option_letter' => 'C', 'option_text' => 'Sound Energy'],
                ['option_letter' => 'D', 'option_text' => 'Potential Energy'],
            ]);
        }

        // Add more questions for other subjects and classes as needed...
    }
}
