<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user'); // 'user', 'admin'
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
            // Add unique_id if you are using it for login instead of email/password
            // $table->string('unique_id')->unique()->nullable(); // If using registration numbers for login
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn(['role', 'class_id']);
            // $table->dropColumn('unique_id');
        });
    }
};
