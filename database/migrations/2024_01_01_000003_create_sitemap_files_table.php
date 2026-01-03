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
        Schema::create('sitemap_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sitemap_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->unsignedInteger('file_number');
            $table->unsignedBigInteger('url_count')->default(0);
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->timestamps();

            $table->index('sitemap_id');
            $table->index(['sitemap_id', 'file_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitemap_files');
    }
};
