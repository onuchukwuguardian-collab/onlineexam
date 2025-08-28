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
            $table->boolean('is_active')->default(true)->after('is_permanent');
            $table->timestamp('reactivated_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('reactivated_by')->nullable()->after('reactivated_at');
            $table->string('reactivation_reason', 500)->nullable()->after('reactivated_by');
            
            $table->foreign('reactivated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_bans', function (Blueprint $table) {
            $table->dropForeign(['reactivated_by']);
            $table->dropColumn([
                'is_active',
                'reactivated_at', 
                'reactivated_by',
                'reactivation_reason'
            ]);
        });
    }
};
