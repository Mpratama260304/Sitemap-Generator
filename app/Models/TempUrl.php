<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'url',
        'lastmod',
        'changefreq',
        'priority',
        'processed',
    ];

    protected $casts = [
        'lastmod' => 'date',
        'priority' => 'float',
        'processed' => 'boolean',
    ];

    /**
     * Get the project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
