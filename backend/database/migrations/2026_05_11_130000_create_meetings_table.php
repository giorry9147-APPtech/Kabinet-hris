<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_number', 60)->unique();
            $table->string('title');
            $table->string('type', 40);
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('chair_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 30)->default('planned');
            $table->text('agenda')->nullable();
            $table->text('external_attendees')->nullable();
            $table->text('notes')->nullable();

            // Notulen-velden (op de vergadering zelf)
            $table->string('minutes_status', 30)->default('none');
            $table->text('minutes_content')->nullable();
            $table->string('minutes_signed_by')->nullable();
            $table->date('minutes_finalized_at')->nullable();

            $table->timestamps();

            $table->index('scheduled_at');
            $table->index('type');
            $table->index('status');
        });

        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('participant');
            $table->boolean('attended')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('meetings');
    }
};
