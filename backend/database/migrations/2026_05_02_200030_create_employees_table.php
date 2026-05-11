<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 50)->unique();
            $table->string('first_name', 120);
            $table->string('middle_name', 120)->nullable();
            $table->string('last_name', 120);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['m', 'v', 'x'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'partner'])->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('current_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'on_leave', 'suspended', 'exited'])->default('active');
            $table->date('joined_at')->nullable();
            $table->date('exited_at')->nullable();
            $table->text('exit_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('last_name');
            $table->index('status');
            $table->index('current_position_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
