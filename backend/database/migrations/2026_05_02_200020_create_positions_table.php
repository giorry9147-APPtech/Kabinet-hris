<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_unit_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('vacancies_count')->default(1);
            $table->enum('status', ['vacant', 'occupied', 'frozen', 'abolished'])->default('vacant');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['org_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
