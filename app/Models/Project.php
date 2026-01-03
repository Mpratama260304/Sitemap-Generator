<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'mode',
        'settings',
        'status',
        'error_message',
        'crawl_status',
        'crawl_urls_found',
        'crawl_queue_size',
        'crawl_started_at',
        'crawl_stopped_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'crawl_started_at' => 'datetime',
        'crawl_stopped_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name) . '-' . Str::random(6);
            }
        });
    }

    /**
     * Get the sitemaps for this project
     */
    public function sitemaps(): HasMany
    {
        return $this->hasMany(Sitemap::class);
    }

    /**
     * Get the latest sitemap
     */
    public function latestSitemap(): HasOne
    {
        return $this->hasOne(Sitemap::class)->latestOfMany();
    }

    /**
     * Get the generation progress
     */
    public function progress(): HasOne
    {
        return $this->hasOne(GenerationProgress::class);
    }

    /**
     * Get temporary URLs for this project
     */
    public function tempUrls(): HasMany
    {
        return $this->hasMany(TempUrl::class);
    }

    /**
     * Get the sitemap directory path
     */
    public function getSitemapDirectoryAttribute(): string
    {
        return public_path('sitemaps/' . $this->slug);
    }

    /**
     * Get the sitemap URL base
     */
    public function getSitemapUrlAttribute(): string
    {
        return url('sitemaps/' . $this->slug);
    }

    /**
     * Get default settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            'changefreq' => config('sitemap.defaults.changefreq', 'weekly'),
            'priority' => config('sitemap.defaults.priority', '0.5'),
            'exclude_patterns' => [],
            'include_lastmod' => true,
            'normalize_urls' => true,
            // CSV mode settings
            'csv_has_header' => true,
            'csv_url_column' => 0,
            'csv_lastmod_column' => null,
            'csv_changefreq_column' => null,
            'csv_priority_column' => null,
            // Database mode settings
            'db_table' => '',
            'db_url_column' => '',
            'db_slug_column' => '',
            'db_lastmod_column' => '',
            'db_url_prefix' => '',
            // Crawler mode settings
            'crawler_max_depth' => 3,
            'crawler_max_pages' => 50000,
            'crawler_delay' => 100,
            'crawler_respect_robots' => true,
        ];
    }

    /**
     * Get merged settings with defaults
     */
    public function getMergedSettingsAttribute(): array
    {
        return array_merge(self::getDefaultSettings(), $this->settings ?? []);
    }

    /**
     * Check if project is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if project has completed sitemap
     */
    public function hasCompletedSitemap(): bool
    {
        return $this->status === 'completed' && $this->latestSitemap !== null;
    }
}
