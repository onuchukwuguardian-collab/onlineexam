<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->char('option_letter', 1);
            $table->text('option_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
