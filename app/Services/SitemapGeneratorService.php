<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Sitemap;
use App\Models\SitemapFile;
use App\Models\GenerationProgress;
use App\Models\TempUrl;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SitemapGeneratorService
{
    protected int $maxUrlsPerFile;
    protected int $chunkSize;
    protected $currentFileHandle = null;
    protected int $currentUrlCount = 0;
    protected int $currentFileNumber = 0;
    protected string $outputDirectory;
    protected Project $project;
    protected array $sitemapFiles = [];
    protected float $startTime;

    public function __construct()
    {
        $this->maxUrlsPerFile = config('sitemap.max_urls_per_file', 50000);
        $this->chunkSize = config('sitemap.chunk_size', 1000);
    }

    /**
     * Generate sitemap from temporary URLs table
     * This method supports chunked processing for shared hosting
     */
    public function generate(Project $project, int $batchSize = 5000): array
    {
        $this->project = $project;
        $this->startTime = microtime(true);
        $this->outputDirectory = $project->sitemap_directory;
        $this->sitemapFiles = [];

        // Ensure output directory exists
        $this->ensureDirectoryExists();

        // Get or create progress tracker
        $progress = $this->getOrCreateProgress($project);

        // If starting fresh, clean old files
        if ($progress->processed_urls === 0) {
            $this->cleanOldSitemaps();
            $this->currentFileNumber = 0;
            $this->currentUrlCount = 0;
        } else {
            $this->currentFileNumber = $progress->current_file - 1;
            $this->currentUrlCount = $progress->processed_urls % $this->maxUrlsPerFile;
        }

        // Update status
        $progress->update(['status' => 'processing']);
        $project->update(['status' => 'processing']);

        try {
            // Process URLs in chunks
            $processed = $this->processUrlsInChunks($project, $progress, $batchSize);

            // Check if we're done
            if ($progress->refresh()->processed_urls >= $progress->total_urls) {
                // Close any open file
                $this->closeCurrentFile();

                // Generate sitemap index
                $indexPath = $this->generateSitemapIndex();

                // Calculate generation time
                $generationTime = microtime(true) - $this->startTime;

                // Create sitemap record
                $sitemap = $this->createSitemapRecord($project, $indexPath, $generationTime);

                // Update progress and project status
                $progress->update(['status' => 'completed']);
                $project->update(['status' => 'completed']);

                // Clean up temp URLs
                TempUrl::where('project_id', $project->id)->delete();

                return [
                    'success' => true,
                    'completed' => true,
                    'sitemap_id' => $sitemap->id,
                    'index_url' => $sitemap->index_url,
                    'total_urls' => $sitemap->total_urls,
                    'total_files' => $sitemap->total_files,
                    'generation_time' => $sitemap->formatted_generation_time,
                ];
            }

            // Return progress for next batch
            return [
                'success' => true,
                'completed' => false,
                'processed' => $progress->processed_urls,
                'total' => $progress->total_urls,
                'percentage' => $progress->percentage,
                'current_file' => $progress->current_file,
            ];

        } catch (\Exception $e) {
            Log::error('Sitemap generation error: ' . $e->getMessage());

            $progress->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            $project->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process URLs in chunks
     */
    protected function processUrlsInChunks(Project $project, GenerationProgress $progress, int $batchSize): int
    {
        $processed = 0;
        $settings = $project->merged_settings;

        TempUrl::where('project_id', $project->id)
            ->where('id', '>', $progress->last_processed_id)
            ->orderBy('id')
            ->chunk($this->chunkSize, function ($urls) use ($project, $progress, &$processed, $batchSize, $settings) {
                foreach ($urls as $urlRecord) {
                    // Check if we've reached the batch limit
                    if ($processed >= $batchSize) {
                        return false; // Stop chunking
                    }

                    // Normalize URL if enabled
                    $url = $settings['normalize_urls'] ? $this->normalizeUrl($urlRecord->url) : $urlRecord->url;

                    // Check exclude patterns
                    if ($this->shouldExclude($url, $settings['exclude_patterns'] ?? [])) {
                        $progress->increment('processed_urls');
                        $progress->update(['last_processed_id' => $urlRecord->id]);
                        continue;
                    }

                    // Write URL to sitemap file
                    $this->writeUrl(
                        $url,
                        $urlRecord->lastmod,
                        $urlRecord->changefreq ?? $settings['changefreq'],
                        $urlRecord->priority ?? $settings['priority']
                    );

                    $processed++;
                    $progress->increment('processed_urls');
                    $progress->update(['last_processed_id' => $urlRecord->id]);
                }
            });

        return $processed;
    }

    /**
     * Write a URL to the current sitemap file
     */
    protected function writeUrl(string $url, $lastmod = null, ?string $changefreq = null, $priority = null): void
    {
        // Check if we need to start a new file
        if ($this->currentFileHandle === null || $this->currentUrlCount >= $this->maxUrlsPerFile) {
            $this->startNewFile();
        }

        // Build URL entry
        $entry = "  <url>\n";
        $entry .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";

        if ($lastmod) {
            $dateStr = $lastmod instanceof \DateTime || $lastmod instanceof \Carbon\Carbon
                ? $lastmod->format('Y-m-d')
                : $lastmod;
            $entry .= "    <lastmod>{$dateStr}</lastmod>\n";
        }

        if ($changefreq) {
            $entry .= "    <changefreq>{$changefreq}</changefreq>\n";
        }

        if ($priority !== null) {
            $entry .= "    <priority>" . number_format((float)$priority, 1) . "</priority>\n";
        }

        $entry .= "  </url>\n";

        fwrite($this->currentFileHandle, $entry);
        $this->currentUrlCount++;
    }

    /**
     * Start a new sitemap file
     */
    protected function startNewFile(): void
    {
        // Close current file if open
        $this->closeCurrentFile();

        $this->currentFileNumber++;
        $this->currentUrlCount = 0;

        $fileName = "sitemap-{$this->currentFileNumber}.xml";
        $filePath = $this->outputDirectory . '/' . $fileName;

        $this->currentFileHandle = fopen($filePath, 'w');

        if ($this->currentFileHandle === false) {
            throw new \Exception("Cannot create sitemap file: {$filePath}");
        }

        // Write XML header and opening tag
        fwrite($this->currentFileHandle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($this->currentFileHandle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");

        // Track this file
        $this->sitemapFiles[] = [
            'path' => "sitemaps/{$this->project->slug}/{$fileName}",
            'number' => $this->currentFileNumber,
            'url_count' => 0,
        ];

        // Update progress
        if ($this->project->progress) {
            $this->project->progress->update(['current_file' => $this->currentFileNumber]);
        }
    }

    /**
     * Close the current sitemap file
     */
    protected function closeCurrentFile(): void
    {
        if ($this->currentFileHandle !== null) {
            fwrite($this->currentFileHandle, "</urlset>\n");
            fclose($this->currentFileHandle);

            // Update file info
            if (!empty($this->sitemapFiles)) {
                $lastIndex = count($this->sitemapFiles) - 1;
                $this->sitemapFiles[$lastIndex]['url_count'] = $this->currentUrlCount;
            }

            $this->currentFileHandle = null;
        }
    }

    /**
     * Generate the sitemap index file
     */
    protected function generateSitemapIndex(): string
    {
        $indexPath = "sitemaps/{$this->project->slug}/sitemap-index.xml";
        $fullPath = public_path($indexPath);

        $handle = fopen($fullPath, 'w');

        if ($handle === false) {
            throw new \Exception("Cannot create sitemap index: {$fullPath}");
        }

        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");

        $lastmod = date('Y-m-d');

        for ($i = 1; $i <= $this->currentFileNumber; $i++) {
            $sitemapUrl = url("sitemaps/{$this->project->slug}/sitemap-{$i}.xml");
            fwrite($handle, "  <sitemap>\n");
            fwrite($handle, "    <loc>" . htmlspecialchars($sitemapUrl, ENT_XML1, 'UTF-8') . "</loc>\n");
            fwrite($handle, "    <lastmod>{$lastmod}</lastmod>\n");
            fwrite($handle, "  </sitemap>\n");
        }

        fwrite($handle, "</sitemapindex>\n");
        fclose($handle);

        return $indexPath;
    }

    /**
     * Create sitemap database record
     */
    protected function createSitemapRecord(Project $project, string $indexPath, float $generationTime): Sitemap
    {
        $totalUrls = 0;

        // Create sitemap record
        $sitemap = Sitemap::create([
            'project_id' => $project->id,
            'index_path' => $indexPath,
            'total_files' => $this->currentFileNumber,
            'generation_time' => $generationTime,
            'generated_at' => now(),
        ]);

        // Create sitemap file records
        foreach ($this->sitemapFiles as $fileInfo) {
            $fullPath = public_path($fileInfo['path']);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            SitemapFile::create([
                'sitemap_id' => $sitemap->id,
                'file_path' => $fileInfo['path'],
                'file_number' => $fileInfo['number'],
                'url_count' => $fileInfo['url_count'],
                'file_size' => $fileSize,
            ]);

            $totalUrls += $fileInfo['url_count'];
        }

        // Update total URLs
        $sitemap->update(['total_urls' => $totalUrls]);

        return $sitemap;
    }

    /**
     * Get or create progress tracker
     */
    protected function getOrCreateProgress(Project $project): GenerationProgress
    {
        $progress = $project->progress;
        $totalUrls = TempUrl::where('project_id', $project->id)->count();

        if (!$progress) {
            $progress = GenerationProgress::create([
                'project_id' => $project->id,
                'total_urls' => $totalUrls,
                'processed_urls' => 0,
                'current_file' => 1,
                'status' => 'pending',
                'last_processed_id' => 0,
            ]);
        } elseif ($progress->status === 'completed' || $progress->status === 'failed') {
            // Reset progress if previously completed or failed
            $progress->update([
                'total_urls' => $totalUrls,
                'processed_urls' => 0,
                'current_file' => 1,
                'status' => 'pending',
                'last_processed_id' => 0,
                'last_error' => null,
            ]);
        } elseif ($progress->total_urls !== $totalUrls) {
            // Update total URL count if changed (e.g., new URLs added from crawl)
            $progress->update(['total_urls' => $totalUrls]);
        }

        return $progress;
    }

    /**
     * Ensure output directory exists
     */
    protected function ensureDirectoryExists(): void
    {
        if (!File::isDirectory($this->outputDirectory)) {
            File::makeDirectory($this->outputDirectory, 0755, true);
        }
    }

    /**
     * Clean old sitemap files
     */
    protected function cleanOldSitemaps(): void
    {
        if (File::isDirectory($this->outputDirectory)) {
            $files = File::glob($this->outputDirectory . '/*.xml');
            foreach ($files as $file) {
                File::delete($file);
            }
        }
    }

    /**
     * Normalize URL
     */
    protected function normalizeUrl(string $url): string
    {
        // Remove double slashes (except after protocol)
        $url = preg_replace('#(?<!:)//+#', '/', $url);

        // Ensure URL has protocol
        if (!preg_match('#^https?://#', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        // Remove trailing slash from path (optional - configurable)
        // $url = rtrim($url, '/');

        return $url;
    }

    /**
     * Check if URL should be excluded
     */
    protected function shouldExclude(string $url, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (empty($pattern)) continue;

            // Support simple wildcard patterns
            $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            if (preg_match('/' . $regex . '/i', $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset project for re-generation
     */
    public function resetProject(Project $project): void
    {
        // Delete old progress
        GenerationProgress::where('project_id', $project->id)->delete();

        // Delete old sitemaps
        $sitemaps = Sitemap::where('project_id', $project->id)->get();
        foreach ($sitemaps as $sitemap) {
            SitemapFile::where('sitemap_id', $sitemap->id)->delete();
            $sitemap->delete();
        }

        // Clean old files
        $this->outputDirectory = $project->sitemap_directory;
        $this->cleanOldSitemaps();

        // Reset project status
        $project->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    /**
     * Reset project completely including crawl data
     */
    public function resetProjectFull(Project $project): void
    {
        // Delete temp URLs
        TempUrl::where('project_id', $project->id)->delete();

        // Clear crawl queue cache
        cache()->forget("crawler_queue_{$project->id}");

        // Delete old progress
        GenerationProgress::where('project_id', $project->id)->delete();

        // Delete old sitemaps
        $sitemaps = Sitemap::where('project_id', $project->id)->get();
        foreach ($sitemaps as $sitemap) {
            SitemapFile::where('sitemap_id', $sitemap->id)->delete();
            $sitemap->delete();
        }

        // Clean old files
        $this->outputDirectory = $project->sitemap_directory;
        $this->cleanOldSitemaps();

        // Reset project status including crawl status
        $project->update([
            'status' => 'pending',
            'error_message' => null,
            'crawl_status' => 'idle',
            'crawl_urls_found' => 0,
            'crawl_queue_size' => 0,
            'crawl_started_at' => null,
            'crawl_stopped_at' => null,
        ]);
    }
}
