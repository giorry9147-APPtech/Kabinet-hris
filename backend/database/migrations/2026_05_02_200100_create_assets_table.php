<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique();
            $table->string('name');
            $table->string('category', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('purchased_at')->nullable();
            $table->decimal('purchase_value', 14, 2)->nullable();
            $table->enum('status', ['available', 'assigned', 'under_maintenance', 'retired', 'lost'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
