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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('crawl_status')->default('idle')->after('status'); // idle, crawling, paused, stopped, completed
            $table->integer('crawl_urls_found')->default(0)->after('crawl_status');
            $table->integer('crawl_queue_size')->default(0)->after('crawl_urls_found');
            $table->timestamp('crawl_started_at')->nullable()->after('crawl_queue_size');
            $table->timestamp('crawl_stopped_at')->nullable()->after('crawl_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['crawl_status', 'crawl_urls_found', 'crawl_queue_size', 'crawl_started_at', 'crawl_stopped_at']);
        });
    }
};
