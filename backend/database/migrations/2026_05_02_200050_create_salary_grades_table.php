<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('schaal');
            $table->unsignedSmallInteger('trede');
            $table->string('code', 20)->unique();
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('SRD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['schaal', 'trede']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grades');
    }
};
