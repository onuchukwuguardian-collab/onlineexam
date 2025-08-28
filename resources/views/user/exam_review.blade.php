@extends('layouts.student_app')

@section('title', 'Review: ' . $subject->name)

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Review Exam: <span class="text-indigo-600 dark:text-indigo-400">{{ $subject->name }}</span>
    </h2>
@endsection

@section('content')
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-xl p-6 md:p-8 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Your Performance</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Submitted: {{ $userScore->submission_time ? \Carbon\Carbon::parse($userScore->submission_time)->format('M d, Y \a\t H:i A') : 'N/A' }}</p>
                    </div>
                    <div class="mt-3 sm:mt-0 text-lg sm:text-xl font-semibold text-gray-800 dark:text-gray-100">
                        Score: <span class="text-indigo-600 dark:text-indigo-400">{{ $userScore->score }} / {{ $userScore->total_questions }}</span>
                        @if($userScore->total_questions > 0)
                            ({{ round(($userScore->score / $userScore->total_questions) * 100, 1) }}%)
                        @endif
                    </div>
                </div>

                @if($questions->isEmpty())
                    <p class="text-center text-gray-500 dark:text-gray-400">No questions found for this review.</p>
                @else
                    <div class="space-y-8">
                        @foreach($questions as $index => $question)
                            @php
                                $userAnswerForThisQuestion = $userAnswers->get($question->id);
                                $selectedOptionLetter = $userAnswerForThisQuestion ? $userAnswerForThisQuestion->selected_option_letter : null;
                                $isCorrect = $userAnswerForThisQuestion ? $userAnswerForThisQuestion->is_correct : false;
                            @endphp
                            <div class="p-5 border rounded-lg dark:border-gray-700 {{ $isCorrect ? 'bg-green-50 dark:bg-green-900/30 border-green-300 dark:border-green-700' : ($selectedOptionLetter ? 'bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-700' : 'bg-gray-50 dark:bg-gray-700/30') }}">
                                <p class="font-semibold text-md mb-1 text-gray-800 dark:text-gray-100">
                                    Question {{ $index + 1 }}:
                                    @if($selectedOptionLetter)
                                        @if($isCorrect)
                                            <span class="ml-2 text-xs font-bold px-2 py-0.5 bg-green-500 text-white rounded-full">Correct</span>
                                        @else
                                            <span class="ml-2 text-xs font-bold px-2 py-0.5 bg-red-500 text-white rounded-full">Incorrect</span>
                                        @endif
                                    @else
                                         <span class="ml-2 text-xs font-bold px-2 py-0.5 bg-gray-400 text-white rounded-full">Skipped</span>
                                    @endif
                                </p>
                                <div class="prose prose-sm sm:prose-base dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 mb-3 leading-relaxed">
                                    {!! nl2br(e($question->question_text)) !!}
                                </div>
                                @if($question->image_path)
                                    <div class="my-3">
                                        <img src="{{ asset('storage/' . $question->image_path) }}" alt="Question Image" class="max-w-xs max-h-40 rounded border dark:border-gray-600">
                                    </div>
                                @endif

                                <ul class="space-y-2 text-sm">
                                    @foreach($question->options as $option)
                                        <li class="flex items-center p-2 rounded-md 
                                            {{ $option->option_letter == $question->correct_answer ? 'bg-green-100 dark:bg-green-800/50 text-green-700 dark:text-green-300 font-semibold ring-1 ring-green-400' : '' }}
                                            {{ $option->option_letter == $selectedOptionLetter && !$isCorrect ? 'bg-red-100 dark:bg-red-800/50 text-red-700 dark:text-red-300 ring-1 ring-red-400' : '' }}
                                            {{ $option->option_letter == $selectedOptionLetter && $isCorrect ? 'font-semibold' : '' }}
                                            {{ !$selectedOptionLetter && $option->option_letter == $question->correct_answer ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-300' }}
                                            ">
                                            <span class="mr-2 font-medium">{{ $option->option_letter }})</span>
                                            <span>{{ $option->option_text }}</span>
                                            @if($option->option_letter == $question->correct_answer)
                                                <i class="fas fa-check text-green-500 ml-auto" title="Correct Answer"></i>
                                            @endif
                                            @if($option->option_letter == $selectedOptionLetter && !$isCorrect)
                                                <i class="fas fa-times text-red-500 ml-auto" title="Your Incorrect Answer"></i>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if($selectedOptionLetter && !$isCorrect)
                                    <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                                        Correct Answer was: <strong>{{ $question->correct_answer }}</strong>
                                    </p>
                                @elseif(!$selectedOptionLetter)
                                     <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        You did not select an answer. Correct was: <strong>{{ $question->correct_answer }}</strong>
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                 <div class="mt-8 text-center">
                    <a href="{{ route('user.score.display', $subject->id) }}" class="admin-btn-secondary mr-3">
                        <i class="fas fa-poll-h mr-2"></i>Back to Score Summary
                    </a>
                    <a href="{{ route('user.dashboard') }}" class="admin-btn-primary">
                        <i class="fas fa-th-large mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
