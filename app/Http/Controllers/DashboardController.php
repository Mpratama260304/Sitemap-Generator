<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sitemap;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        $projects = Project::with('latestSitemap')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $stats = [
            'total_projects' => Project::count(),
            'completed_sitemaps' => Sitemap::count(),
            'total_urls' => Sitemap::sum('total_urls'),
        ];

        return view('dashboard.index', compact('projects', 'stats'));
    }
}
