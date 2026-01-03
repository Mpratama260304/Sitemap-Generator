<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sitemap Generator Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum URLs per sitemap file (Google standard is 50,000)
    'max_urls_per_file' => env('SITEMAP_MAX_URLS', 50000),

    // Maximum total URLs supported
    'max_total_urls' => env('SITEMAP_MAX_TOTAL', 1000000),

    // Chunk size for processing (memory optimization)
    'chunk_size' => env('SITEMAP_CHUNK_SIZE', 1000),

    // Default sitemap settings
    'defaults' => [
        'changefreq' => env('SITEMAP_DEFAULT_CHANGEFREQ', 'weekly'),
        'priority' => env('SITEMAP_DEFAULT_PRIORITY', '0.5'),
    ],

    // Crawler settings
    'crawler' => [
        'enabled' => env('CRAWLER_ENABLED', false),
        'max_depth' => env('CRAWLER_MAX_DEPTH', 3),
        'max_pages' => env('CRAWLER_MAX_PAGES', 50000),
        'timeout' => env('CRAWLER_TIMEOUT', 30),
        'delay' => env('CRAWLER_DELAY', 100), // milliseconds between requests
        'user_agent' => 'SitemapGenerator/1.0 (+https://github.com/sitemap-generator)',
    ],

    // Output directory (relative to public/)
    'output_directory' => 'sitemaps',

    // Allowed changefreq values
    'changefreq_options' => [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ],

    // CSV settings
    'csv' => [
        'max_file_size' => env('CSV_MAX_FILE_SIZE', 52428800), // 50MB
        'allowed_extensions' => ['csv', 'txt'],
    ],

];
