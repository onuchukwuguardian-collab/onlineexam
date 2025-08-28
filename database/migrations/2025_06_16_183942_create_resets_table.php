<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('reset_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reset_time')->useCurrent();
            $table->text('reason')->nullable();
            // No unique constraint here, a user can be reset multiple times for the same subject
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('resets');
    }
};
