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
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->boolean('has_security_violation')->default(false)->after('auto_submitted');
            $table->timestamp('violation_occurred_at')->nullable()->after('has_security_violation');
            $table->text('violation_reason')->nullable()->after('violation_occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['has_security_violation', 'violation_occurred_at', 'violation_reason']);
        });
    }
};