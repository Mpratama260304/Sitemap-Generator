@extends('layouts.app')

@section('title', $project->name . ' - Sitemap Generator')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="projectDetail()">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-primary-600 hover:text-primary-700 flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        @if($project->status === 'completed') bg-green-100 text-green-800
                        @elseif($project->status === 'processing') bg-yellow-100 text-yellow-800
                        @elseif($project->status === 'failed') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($project->status) }}
                    </span>
                </div>
                <p class="text-gray-500 mt-1">{{ $project->base_url }}</p>
                <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                    <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-600 text-xs font-medium">
                        Mode: {{ strtoupper($project->mode) }}
                    </span>
                    <span>Dibuat {{ $project->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <form action="{{ route('projects.destroy', $project->slug) }}" method="POST" 
                      onsubmit="return confirm('Yakin ingin menghapus project ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- URL Stats -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Statistik URL</h2>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($urlCount) }}</p>
                        <p class="text-sm text-gray-500">Total URL</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900">{{ $estimatedFiles }}</p>
                        <p class="text-sm text-gray-500">Estimasi File</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900">50K</p>
                        <p class="text-sm text-gray-500">URL/File</p>
                    </div>
                </div>

                @if($urlCount === 0)
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Belum ada URL.</strong> 
                            @if($project->mode === 'csv')
                                Silakan upload file CSV terlebih dahulu.
                            @elseif($project->mode === 'database')
                                Silakan import dari database terlebih dahulu.
                            @else
                                Silakan jalankan crawler terlebih dahulu.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Upload/Import Section -->
            @if($project->mode === 'csv')
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Upload CSV</h2>
                    <form action="{{ route('projects.upload-csv', $project->slug) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-center space-x-4">
                            <input type="file" name="csv_file" accept=".csv,.txt" required
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($project->mode === 'database')
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Import dari Database</h2>
                    <form action="{{ route('projects.import-database', $project->slug) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                            Import Sekarang
                        </button>
                    </form>
                </div>
            @elseif($project->mode === 'crawl')
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">🕷️ Crawl Website</h2>
                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                              :class="{
                                  'bg-gray-100 text-gray-600': crawlStatus === 'idle',
                                  'bg-blue-100 text-blue-600': crawlStatus === 'crawling',
                                  'bg-yellow-100 text-yellow-600': crawlStatus === 'stopped',
                                  'bg-green-100 text-green-600': crawlStatus === 'completed'
                              }"
                              x-text="crawlStatusText"></span>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            <strong>Info:</strong> Crawler akan mengunjungi <code class="bg-blue-100 px-1 rounded">{{ $project->base_url }}</code> 
                            dan mengumpulkan semua URL internal secara otomatis.
                        </p>
                        <ul class="text-sm text-blue-700 mt-2 space-y-1">
                            <li>• Max Depth: {{ $project->settings['crawler_max_depth'] ?? 3 }}</li>
                            <li>• Max Pages: {{ number_format($project->settings['crawler_max_pages'] ?? 1000) }}</li>
                            <li>• Progress otomatis tersimpan - bisa dilanjutkan kapan saja</li>
                        </ul>
                    </div>

                    <!-- Crawl Progress -->
                    <div x-show="crawlStatus === 'crawling' || crawlProgress.crawled > 0" class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>🕷️ <span x-text="crawlProgress.crawled.toLocaleString()"></span> URL ditemukan</span>
                            <span x-text="crawlProgress.status" class="font-medium"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full transition-all duration-300" 
                                 :class="crawlStatus === 'crawling' ? 'bg-blue-600 animate-pulse' : 'bg-green-500'"
                                 :style="'width: ' + Math.min((crawlProgress.crawled / {{ $project->settings['crawler_max_pages'] ?? 1000 }}) * 100, 100) + '%'"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <span x-show="crawlProgress.queue > 0" class="text-blue-600">
                                Queue: <span x-text="crawlProgress.queue.toLocaleString()"></span> URL pending
                            </span>
                        </p>
                    </div>

                    <!-- Crawl Result -->
                    <div x-show="crawlStatus === 'completed'" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">
                            <strong>✅ Crawl selesai!</strong> Ditemukan <span x-text="crawlProgress.crawled.toLocaleString()"></span> URL.
                            Silakan lanjut ke Generate Sitemap.
                        </p>
                    </div>

                    <!-- Stopped Notice -->
                    <div x-show="crawlStatus === 'stopped'" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>⏸️ Crawl dihentikan.</strong> <span x-text="crawlProgress.crawled.toLocaleString()"></span> URL tersimpan.
                            Klik "Lanjutkan Crawl" untuk melanjutkan.
                        </p>
                    </div>

                    <!-- Crawl Error -->
                    <div x-show="crawlError" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-800" x-text="crawlError"></p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <!-- Start/Resume Button -->
                        <button @click="startCrawl()" 
                                x-show="crawlStatus !== 'crawling'"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition flex items-center">
                            <span x-text="crawlStatus === 'stopped' || crawlProgress.crawled > 0 ? '▶️ Lanjutkan Crawl' : '🕷️ Mulai Crawl'"></span>
                        </button>

                        <!-- Crawling indicator -->
                        <button x-show="crawlStatus === 'crawling'" disabled
                                class="px-6 py-3 bg-gray-400 text-white rounded-lg font-medium flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Crawling...
                        </button>
                        
                        <!-- Stop Button -->
                        <button @click="stopCrawl()" 
                                x-show="crawlStatus === 'crawling'"
                                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                            ⏹️ Stop Crawl
                        </button>

                        <!-- Reset Button -->
                        <button @click="resetCrawl()" 
                                x-show="crawlStatus === 'stopped' || crawlStatus === 'completed'"
                                class="px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            🔄 Reset & Mulai Ulang
                        </button>
                    </div>
                </div>
            @endif

            <!-- Generate Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Generate Sitemap</h2>
                
                <!-- Progress Bar -->
                <div x-show="isGenerating || progress.percentage > 0" class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Progress: <span x-text="progress.processed_urls.toLocaleString()"></span> / <span x-text="progress.total_urls.toLocaleString()"></span> URL</span>
                        <span x-text="progress.percentage + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-primary-600 h-4 rounded-full transition-all duration-300" 
                             :style="'width: ' + progress.percentage + '%'"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        File: <span x-text="progress.current_file"></span> / <span x-text="progress.estimated_files"></span>
                    </p>
                </div>

                <!-- Error Message -->
                <div x-show="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-800" x-text="error"></p>
                </div>

                <!-- Generate Button -->
                <div class="flex flex-wrap gap-3">
                    <button @click="startGenerate()" 
                            :disabled="isGenerating || progress.total_urls === 0"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center">
                        <svg x-show="isGenerating" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isGenerating ? 'Generating...' : 'Generate Sitemap'"></span>
                    </button>
                    
                    <button @click="resetProgress()" 
                            :disabled="isGenerating"
                            class="px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Reset Progress
                    </button>
                </div>
            </div>

            <!-- Result Section -->
            @if($project->latestSitemap)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Hasil Sitemap</h2>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-green-800 font-medium">Sitemap berhasil di-generate!</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Sitemap Index</span>
                            <a href="{{ $project->latestSitemap->index_url }}" target="_blank" 
                               class="text-primary-600 hover:text-primary-700 font-medium flex items-center">
                                {{ $project->latestSitemap->index_url }}
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="p-3 bg-gray-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-gray-900">{{ number_format($project->latestSitemap->total_urls) }}</p>
                                <p class="text-xs text-gray-500">Total URL</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-gray-900">{{ $project->latestSitemap->total_files }}</p>
                                <p class="text-xs text-gray-500">File Sitemap</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-gray-900">{{ $project->latestSitemap->formatted_generation_time }}</p>
                                <p class="text-xs text-gray-500">Waktu Generate</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-gray-900">{{ $project->latestSitemap->formatted_file_size }}</p>
                                <p class="text-xs text-gray-500">Total Size</p>
                            </div>
                        </div>
                    </div>

                    <!-- File List -->
                    @if($project->latestSitemap->files->count() > 0)
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Daftar File:</h3>
                            <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">File</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">URL</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Size</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($project->latestSitemap->files as $file)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ $file->file_name }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-500 text-right">{{ number_format($file->url_count) }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-500 text-right">{{ $file->formatted_file_size }}</td>
                                                <td class="px-4 py-2 text-right">
                                                    <a href="{{ $file->file_url }}" target="_blank" class="text-primary-600 hover:text-primary-700 text-sm">
                                                        Lihat
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    @if($project->latestSitemap)
                        <a href="{{ $project->latestSitemap->index_url }}" target="_blank"
                           class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            Buka Sitemap Index
                        </a>
                        <button onclick="copyToClipboard('{{ $project->latestSitemap->index_url }}')"
                                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Copy URL Sitemap
                        </button>
                    @endif
                </div>
            </div>

            <!-- Settings Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pengaturan</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Changefreq</dt>
                        <dd class="text-gray-900 font-medium">{{ $project->merged_settings['changefreq'] ?? 'weekly' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Priority</dt>
                        <dd class="text-gray-900 font-medium">{{ $project->merged_settings['priority'] ?? '0.5' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Max URL/File</dt>
                        <dd class="text-gray-900 font-medium">50,000</dd>
                    </div>
                </dl>
            </div>

            <!-- Help -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">💡 Tips</h3>
                <ul class="text-sm text-blue-800 space-y-2">
                    <li>• Sitemap index otomatis dibuat jika URL > 50k</li>
                    <li>• Progress tersimpan, bisa dilanjutkan</li>
                    <li>• File sitemap tersedia di folder public</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function projectDetail() {
    return {
        isGenerating: false,
        error: null,
        crawlError: null,
        crawlStatus: '{{ $project->crawl_status ?? "idle" }}',
        crawlProgress: { 
            crawled: {{ $project->crawl_urls_found ?? 0 }}, 
            status: 'Idle', 
            queue: {{ $crawlQueueSize ?? 0 }} 
        },
        progress: {
            status: 'pending',
            total_urls: {{ $urlCount }},
            processed_urls: 0,
            percentage: 0,
            current_file: 0,
            estimated_files: {{ $estimatedFiles }}
        },
        
        get crawlStatusText() {
            const statusMap = {
                'idle': 'Belum dimulai',
                'crawling': 'Sedang crawling...',
                'stopped': 'Dihentikan',
                'completed': 'Selesai'
            };
            return statusMap[this.crawlStatus] || this.crawlStatus;
        },
        
        async startCrawl() {
            this.crawlError = null;
            this.crawlStatus = 'crawling';
            this.crawlProgress.status = 'Memulai...';
            
            // First call start endpoint to initialize/resume
            try {
                const startResponse = await fetch('{{ route('generate.crawl.start', $project->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const startData = await startResponse.json();
                
                if (!startData.success) {
                    this.crawlError = startData.error || 'Failed to start';
                    this.crawlStatus = 'idle';
                    return;
                }
                
                this.crawlProgress.crawled = startData.urls_found || 0;
                this.crawlProgress.queue = startData.queue_remaining || 0;
            } catch (e) {
                this.crawlError = e.message;
                this.crawlStatus = 'idle';
                return;
            }
            
            // Continue crawling in loop
            await this.processCrawl();
        },
        
        async processCrawl() {
            if (this.crawlStatus !== 'crawling') return;
            
            try {
                const response = await fetch('{{ route('generate.crawl', $project->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    this.crawlError = data.error || 'Unknown error';
                    this.crawlStatus = 'stopped';
                    return;
                }
                
                // Check if stopped externally
                if (data.stopped) {
                    this.crawlStatus = 'stopped';
                    this.crawlProgress.crawled = data.urls_found || 0;
                    return;
                }
                
                // Update progress
                this.crawlProgress = {
                    crawled: data.urls_found || 0,
                    status: data.completed ? 'Selesai!' : 'Crawling...',
                    queue: data.queue_remaining || 0
                };
                
                // Also update the generate progress total_urls
                this.progress.total_urls = data.urls_found || 0;
                
                if (data.completed) {
                    this.crawlStatus = 'completed';
                    // Update URL count display
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    // Continue crawling with small delay
                    setTimeout(() => this.processCrawl(), 300);
                }
                
            } catch (e) {
                console.error('Crawl error:', e);
                this.crawlError = 'Terjadi kesalahan: ' + e.message;
                this.crawlStatus = 'stopped';
            }
        },
        
        async stopCrawl() {
            try {
                const response = await fetch('{{ route('generate.crawl.stop', $project->slug) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                this.crawlStatus = 'stopped';
                this.crawlProgress.status = 'Dihentikan';
                
                // Update URL counts from response
                if (data.urls_found !== undefined) {
                    this.crawlProgress.crawled = data.urls_found;
                    this.progress.total_urls = data.urls_found;
                }
                
            } catch (e) {
                this.crawlError = e.message;
            }
        },
        
        async resetCrawl() {
            if (!confirm('Reset akan menghapus semua URL yang sudah di-crawl. Lanjutkan?')) return;
            
            try {
                // Reset all crawl data
                this.crawlStatus = 'idle';
                this.crawlProgress = { crawled: 0, status: 'Resetting...', queue: 0 };
                
                // Call full reset endpoint
                const response = await fetch('{{ route('generate.reset.full', $project->slug) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    this.crawlError = data.error || 'Gagal reset';
                    this.crawlStatus = 'stopped';
                }
                
            } catch (e) {
                this.crawlError = e.message;
                this.crawlStatus = 'stopped';
            }
        },
        
        async startGenerate() {
            this.isGenerating = true;
            this.error = null;
            await this.processGeneration();
        },
        
        async processGeneration() {
            try {
                const response = await fetch('{{ route('generate.process', $project->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ batch_size: 5000 })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    this.error = data.error;
                    this.isGenerating = false;
                    return;
                }
                
                if (data.completed) {
                    window.location.reload();
                    return;
                }
                
                this.progress = {
                    status: 'processing',
                    total_urls: data.total,
                    processed_urls: data.processed,
                    percentage: data.percentage,
                    current_file: data.current_file,
                    estimated_files: Math.ceil(data.total / 50000)
                };
                
                setTimeout(() => this.processGeneration(), 500);
                
            } catch (e) {
                this.error = 'Terjadi kesalahan: ' + e.message;
                this.isGenerating = false;
            }
        },
        
        async resetProgress() {
            if (!confirm('Yakin ingin reset progress? Sitemap yang sudah ada akan dihapus.')) return;
            
            try {
                const response = await fetch('{{ route('generate.reset', $project->slug) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    this.error = data.error || 'Gagal reset progress';
                }
            } catch (e) {
                this.error = 'Terjadi kesalahan: ' + e.message;
            }
        },
        
        async init() {
            // Check crawl status and auto-resume if was crawling
            @if($project->mode === 'crawl')
            if (this.crawlStatus === 'crawling') {
                // Auto-resume crawling
                this.crawlProgress.status = 'Melanjutkan...';
                setTimeout(() => this.processCrawl(), 500);
            }
            @endif
            
            // Check generate progress
            try {
                const response = await fetch('{{ route('generate.progress', $project->slug) }}');
                const data = await response.json();
                this.progress = data;
                
                if (data.status === 'processing') {
                    this.isGenerating = true;
                    this.processGeneration();
                }
            } catch (e) {
                console.error('Failed to load progress:', e);
            }
        }
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL berhasil disalin!');
    });
}
</script>
@endpush
@endsection
