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
        Schema::create('sitemaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('index_path')->nullable();
            $table->unsignedBigInteger('total_urls')->default(0);
            $table->unsignedInteger('total_files')->default(0);
            $table->decimal('generation_time', 10, 2)->nullable(); // in seconds
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitemaps');
    }
};
