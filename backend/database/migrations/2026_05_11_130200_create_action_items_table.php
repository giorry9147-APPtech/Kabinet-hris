<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');
            $table->dateTime('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->timestamps();

            $table->index('due_date');
            $table->index('status');
            $table->index(['assignee_employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_items');
    }
};
