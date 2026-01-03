<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generation_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('total_urls')->default(0);
            $table->unsignedBigInteger('processed_urls')->default(0);
            $table->unsignedInteger('current_file')->default(1);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'paused'])->default('pending');
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('last_processed_id')->default(0);
            $table->timestamps();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_progress');
    }
};
