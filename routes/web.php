<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\GenerateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Projects
Route::prefix('projects')->name('projects.')->group(function () {
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');
    Route::get('/{slug}', [ProjectController::class, 'show'])->name('show');
    Route::delete('/{slug}', [ProjectController::class, 'destroy'])->name('destroy');
    
    // CSV Upload
    Route::post('/{slug}/upload-csv', [ProjectController::class, 'uploadCsv'])->name('upload-csv');
    
    // Database Import
    Route::post('/{slug}/import-database', [ProjectController::class, 'importDatabase'])->name('import-database');
});

// Generate Sitemap (AJAX endpoints)
Route::prefix('generate')->name('generate.')->group(function () {
    Route::post('/{slug}', [GenerateController::class, 'generate'])->name('process');
    Route::get('/{slug}/progress', [GenerateController::class, 'progress'])->name('progress');
    Route::post('/{slug}/reset', [GenerateController::class, 'reset'])->name('reset');
    Route::post('/{slug}/reset-full', [GenerateController::class, 'resetFull'])->name('reset.full');
    Route::post('/{slug}/pause', [GenerateController::class, 'pause'])->name('pause');
    Route::get('/{slug}/result', [GenerateController::class, 'result'])->name('result');
    
    // Crawl endpoints
    Route::post('/{slug}/crawl', [GenerateController::class, 'crawl'])->name('crawl');
    Route::post('/{slug}/crawl/start', [GenerateController::class, 'startCrawl'])->name('crawl.start');
    Route::post('/{slug}/crawl/stop', [GenerateController::class, 'stopCrawl'])->name('crawl.stop');
    Route::get('/{slug}/crawl/status', [GenerateController::class, 'crawlStatus'])->name('crawl.status');
});

// API for AJAX calls
Route::prefix('api')->group(function () {
    Route::post('/test-db-connection', [ProjectController::class, 'testDbConnection']);
    Route::post('/get-table-columns', [ProjectController::class, 'getTableColumns']);
});
