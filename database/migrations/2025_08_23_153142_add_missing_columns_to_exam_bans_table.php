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
        Schema::table('exam_bans', function (Blueprint $table) {
            // Add missing columns that are expected by the model
            $table->boolean('is_active')->default(true)->after('is_permanent');
            $table->timestamp('reactivated_at')->nullable()->after('admin_notes');
            $table->foreignId('reactivated_by')->nullable()->constrained('users')->onDelete('set null')->after('reactivated_at');
            $table->text('reactivation_reason')->nullable()->after('reactivated_by');
            
            // Add index for the new is_active column
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_bans', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn(['is_active', 'reactivated_at', 'reactivated_by', 'reactivation_reason']);
        });
    }
};
