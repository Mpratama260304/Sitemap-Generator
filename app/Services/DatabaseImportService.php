<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TempUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseImportService
{
    protected int $chunkSize;

    public function __construct()
    {
        $this->chunkSize = config('sitemap.chunk_size', 1000);
    }

    /**
     * Import URLs from external database
     */
    public function import(Project $project): array
    {
        $settings = $project->merged_settings;

        // Validate required settings
        $table = $settings['db_table'] ?? '';
        $slugColumn = $settings['db_slug_column'] ?? '';
        $urlPrefix = rtrim($settings['db_url_prefix'] ?? $project->base_url, '/');

        if (empty($table)) {
            throw new \Exception('Database table name is required');
        }

        // Clear existing temp URLs
        TempUrl::where('project_id', $project->id)->delete();

        // Build query
        $connection = $this->getConnection($settings);
        $query = DB::connection($connection)->table($table);

        // Select columns
        $selectColumns = [$slugColumn . ' as slug'];

        $lastmodColumn = $settings['db_lastmod_column'] ?? null;
        if ($lastmodColumn) {
            $selectColumns[] = $lastmodColumn . ' as lastmod';
        }

        $query->select($selectColumns);

        // Apply any filters if specified
        if (!empty($settings['db_where_column']) && !empty($settings['db_where_value'])) {
            $query->where($settings['db_where_column'], $settings['db_where_value']);
        }

        // Count total
        $totalCount = (clone $query)->count();
        $importedCount = 0;
        $batch = [];

        // Process in chunks using cursor for memory efficiency
        $query->orderBy('id')->chunk($this->chunkSize, function ($rows) use ($project, $urlPrefix, $lastmodColumn, &$batch, &$importedCount) {
            foreach ($rows as $row) {
                $slug = $row->slug ?? '';

                if (empty($slug)) {
                    continue;
                }

                // Build URL
                $url = $urlPrefix . '/' . ltrim($slug, '/');

                $data = [
                    'project_id' => $project->id,
                    'url' => $url,
                    'lastmod' => isset($row->lastmod) ? $this->formatDate($row->lastmod) : null,
                    'changefreq' => null,
                    'priority' => null,
                    'processed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $batch[] = $data;
                $importedCount++;

                // Insert in batches
                if (count($batch) >= 500) {
                    $this->insertBatch($batch);
                    $batch = [];
                }
            }
        });

        // Insert remaining
        if (!empty($batch)) {
            $this->insertBatch($batch);
        }

        return [
            'success' => true,
            'total' => $totalCount,
            'imported' => $importedCount,
        ];
    }

    /**
     * Test database connection
     */
    public function testConnection(array $settings): array
    {
        try {
            // Set connection config dynamically
            config([
                'database.connections.external.host' => $settings['db_host'] ?? '127.0.0.1',
                'database.connections.external.port' => $settings['db_port'] ?? 3306,
                'database.connections.external.database' => $settings['db_name'] ?? '',
                'database.connections.external.username' => $settings['db_username'] ?? '',
                'database.connections.external.password' => $settings['db_password'] ?? '',
            ]);

            // Test connection
            DB::connection('external')->getPdo();

            // Get tables list
            $tables = DB::connection('external')
                ->select('SHOW TABLES');

            $tableNames = array_map(function ($table) {
                return array_values((array) $table)[0];
            }, $tables);

            return [
                'success' => true,
                'tables' => $tableNames,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get table columns
     */
    public function getTableColumns(array $settings, string $table): array
    {
        try {
            $this->setConnectionConfig($settings);

            $columns = DB::connection('external')
                ->select("DESCRIBE {$table}");

            return [
                'success' => true,
                'columns' => array_map(fn($col) => $col->Field, $columns),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Preview data from table
     */
    public function previewTable(array $settings, string $table, int $limit = 5): array
    {
        try {
            $this->setConnectionConfig($settings);

            $rows = DB::connection('external')
                ->table($table)
                ->limit($limit)
                ->get();

            $count = DB::connection('external')
                ->table($table)
                ->count();

            return [
                'success' => true,
                'rows' => $rows->toArray(),
                'total_count' => $count,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get connection name based on settings
     */
    protected function getConnection(array $settings): string
    {
        // If using external database
        if (!empty($settings['db_host'])) {
            $this->setConnectionConfig($settings);
            return 'external';
        }

        // Use default connection
        return config('database.default');
    }

    /**
     * Set external connection config
     */
    protected function setConnectionConfig(array $settings): void
    {
        config([
            'database.connections.external.host' => $settings['db_host'] ?? '127.0.0.1',
            'database.connections.external.port' => $settings['db_port'] ?? 3306,
            'database.connections.external.database' => $settings['db_name'] ?? '',
            'database.connections.external.username' => $settings['db_username'] ?? '',
            'database.connections.external.password' => $settings['db_password'] ?? '',
        ]);
    }

    /**
     * Insert batch of URLs
     */
    protected function insertBatch(array $batch): void
    {
        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('temp_urls')->insert($chunk);
        }
    }

    /**
     * Format date
     */
    protected function formatDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }

            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }
}
