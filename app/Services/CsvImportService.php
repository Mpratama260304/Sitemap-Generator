<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TempUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    protected int $chunkSize;
    protected int $maxFileSize;

    public function __construct()
    {
        $this->chunkSize = config('sitemap.chunk_size', 1000);
        $this->maxFileSize = config('sitemap.csv.max_file_size', 52428800);
    }

    /**
     * Import URLs from CSV file
     * Uses streaming to handle large files
     */
    public function import(Project $project, string $filePath): array
    {
        $settings = $project->merged_settings;
        $hasHeader = $settings['csv_has_header'] ?? true;
        $urlColumn = $settings['csv_url_column'] ?? 0;
        $lastmodColumn = $settings['csv_lastmod_column'] ?? null;
        $changefreqColumn = $settings['csv_changefreq_column'] ?? null;
        $priorityColumn = $settings['csv_priority_column'] ?? null;

        // Clear existing temp URLs for this project
        TempUrl::where('project_id', $project->id)->delete();

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Cannot open CSV file: {$filePath}");
        }

        $rowCount = 0;
        $importedCount = 0;
        $errorCount = 0;
        $batch = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                // Skip header row
                if ($rowCount === 1 && $hasHeader) {
                    continue;
                }

                // Get URL from specified column
                $url = trim($row[$urlColumn] ?? '');

                if (empty($url)) {
                    $errorCount++;
                    continue;
                }

                // Validate URL
                if (!$this->isValidUrl($url, $project->base_url)) {
                    $errorCount++;
                    continue;
                }

                // Build full URL if relative
                $url = $this->buildFullUrl($url, $project->base_url);

                // Prepare data
                $data = [
                    'project_id' => $project->id,
                    'url' => $url,
                    'lastmod' => $this->parseDate($row[$lastmodColumn] ?? null),
                    'changefreq' => $this->validateChangefreq($row[$changefreqColumn] ?? null),
                    'priority' => $this->validatePriority($row[$priorityColumn] ?? null),
                    'processed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $batch[] = $data;
                $importedCount++;

                // Insert in batches for memory efficiency
                if (count($batch) >= $this->chunkSize) {
                    $this->insertBatch($batch);
                    $batch = [];
                }
            }

            // Insert remaining batch
            if (!empty($batch)) {
                $this->insertBatch($batch);
            }

            fclose($handle);

            return [
                'success' => true,
                'total_rows' => $rowCount - ($hasHeader ? 1 : 0),
                'imported' => $importedCount,
                'errors' => $errorCount,
            ];

        } catch (\Exception $e) {
            fclose($handle);
            Log::error('CSV import error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import URLs from uploaded file (handles temp file)
     */
    public function importFromUpload(Project $project, $uploadedFile): array
    {
        // Validate file size
        if ($uploadedFile->getSize() > $this->maxFileSize) {
            throw new \Exception('File size exceeds maximum allowed: ' . $this->formatBytes($this->maxFileSize));
        }

        // Validate extension
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $allowedExtensions = config('sitemap.csv.allowed_extensions', ['csv', 'txt']);

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Invalid file type. Allowed: ' . implode(', ', $allowedExtensions));
        }

        // Store temporarily
        $tempPath = $uploadedFile->store('temp', 'local');
        $fullPath = storage_path('app/' . $tempPath);

        try {
            $result = $this->import($project, $fullPath);

            // Clean up temp file
            unlink($fullPath);

            return $result;

        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            throw $e;
        }
    }

    /**
     * Insert batch of URLs
     */
    protected function insertBatch(array $batch): void
    {
        // Use chunked insert to avoid query size limits
        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('temp_urls')->insert($chunk);
        }
    }

    /**
     * Validate URL format
     */
    protected function isValidUrl(string $url, string $baseUrl): bool
    {
        // Allow relative URLs
        if (str_starts_with($url, '/')) {
            return true;
        }

        // Allow URLs with same domain
        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        // Has protocol - validate it's http/https
        if (isset($parsed['scheme']) && !in_array($parsed['scheme'], ['http', 'https'])) {
            return false;
        }

        return true;
    }

    /**
     * Build full URL from relative path
     */
    protected function buildFullUrl(string $url, string $baseUrl): string
    {
        // Already has protocol
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }

        // Relative URL
        $baseUrl = rtrim($baseUrl, '/');
        $url = '/' . ltrim($url, '/');

        return $baseUrl . $url;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Try common date formats
            $formats = [
                'Y-m-d',
                'Y-m-d H:i:s',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'Y/m/d',
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // Try strtotime as fallback
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }

        } catch (\Exception $e) {
            // Ignore parse errors
        }

        return null;
    }

    /**
     * Validate changefreq value
     */
    protected function validateChangefreq($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $allowed = config('sitemap.changefreq_options', [
            'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'
        ]);

        $value = strtolower(trim($value));

        return in_array($value, $allowed) ? $value : null;
    }

    /**
     * Validate priority value
     */
    protected function validatePriority($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $priority = (float) $value;

        if ($priority < 0 || $priority > 1) {
            return null;
        }

        return $priority;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return number_format($bytes / 1048576, 2) . ' MB';
    }

    /**
     * Preview CSV file (first N rows)
     */
    public function preview(string $filePath, int $rows = 5): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Cannot open CSV file");
        }

        $data = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false && $count < $rows) {
            $data[] = $row;
            $count++;
        }

        fclose($handle);

        return $data;
    }
}
