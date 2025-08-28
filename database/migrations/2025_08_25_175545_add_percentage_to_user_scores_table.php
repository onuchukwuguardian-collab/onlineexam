<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_scores', function (Blueprint $table) {
            $table->decimal('percentage', 5, 2)->nullable()->after('total_questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_scores', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
    }
};