<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Temporary URLs table for processing large datasets
     */
    public function up(): void
    {
        Schema::create('temp_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->text('url');
            $table->date('lastmod')->nullable();
            $table->string('changefreq', 20)->nullable();
            $table->decimal('priority', 2, 1)->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();

            $table->index('project_id');
            $table->index(['project_id', 'processed']);
            $table->index(['project_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_urls');
    }
};
