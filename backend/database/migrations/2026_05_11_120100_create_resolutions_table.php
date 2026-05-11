<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('resolution_number', 100)->unique();
            $table->string('subject');
            $table->string('category', 60)->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->date('signed_at');
            $table->date('effective_from')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->string('signed_by')->nullable();
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'expires_at']);
            $table->index('expires_at');
            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
