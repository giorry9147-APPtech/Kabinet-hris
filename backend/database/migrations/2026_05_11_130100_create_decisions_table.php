<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->string('decision_number', 60)->unique();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('decision_text');
            $table->date('decided_at');
            $table->foreignId('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('decided_at');
            $table->index('deadline');
            $table->index('status');
            $table->index('responsible_employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
