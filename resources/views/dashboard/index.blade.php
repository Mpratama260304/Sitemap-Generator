@extends('layouts.app')

@section('title', 'Dashboard - Sitemap Generator')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Project</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_projects']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Sitemap Selesai</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed_sitemaps']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total URL</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_urls']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Project Sitemap</h1>
            <p class="text-gray-500 mt-1">Kelola semua project sitemap Anda</p>
        </div>
        <a href="{{ route('projects.create') }}" class="mt-4 sm:mt-0 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Project Baru
        </a>
    </div>

    <!-- Projects Grid -->
    @if($projects->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($projects as $project)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 card-hover overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $project->name }}</h3>
                                <p class="text-sm text-gray-500 truncate">{{ $project->base_url }}</p>
                            </div>
                            <span class="ml-2 px-2.5 py-1 rounded-full text-xs font-medium
                                @if($project->status === 'completed') bg-green-100 text-green-800
                                @elseif($project->status === 'processing') bg-yellow-100 text-yellow-800
                                @elseif($project->status === 'failed') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($project->status) }}
                            </span>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-600 text-xs font-medium mr-2">
                                {{ strtoupper($project->mode) }}
                            </span>
                            <span>{{ $project->created_at->diffForHumans() }}</span>
                        </div>
                        
                        @if($project->latestSitemap)
                            <div class="bg-gray-50 rounded-lg p-3 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">URL</span>
                                    <span class="font-medium text-gray-900">{{ number_format($project->latestSitemap->total_urls) }}</span>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <span class="text-gray-500">Files</span>
                                    <span class="font-medium text-gray-900">{{ $project->latestSitemap->total_files }}</span>
                                </div>
                            </div>
                        @endif
                        
                        <a href="{{ route('projects.show', $project->slug) }}" 
                           class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium transition">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="mt-8">
            {{ $projects->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Project</h3>
            <p class="text-gray-500 mb-6">Mulai dengan membuat project sitemap pertama Anda</p>
            <a href="{{ route('projects.create') }}" class="inline-flex items-center bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Buat Project Pertama
            </a>
        </div>
    @endif
</div>
@endsection
