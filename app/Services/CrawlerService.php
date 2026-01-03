<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TempUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CrawlerService
{
    protected int $maxDepth;
    protected int $maxPages;
    protected int $delay;
    protected int $timeout;
    protected string $userAgent;
    protected array $visited = [];
    protected array $queue = [];
    protected string $baseHost;
    protected string $baseScheme;
    protected array $excludePatterns = [];

    public function __construct()
    {
        $this->maxDepth = config('sitemap.crawler.max_depth', 3);
        $this->maxPages = config('sitemap.crawler.max_pages', 50000);
        $this->timeout = config('sitemap.crawler.timeout', 10);
        $this->delay = config('sitemap.crawler.delay', 50);
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    }

    /**
     * Start crawling - sets status and begins
     */
    public function startCrawl(Project $project): array
    {
        // Check if already crawling
        if ($project->crawl_status === 'crawling') {
            return $this->crawl($project);
        }

        // Reset and start fresh
        TempUrl::where('project_id', $project->id)->delete();
        cache()->forget("crawler_queue_{$project->id}");

        $project->update([
            'crawl_status' => 'crawling',
            'crawl_urls_found' => 0,
            'crawl_queue_size' => 0,
            'crawl_started_at' => now(),
            'crawl_stopped_at' => null,
        ]);

        return $this->crawl($project);
    }

    /**
     * Stop crawling
     */
    public function stopCrawl(Project $project): array
    {
        // Get actual URL count from database
        $urlCount = TempUrl::where('project_id', $project->id)->count();
        
        $project->update([
            'crawl_status' => 'stopped',
            'crawl_stopped_at' => now(),
            'crawl_urls_found' => $urlCount,
        ]);

        return [
            'success' => true,
            'message' => 'Crawl stopped',
            'urls_found' => $urlCount,
        ];
    }

    /**
     * Get crawl status
     */
    public function getCrawlStatus(Project $project): array
    {
        $urlCount = TempUrl::where('project_id', $project->id)->count();
        $queueSize = count(cache()->get("crawler_queue_{$project->id}", []));

        return [
            'status' => $project->crawl_status,
            'urls_found' => $urlCount,
            'queue_size' => $queueSize,
            'started_at' => $project->crawl_started_at?->toIso8601String(),
            'stopped_at' => $project->crawl_stopped_at?->toIso8601String(),
        ];
    }

    /**
     * Crawl website - continues from where it left off
     */
    public function crawl(Project $project, int $batchLimit = 10): array
    {
        if (!config('sitemap.crawler.enabled', false)) {
            return [
                'success' => false,
                'error' => 'Crawler mode is disabled. Enable CRAWLER_ENABLED=true in .env file.',
            ];
        }

        // Check if stopped
        $project->refresh();
        if ($project->crawl_status === 'stopped') {
            return [
                'success' => true,
                'completed' => true,
                'stopped' => true,
                'urls_found' => TempUrl::where('project_id', $project->id)->count(),
                'queue_remaining' => 0,
            ];
        }

        $settings = $project->merged_settings;
        $this->maxDepth = $settings['crawler_max_depth'] ?? $this->maxDepth;
        $this->maxPages = min($settings['crawler_max_pages'] ?? $this->maxPages, 50000);
        $this->delay = $settings['crawler_delay'] ?? 50;
        $this->excludePatterns = $settings['exclude_patterns'] ?? [];

        // Parse base URL
        $parsedBase = parse_url($project->base_url);
        $this->baseHost = $parsedBase['host'] ?? '';
        $this->baseScheme = $parsedBase['scheme'] ?? 'https';

        // Get already visited URLs
        $this->visited = TempUrl::where('project_id', $project->id)
            ->pluck('url')
            ->flip()
            ->toArray();

        // Load pending queue from cache
        $cacheKey = "crawler_queue_{$project->id}";
        $cachedQueue = cache()->get($cacheKey, []);
        
        // Initialize queue with base URL if empty and no visited
        if (empty($this->visited) && empty($cachedQueue)) {
            $this->queue[] = ['url' => $project->base_url, 'depth' => 0];
        } else {
            $this->queue = $cachedQueue;
        }

        $newUrls = [];
        $processed = 0;

        while (!empty($this->queue) && $processed < $batchLimit && count($this->visited) < $this->maxPages) {
            $item = array_shift($this->queue);
            $url = $item['url'];
            $depth = $item['depth'];

            // Skip if already visited
            if (isset($this->visited[$url])) {
                continue;
            }

            // Skip if max depth exceeded
            if ($depth > $this->maxDepth) {
                continue;
            }

            try {
                // Fetch page with browser-like headers
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'User-Agent' => $this->userAgent,
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.5',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Connection' => 'keep-alive',
                        'Upgrade-Insecure-Requests' => '1',
                    ])
                    ->withOptions([
                        'verify' => false,
                        'allow_redirects' => ['max' => 3],
                    ])
                    ->get($url);

                if ($response->successful()) {
                    // Mark as visited
                    $this->visited[$url] = true;
                    $newUrls[] = [
                        'project_id' => $project->id,
                        'url' => $url,
                        'lastmod' => now()->format('Y-m-d'),
                        'changefreq' => null,
                        'priority' => null,
                        'processed' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Extract links if not at max depth
                    if ($depth < $this->maxDepth) {
                        $links = $this->extractLinks($response->body(), $url);
                        foreach ($links as $link) {
                            if (!isset($this->visited[$link]) && !$this->shouldExclude($link)) {
                                $this->queue[] = ['url' => $link, 'depth' => $depth + 1];
                            }
                        }
                    }
                }

            } catch (\Exception $e) {
                // Silently skip failed URLs
            }

            $processed++;

            // Minimal rate limiting
            if ($this->delay > 0) {
                usleep($this->delay * 1000);
            }
        }

        // Save new URLs to database
        if (!empty($newUrls)) {
            foreach (array_chunk($newUrls, 500) as $chunk) {
                DB::table('temp_urls')->insert($chunk);
            }
        }

        // Cache remaining queue
        cache()->put($cacheKey, $this->queue, 3600);

        $totalVisited = count($this->visited);
        $isComplete = empty($this->queue) || $totalVisited >= $this->maxPages;

        // Update project status
        $project->update([
            'crawl_urls_found' => $totalVisited,
            'crawl_queue_size' => count($this->queue),
            'crawl_status' => $isComplete ? 'completed' : 'crawling',
        ]);

        if ($isComplete) {
            cache()->forget($cacheKey);
            $project->update(['crawl_stopped_at' => now()]);
        }

        return [
            'success' => true,
            'completed' => $isComplete,
            'urls_found' => $totalVisited,
            'total_urls' => $totalVisited,
            'crawled' => $totalVisited,
            'queue_remaining' => count($this->queue),
            'new_urls' => count($newUrls),
            'max_pages' => $this->maxPages,
        ];
    }

    /**
     * Extract links from HTML
     */
    protected function extractLinks(string $html, string $pageUrl): array
    {
        $links = [];
        $dom = new \DOMDocument();

        // Suppress HTML parsing errors
        @$dom->loadHTML($html);

        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');

            if (empty($href)) {
                continue;
            }

            // Skip anchors, javascript, mailto
            if (str_starts_with($href, '#') ||
                str_starts_with($href, 'javascript:') ||
                str_starts_with($href, 'mailto:') ||
                str_starts_with($href, 'tel:')) {
                continue;
            }

            // Resolve relative URLs
            $absoluteUrl = $this->resolveUrl($href, $pageUrl);

            if ($absoluteUrl && $this->isSameDomain($absoluteUrl)) {
                // Normalize URL
                $absoluteUrl = $this->normalizeUrl($absoluteUrl);
                $links[] = $absoluteUrl;
            }
        }

        return array_unique($links);
    }

    /**
     * Resolve relative URL to absolute
     */
    protected function resolveUrl(string $href, string $pageUrl): ?string
    {
        // Already absolute
        if (preg_match('#^https?://#', $href)) {
            return $href;
        }

        $parsed = parse_url($pageUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '/';

        // Protocol-relative
        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }

        // Root-relative
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        // Relative to current path
        $basePath = dirname($path);
        return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . $href;
    }

    /**
     * Check if URL is on the same domain
     */
    protected function isSameDomain(string $url): bool
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        return $host === $this->baseHost || str_ends_with($host, '.' . $this->baseHost);
    }

    /**
     * Normalize URL
     */
    protected function normalizeUrl(string $url): string
    {
        // Remove fragment
        $url = preg_replace('/#.*$/', '', $url);

        // Remove common tracking parameters
        $url = preg_replace('/[?&](utm_[^&]*|ref=[^&]*|fbclid=[^&]*)/', '', $url);

        // Clean up multiple slashes
        $url = preg_replace('#(?<!:)//+#', '/', $url);

        // Remove trailing slash from path (optional)
        $parsed = parse_url($url);
        if (empty($parsed['query']) && !empty($parsed['path']) && $parsed['path'] !== '/') {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    /**
     * Check if URL should be excluded
     */
    protected function shouldExclude(string $url): bool
    {
        // Common excludes
        $defaultExcludes = [
            '/wp-admin/',
            '/wp-includes/',
            '/wp-json/',
            '/feed/',
            '/xmlrpc.php',
            '.pdf',
            '.jpg',
            '.png',
            '.gif',
            '.css',
            '.js',
        ];

        $allPatterns = array_merge($defaultExcludes, $this->excludePatterns);

        foreach ($allPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset crawler state for a project
     */
    public function reset(Project $project): void
    {
        $cacheKey = "crawler_queue_{$project->id}";
        cache()->forget($cacheKey);
        TempUrl::where('project_id', $project->id)->delete();
    }
}
