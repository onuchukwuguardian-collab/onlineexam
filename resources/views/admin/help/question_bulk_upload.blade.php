@extends('layouts.admin')

@section('title', '- Bulk Question Upload Instructions')

@section('headerContent')
    <h3 class="font-bold pl-2 text-xl md:text-2xl text-white">Bulk Question Upload Instructions</h3>
@endsection

@section('content')
    <div class="admin-card space-y-4">
        <p class="text-lg font-semibold text-gray-800 dark:text-gray-100">How to Prepare Your CSV File for Bulk Question Upload</p>

        <p class="dark:text-gray-300">To bulk upload questions for a subject, please prepare a CSV (Comma Separated Values) file with the following columns in the first row (header):</p>
        
        <ul class="list-disc list-inside dark:text-gray-300 space-y-1 pl-4">
            <li><strong>question_text</strong> (Required): The main text of the question.</li>
            <li><strong>correct_answer</strong> (Required): The letter (A, B, C, D, or E) corresponding to the correct option.</li>
            <li><strong>option_a_text</strong> (Required): The text for option A.</li>
            <li><strong>option_b_text</strong> (Required): The text for option B.</li>
            <li><strong>option_c_text</strong> (Optional): The text for option C.</li>
            <li><strong>option_d_text</strong> (Optional): The text for option D.</li>
            <li><strong>option_e_text</strong> (Optional): The text for option E (if you support up to 5 options).</li>
            <li><strong>image_filename</strong> (Optional): If the question has an image, provide the filename (e.g., `q1_image.png`).
                <ul class="list-disc list-inside ml-6 text-sm">
                    <li>Currently, you need to manually upload these images to a specific server folder (e.g., `public/storage/question_images_bulk/`) before running the CSV import, and the system will try to link them based on this filename.
                    </li>
                    <li>Future enhancements could include a ZIP upload of images alongside the CSV.</li>
                </ul>
            </li>
        </ul>

        <p class="dark:text-gray-300 mt-4"><strong>Important Notes:</strong></p>
        <ul class="list-disc list-inside dark:text-gray-300 space-y-1 pl-4">
            <li>The first row of your CSV file **must** be the header row with these exact column names (case-insensitive).</li>
            <li>Each subsequent row represents one question.</li>
            <li>Ensure you have at least two options (option_a_text, option_b_text) for each question.</li>
            <li>The `correct_answer` letter must match one of the option letters you define for that question (A, B, C, D, E).</li>
            <li>Text within cells should not contain commas unless enclosed in double quotes (standard CSV practice). If using Excel or Google Sheets, saving as CSV usually handles this.</li>
        </ul>

        <div class="mt-6">
            <h5 class="font-semibold mb-2 dark:text-gray-200">Example CSV Rows:</h5>
            <pre class="bg-gray-100 dark:bg-gray-700 p-3 rounded-md text-xs overflow-x-auto"><code>question_text,correct_answer,option_a_text,option_b_text,option_c_text,option_d_text,image_filename
"What is the capital of France?",B,London,Paris,Berlin,Rome,
"Solve for x: 2x + 3 = 7",A,2,3,4,5,math_q2.png
"Which planet is known as the Red Planet?",D,Earth,Jupiter,Venus,Mars,</code></pre>
        </div>

         <p class="mt-6 dark:text-gray-300">
             To upload, navigate to the "Subjects & Questions" page, select "Manage Questions" for the desired subject, and then use the "Bulk Upload Questions" button.
         </p>
    </div>
@endsection
