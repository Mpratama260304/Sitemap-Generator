<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\GenerationProgress;
use App\Models\TempUrl;
use App\Services\SitemapGeneratorService;
use App\Services\CrawlerService;
use Illuminate\Http\Request;

class GenerateController extends Controller
{
    protected SitemapGeneratorService $generatorService;
    protected CrawlerService $crawlerService;

    public function __construct(
        SitemapGeneratorService $generatorService,
        CrawlerService $crawlerService
    ) {
        $this->generatorService = $generatorService;
        $this->crawlerService = $crawlerService;
    }

    /**
     * Start or continue sitemap generation
     * Called via AJAX for progress updates
     */
    public function generate(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        // Check if there are URLs to process
        $urlCount = TempUrl::where('project_id', $project->id)->count();

        if ($urlCount === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Tidak ada URL untuk diproses. Silakan upload CSV atau import dari database terlebih dahulu.',
            ]);
        }

        // Batch size for shared hosting (lower = safer)
        $batchSize = (int) $request->input('batch_size', 5000);

        try {
            $result = $this->generatorService->generate($project, $batchSize);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current generation progress
     */
    public function progress(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        $progress = $project->progress;

        if (!$progress) {
            $urlCount = TempUrl::where('project_id', $project->id)->count();

            return response()->json([
                'status' => 'pending',
                'total_urls' => $urlCount,
                'processed_urls' => 0,
                'percentage' => 0,
                'current_file' => 0,
                'estimated_files' => ceil($urlCount / config('sitemap.max_urls_per_file', 50000)),
            ]);
        }

        return response()->json([
            'status' => $progress->status,
            'total_urls' => $progress->total_urls,
            'processed_urls' => $progress->processed_urls,
            'percentage' => $progress->percentage,
            'current_file' => $progress->current_file,
            'estimated_files' => $progress->estimated_files,
            'last_error' => $progress->last_error,
        ]);
    }

    /**
     * Reset generation for re-processing
     */
    public function reset(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        $this->generatorService->resetProject($project);

        return response()->json([
            'success' => true,
            'message' => 'Progress berhasil di-reset. Anda dapat memulai generate ulang.',
        ]);
    }

    /**
     * Reset project completely including crawl data
     */
    public function resetFull(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        $this->generatorService->resetProjectFull($project);

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil di-reset. Anda dapat memulai crawl ulang.',
        ]);
    }

    /**
     * Start crawling (for crawl mode)
     */
    public function startCrawl(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if ($project->mode !== 'crawl') {
            return response()->json([
                'success' => false,
                'error' => 'Project ini bukan mode crawl.',
            ]);
        }

        try {
            $result = $this->crawlerService->startCrawl($project);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Continue crawling (for crawl mode) - called repeatedly
     */
    public function crawl(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if ($project->mode !== 'crawl') {
            return response()->json([
                'success' => false,
                'error' => 'Project ini bukan mode crawl.',
            ]);
        }

        try {
            $result = $this->crawlerService->crawl($project);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Stop crawling
     */
    public function stopCrawl(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        try {
            $result = $this->crawlerService->stopCrawl($project);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get crawl status
     */
    public function crawlStatus(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        $status = $this->crawlerService->getCrawlStatus($project);
        return response()->json($status);
    }

    /**
     * Pause/resume generation (for long running tasks)
     */
    public function pause(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        $progress = $project->progress;

        if ($progress && $progress->status === 'processing') {
            $progress->update(['status' => 'paused']);
            $project->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Generation di-pause. Anda dapat melanjutkan kapan saja.',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Tidak ada proses yang sedang berjalan.',
        ]);
    }

    /**
     * Get sitemap result after generation
     */
    public function result(string $slug)
    {
        $project = Project::where('slug', $slug)
            ->with('latestSitemap.files')
            ->firstOrFail();

        $sitemap = $project->latestSitemap;

        if (!$sitemap) {
            return response()->json([
                'success' => false,
                'error' => 'Belum ada sitemap yang di-generate.',
            ]);
        }

        return response()->json([
            'success' => true,
            'index_url' => $sitemap->index_url,
            'total_urls' => $sitemap->total_urls,
            'total_files' => $sitemap->total_files,
            'generation_time' => $sitemap->formatted_generation_time,
            'total_size' => $sitemap->formatted_file_size,
            'generated_at' => $sitemap->generated_at->format('d M Y H:i'),
            'files' => $sitemap->files->map(function ($file) {
                return [
                    'name' => $file->file_name,
                    'url' => $file->file_url,
                    'url_count' => number_format($file->url_count),
                    'size' => $file->formatted_file_size,
                ];
            }),
        ]);
    }
}
