<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap Generator - Generate Sitemap XML untuk 1 Juta URL</title>
    <meta name="description" content="Aplikasi web untuk generate sitemap XML dari website dengan jutaan artikel. Mendukung CSV upload, database import, dan crawling.">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .gradient-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Hero Section -->
    <div class="gradient-hero">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-bold text-xl text-white">Sitemap Generator</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-white/80 hover:text-white transition">Dashboard</a>
                    <a href="{{ route('projects.create') }}" class="bg-white text-primary-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                        Mulai Sekarang
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    Generate Sitemap XML<br>
                    <span class="text-yellow-300">Hingga 1 Juta URL</span>
                </h1>
                <p class="text-xl text-white/80 mb-8 max-w-2xl mx-auto">
                    Aplikasi web untuk membuat sitemap XML dari website besar dengan jutaan artikel. 
                    Otomatis membagi file sesuai standar Google (50k URL per file).
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('projects.create') }}" class="bg-white text-primary-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-gray-100 transition shadow-lg">
                        🚀 Buat Project Baru
                    </a>
                    <a href="{{ route('dashboard') }}" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/10 transition">
                        Lihat Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Wave SVG -->
        <svg class="fill-gray-50" viewBox="0 0 1440 100" preserveAspectRatio="none">
            <path d="M0,50 C360,100 1080,0 1440,50 L1440,100 L0,100 Z"></path>
        </svg>
    </div>

    <!-- Features Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Fitur Unggulan</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Didesain untuk website besar dengan performa tinggi dan mudah digunakan
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Upload CSV</h3>
                <p class="text-gray-600">
                    Upload file CSV dengan daftar URL. Mendukung file hingga 50MB dengan jutaan baris.
                </p>
            </div>
            
            <!-- Feature 2 -->
            <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Import Database</h3>
                <p class="text-gray-600">
                    Koneksi langsung ke database MySQL/MariaDB untuk import URL dari tabel posts.
                </p>
            </div>
            
            <!-- Feature 3 -->
            <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Performa Tinggi</h3>
                <p class="text-gray-600">
                    Streaming write & chunking untuk handle 1 juta URL tanpa memory overflow.
                </p>
            </div>
            
            <!-- Feature 4 -->
            <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Standar Google</h3>
                <p class="text-gray-600">
                    Output XML sesuai protokol sitemap dengan auto-split 50k URL per file.
                </p>
            </div>
            
            <!-- Feature 5 -->
            <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Progress Realtime</h3>
                <p class="text-gray-600">
                    Pantau progress generate dengan progress bar realtime dan estimasi waktu.
                </p>
            </div>
            
            <!-- Feature 6 -->
            <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Shared Hosting Ready</h3>
                <p class="text-gray-600">
                    Dioptimalkan untuk shared hosting. Tidak perlu Docker atau konfigurasi rumit.
                </p>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="bg-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Cara Kerja</h2>
                <p class="text-gray-600">Hanya 3 langkah untuk generate sitemap</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                        1
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Buat Project</h3>
                    <p class="text-gray-600">Masukkan nama website dan URL dasar</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                        2
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Import URL</h3>
                    <p class="text-gray-600">Upload CSV atau koneksi ke database</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                        3
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Generate</h3>
                    <p class="text-gray-600">Klik tombol generate dan selesai!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="gradient-hero py-16">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-3xl font-bold text-white mb-4">
                Siap Generate Sitemap?
            </h2>
            <p class="text-white/80 mb-8">
                Mulai sekarang dan optimalkan SEO website Anda dengan sitemap yang proper.
            </p>
            <a href="{{ route('projects.create') }}" class="inline-block bg-white text-primary-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-gray-100 transition shadow-lg">
                🚀 Buat Project Sekarang
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; {{ date('Y') }} Sitemap Generator. Built with Laravel & Tailwind CSS.</p>
            <p class="mt-2 text-sm">Mendukung hingga 1.000.000 URL • Shared Hosting Ready</p>
        </div>
    </footer>
</body>
</html>
