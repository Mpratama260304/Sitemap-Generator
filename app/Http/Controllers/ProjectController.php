<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TempUrl;
use App\Services\CsvImportService;
use App\Services\DatabaseImportService;
use App\Services\CrawlerService;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    protected CsvImportService $csvService;
    protected DatabaseImportService $dbService;
    protected SitemapGeneratorService $generatorService;

    public function __construct(
        CsvImportService $csvService,
        DatabaseImportService $dbService,
        SitemapGeneratorService $generatorService
    ) {
        $this->csvService = $csvService;
        $this->dbService = $dbService;
        $this->generatorService = $generatorService;
    }

    /**
     * Show the create project form
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Store a new project
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:500',
            'mode' => 'required|in:csv,database,crawl',
            'csv_file' => 'required_if:mode,csv|file|max:51200',
            'changefreq' => 'nullable|in:always,hourly,daily,weekly,monthly,yearly,never',
            'priority' => 'nullable|numeric|min:0|max:1',
            'exclude_patterns' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Prepare settings
        $settings = Project::getDefaultSettings();
        $settings['changefreq'] = $request->input('changefreq', 'weekly');
        $settings['priority'] = $request->input('priority', '0.5');

        // Parse exclude patterns
        if ($request->filled('exclude_patterns')) {
            $patterns = array_filter(array_map('trim', explode("\n", $request->input('exclude_patterns'))));
            $settings['exclude_patterns'] = $patterns;
        }

        // Mode-specific settings
        if ($request->mode === 'csv') {
            $settings['csv_has_header'] = $request->boolean('csv_has_header', true);
            $settings['csv_url_column'] = (int) $request->input('csv_url_column', 0);
            $settings['csv_lastmod_column'] = $request->filled('csv_lastmod_column') ? (int) $request->input('csv_lastmod_column') : null;
        } elseif ($request->mode === 'database') {
            $settings['db_host'] = $request->input('db_host');
            $settings['db_port'] = $request->input('db_port', 3306);
            $settings['db_name'] = $request->input('db_name');
            $settings['db_username'] = $request->input('db_username');
            $settings['db_password'] = $request->input('db_password');
            $settings['db_table'] = $request->input('db_table');
            $settings['db_slug_column'] = $request->input('db_slug_column');
            $settings['db_lastmod_column'] = $request->input('db_lastmod_column');
            $settings['db_url_prefix'] = $request->input('db_url_prefix', $request->input('base_url'));
        } elseif ($request->mode === 'crawl') {
            $settings['crawler_max_depth'] = min((int) $request->input('crawler_max_depth', 3), 5);
            $settings['crawler_max_pages'] = min((int) $request->input('crawler_max_pages', 1000), 50000);
        }

        // Create project
        $project = Project::create([
            'name' => $request->input('name'),
            'base_url' => rtrim($request->input('base_url'), '/'),
            'mode' => $request->input('mode'),
            'settings' => $settings,
            'status' => 'pending',
        ]);

        // Handle CSV upload immediately
        if ($request->mode === 'csv' && $request->hasFile('csv_file')) {
            try {
                $result = $this->csvService->importFromUpload($project, $request->file('csv_file'));

                if (!$result['success']) {
                    $project->delete();
                    return back()->with('error', 'Gagal mengimpor CSV: ' . ($result['error'] ?? 'Unknown error'))->withInput();
                }

                session()->flash('import_result', $result);

            } catch (\Exception $e) {
                $project->delete();
                return back()->with('error', 'Gagal mengimpor CSV: ' . $e->getMessage())->withInput();
            }
        }

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Project berhasil dibuat!');
    }

    /**
     * Show project details
     */
    public function show(string $slug)
    {
        $project = Project::where('slug', $slug)
            ->with(['latestSitemap.files', 'progress'])
            ->firstOrFail();

        $urlCount = TempUrl::where('project_id', $project->id)->count();
        $estimatedFiles = ceil($urlCount / config('sitemap.max_urls_per_file', 50000));
        
        // Get crawl queue size from cache
        $crawlQueueSize = count(cache()->get("crawler_queue_{$project->id}", []));

        return view('projects.show', compact('project', 'urlCount', 'estimatedFiles', 'crawlQueueSize'));
    }

    /**
     * Delete project
     */
    public function destroy(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        // Clean up sitemap files
        $this->generatorService->resetProject($project);

        // Delete temp URLs
        TempUrl::where('project_id', $project->id)->delete();

        // Delete project
        $project->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Project berhasil dihapus!');
    }

    /**
     * Import URLs for database mode
     */
    public function importDatabase(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if ($project->mode !== 'database') {
            return back()->with('error', 'Project ini bukan mode database');
        }

        try {
            $result = $this->dbService->import($project);

            return back()->with('success', "Berhasil mengimpor {$result['imported']} URL dari database!");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor dari database: ' . $e->getMessage());
        }
    }

    /**
     * Re-upload CSV file
     */
    public function uploadCsv(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|max:51200',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $result = $this->csvService->importFromUpload($project, $request->file('csv_file'));

            return back()->with('success', "Berhasil mengimpor {$result['imported']} URL dari CSV!");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor CSV: ' . $e->getMessage());
        }
    }

    /**
     * Test database connection (AJAX)
     */
    public function testDbConnection(Request $request)
    {
        $result = $this->dbService->testConnection($request->all());

        return response()->json($result);
    }

    /**
     * Get table columns (AJAX)
     */
    public function getTableColumns(Request $request)
    {
        $result = $this->dbService->getTableColumns(
            $request->all(),
            $request->input('table')
        );

        return response()->json($result);
    }
}
