@extends('layouts.app')

@section('title', 'Buat Project Baru - Sitemap Generator')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="text-primary-600 hover:text-primary-700 flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Dashboard
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-4">Buat Project Baru</h1>
        <p class="text-gray-500 mt-1">Masukkan informasi website dan pilih metode import URL</p>
    </div>

    <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data" 
          x-data="projectForm()" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Dasar</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Project *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Contoh: Blog Utama">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="base_url" class="block text-sm font-medium text-gray-700 mb-1">Base URL *</label>
                    <input type="url" name="base_url" id="base_url" value="{{ old('base_url') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://example.com">
                    @error('base_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">URL dasar website tanpa trailing slash</p>
                </div>
            </div>
        </div>

        <!-- Mode Selection -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Metode Import URL</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- CSV Mode -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="mode" value="csv" x-model="mode" class="sr-only peer" {{ old('mode', 'csv') === 'csv' ? 'checked' : '' }}>
                    <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 transition">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900">Upload CSV</h3>
                        <p class="text-sm text-gray-500 mt-1">Upload file CSV berisi URL</p>
                    </div>
                </label>
                
                <!-- Database Mode -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="mode" value="database" x-model="mode" class="sr-only peer" {{ old('mode') === 'database' ? 'checked' : '' }}>
                    <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 transition">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900">Database</h3>
                        <p class="text-sm text-gray-500 mt-1">Import dari MySQL/MariaDB</p>
                    </div>
                </label>
                
                <!-- Crawl Mode -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="mode" value="crawl" x-model="mode" class="sr-only peer" {{ old('mode') === 'crawl' ? 'checked' : '' }}>
                    <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 transition">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900">Crawl Website</h3>
                        <p class="text-sm text-gray-500 mt-1">Crawl otomatis (max 50k)</p>
                    </div>
                </label>
            </div>

            <!-- CSV Options -->
            <div x-show="mode === 'csv'" x-cloak class="space-y-4 border-t border-gray-100 pt-6">
                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">File CSV *</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    @error('csv_file')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Format: CSV dengan kolom URL. Maksimal 50MB.</p>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="csv_has_header" id="csv_has_header" value="1" checked
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="csv_has_header" class="ml-2 text-sm text-gray-700">File memiliki baris header</label>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="csv_url_column" class="block text-sm font-medium text-gray-700 mb-1">Kolom URL (index)</label>
                        <input type="number" name="csv_url_column" id="csv_url_column" value="0" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="text-gray-500 text-sm mt-1">0 = kolom pertama</p>
                    </div>
                    <div>
                        <label for="csv_lastmod_column" class="block text-sm font-medium text-gray-700 mb-1">Kolom Lastmod (opsional)</label>
                        <input type="number" name="csv_lastmod_column" id="csv_lastmod_column" value="" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="Kosongkan jika tidak ada">
                    </div>
                </div>
            </div>

            <!-- Database Options -->
            <div x-show="mode === 'database'" x-cloak class="space-y-4 border-t border-gray-100 pt-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <strong>Catatan:</strong> Pastikan database dapat diakses dari server hosting. 
                        Untuk keamanan, Anda dapat menyimpan koneksi di halaman detail project nanti.
                    </p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="db_host" class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                        <input type="text" name="db_host" id="db_host" value="{{ old('db_host', 'localhost') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="db_port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                        <input type="number" name="db_port" id="db_port" value="{{ old('db_port', '3306') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="db_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Database</label>
                        <input type="text" name="db_name" id="db_name" value="{{ old('db_name') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="db_table" class="block text-sm font-medium text-gray-700 mb-1">Nama Tabel</label>
                        <input type="text" name="db_table" id="db_table" value="{{ old('db_table', 'posts') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="db_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="db_username" id="db_username" value="{{ old('db_username') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="db_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="db_password" id="db_password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="db_slug_column" class="block text-sm font-medium text-gray-700 mb-1">Kolom Slug/URL</label>
                        <input type="text" name="db_slug_column" id="db_slug_column" value="{{ old('db_slug_column', 'slug') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="db_lastmod_column" class="block text-sm font-medium text-gray-700 mb-1">Kolom Updated At</label>
                        <input type="text" name="db_lastmod_column" id="db_lastmod_column" value="{{ old('db_lastmod_column', 'updated_at') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                
                <div>
                    <label for="db_url_prefix" class="block text-sm font-medium text-gray-700 mb-1">URL Prefix</label>
                    <input type="text" name="db_url_prefix" id="db_url_prefix" value="{{ old('db_url_prefix') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://example.com/post/">
                    <p class="text-gray-500 text-sm mt-1">Prefix yang akan ditambahkan sebelum slug. Kosongkan untuk menggunakan Base URL.</p>
                </div>
            </div>

            <!-- Crawl Options -->
            <div x-show="mode === 'crawl'" x-cloak class="space-y-4 border-t border-gray-100 pt-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <strong>Peringatan:</strong> Mode crawl dibatasi maksimal 50.000 URL untuk menghindari timeout. 
                        Untuk website besar, gunakan mode CSV atau Database.
                    </p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="crawler_max_depth" class="block text-sm font-medium text-gray-700 mb-1">Max Depth</label>
                        <input type="number" name="crawler_max_depth" id="crawler_max_depth" value="3" min="1" max="5"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="text-gray-500 text-sm mt-1">Kedalaman crawl (1-5)</p>
                    </div>
                    <div>
                        <label for="crawler_max_pages" class="block text-sm font-medium text-gray-700 mb-1">Max Pages</label>
                        <input type="number" name="crawler_max_pages" id="crawler_max_pages" value="1000" min="100" max="50000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="text-gray-500 text-sm mt-1">Maksimal halaman (max 50.000)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sitemap Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Pengaturan Sitemap</h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="changefreq" class="block text-sm font-medium text-gray-700 mb-1">Default Changefreq</label>
                        <select name="changefreq" id="changefreq"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="always">Always</option>
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="never">Never</option>
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Default Priority</label>
                        <input type="number" name="priority" id="priority" value="0.5" min="0" max="1" step="0.1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                
                <div>
                    <label for="exclude_patterns" class="block text-sm font-medium text-gray-700 mb-1">Exclude Patterns</label>
                    <textarea name="exclude_patterns" id="exclude_patterns" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                              placeholder="/tag/&#10;/search&#10;/page/">{{ old('exclude_patterns') }}</textarea>
                    <p class="text-gray-500 text-sm mt-1">Satu pattern per baris. URL yang mengandung pattern ini akan diabaikan.</p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('dashboard') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                Buat Project
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function projectForm() {
    return {
        mode: '{{ old('mode', 'csv') }}'
    }
}
</script>
@endpush
@endsection
