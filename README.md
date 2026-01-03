# Sitemap Generator

Aplikasi web untuk generate sitemap XML dari website dengan jutaan artikel. Mendukung hingga 1.000.000 URL dengan auto-split sesuai standar Google (50k URL per file).

![Sitemap Generator](https://img.shields.io/badge/Laravel-10.x-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Fitur Utama

- **Skalabilitas Tinggi**: Mendukung hingga 1.000.000 URL tanpa memory overflow
- **Auto-Split**: Otomatis membagi sitemap menjadi file-file 50k URL sesuai standar Google
- **Multi-Mode Import**:
  - 📄 **CSV Upload**: Upload file CSV berisi daftar URL
  - 🗄️ **Database Import**: Koneksi langsung ke MySQL/MariaDB
  - 🌐 **Crawl** (Opsional): Crawl website otomatis (max 50k URL)
- **Memory Efficient**: Menggunakan chunking dan streaming write
- **Shared Hosting Ready**: Tidak perlu Docker, Node.js build, atau konfigurasi rumit
- **Progress Tracking**: Progress bar realtime dengan resume capability
- **UI Modern**: Interface responsif dengan Tailwind CSS

## 📋 Requirements

- PHP 8.1+
- MySQL 5.7+ atau MariaDB 10.3+
- Composer
- Apache/Nginx dengan mod_rewrite

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/your-username/sitemap-generator.git
cd sitemap-generator
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sitemap_generator
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Jalankan Migration

```bash
php artisan migrate
```

### 5. Set Permission

```bash
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/sitemaps
```

### 6. Jalankan Aplikasi (Development)

```bash
php artisan serve
```

Akses di: http://localhost:8000

## 📁 Struktur Database

### ERD

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    projects     │     │    sitemaps     │     │  sitemap_files  │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │────<│ id              │────<│ id              │
│ name            │     │ project_id (FK) │     │ sitemap_id (FK) │
│ slug            │     │ index_path      │     │ file_path       │
│ base_url        │     │ total_urls      │     │ file_number     │
│ mode            │     │ total_files     │     │ url_count       │
│ settings (JSON) │     │ generation_time │     │ file_size       │
│ status          │     │ generated_at    │     │ created_at      │
│ error_message   │     │ created_at      │     │ updated_at      │
│ created_at      │     │ updated_at      │     └─────────────────┘
│ updated_at      │     └─────────────────┘
└─────────────────┘
```

### Tabel Tambahan

- `generation_progress`: Menyimpan progress generate untuk resume
- `temp_urls`: Tabel temporary untuk menyimpan URL sebelum di-generate

## 🔧 Penggunaan

### 1. Buat Project Baru

1. Buka Dashboard → Klik "Project Baru"
2. Masukkan nama project dan Base URL
3. Pilih mode import:
   - **CSV**: Upload file CSV
   - **Database**: Masukkan kredensial database
   - **Crawl**: Akan crawl website secara otomatis

### 2. Format CSV

```csv
url,lastmod,changefreq,priority
/artikel/judul-artikel-1,2024-01-15,weekly,0.8
/artikel/judul-artikel-2,2024-01-14,weekly,0.7
/berita/judul-berita,2024-01-13,daily,0.9
```

**Kolom:**
- `url` (wajib): URL atau path relatif
- `lastmod` (opsional): Tanggal terakhir diubah (YYYY-MM-DD)
- `changefreq` (opsional): Frekuensi perubahan
- `priority` (opsional): Prioritas 0.0-1.0

### 3. Generate Sitemap

1. Buka detail project
2. Klik tombol "Generate Sitemap"
3. Tunggu progress selesai
4. Copy URL sitemap-index.xml untuk submit ke Google Search Console

## 🌐 Deploy ke Shared Hosting (cPanel)

### Metode 1: Document Root ke /public

1. **Upload Files**
   - Buat folder baru di `public_html` (misal: `sitemap-generator`)
   - Upload semua file ke folder tersebut via File Manager atau FTP

2. **Set Document Root**
   - Masuk ke cPanel → Domains → Modify
   - Ubah Document Root ke `/public_html/sitemap-generator/public`

3. **Konfigurasi .env**
   ```bash
   # Edit .env via File Manager
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   DB_DATABASE=cpanel_username_sitemap
   DB_USERNAME=cpanel_username_dbuser
   DB_PASSWORD=your_db_password
   ```

4. **Jalankan Migration**
   - Akses via SSH atau Terminal cPanel:
   ```bash
   cd public_html/sitemap-generator
   php artisan migrate --force
   ```

5. **Set Permission**
   ```bash
   chmod -R 755 storage bootstrap/cache public/sitemaps
   ```

### Metode 2: Upload di public_html (Tanpa Ubah Document Root)

1. **Upload semua folder KECUALI `/public`** ke luar `public_html`:
   ```
   /home/username/
   ├── sitemap-generator/
   │   ├── app/
   │   ├── bootstrap/
   │   ├── config/
   │   ├── database/
   │   ├── routes/
   │   ├── storage/
   │   ├── vendor/
   │   └── ...
   ```

2. **Upload isi folder `/public`** ke `public_html`:
   ```
   /home/username/public_html/
   ├── index.php
   ├── .htaccess
   └── sitemaps/
   ```

3. **Edit `public_html/index.php`**:
   ```php
   // Ubah path ke folder Laravel
   require __DIR__.'/../sitemap-generator/vendor/autoload.php';
   $app = require_once __DIR__.'/../sitemap-generator/bootstrap/app.php';
   ```

4. **Edit `sitemap-generator/bootstrap/app.php`** jika perlu sesuaikan path

5. **Konfigurasi .env dan jalankan migration**

### Troubleshooting Deploy

| Error | Solusi |
|-------|--------|
| 500 Internal Server Error | Cek permission storage dan bootstrap/cache (755 atau 775) |
| Page Not Found | Pastikan mod_rewrite aktif dan .htaccess ter-upload |
| Database Connection Error | Cek kredensial database di .env |
| Class Not Found | Jalankan `composer dump-autoload` |
| Session/Cache Error | `php artisan config:clear && php artisan cache:clear` |

## ⚡ Performance Tips

1. **Untuk 100k+ URL**: Gunakan mode CSV, upload file terlebih dahulu
2. **Shared Hosting Limit**: Jika timeout, progress otomatis tersimpan dan bisa dilanjutkan
3. **Memory**: Default chunk size 1000, bisa disesuaikan di config/sitemap.php
4. **Database Mode**: Pastikan tabel yang diquery sudah terindeks dengan baik

## 📝 Testing dengan Sample Data

### Generate Sample CSV

```bash
# Generate 1000 URL
php scripts/generate-sample-csv.php 1000 > storage/app/test-1k.csv

# Generate 100k URL  
php scripts/generate-sample-csv.php 100000 > storage/app/test-100k.csv

# Generate 1 juta URL (file besar ~50MB)
php scripts/generate-sample-csv.php 1000000 > storage/app/test-1m.csv
```

### Menggunakan Sample CSV Kecil

File `storage/app/sample-urls.csv` sudah tersedia untuk testing cepat dengan 27 URL.

## 🔒 Keamanan

- Semua input di-validasi dan sanitasi
- File CSV hanya bisa upload format CSV/TXT
- Database credential disimpan di .env (tidak di-commit)
- CSRF protection untuk semua form
- Rate limiting untuk crawler mode

## 📄 Output Format

### sitemap-index.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>https://example.com/sitemaps/project-slug/sitemap-1.xml</loc>
    <lastmod>2024-01-15</lastmod>
  </sitemap>
  <sitemap>
    <loc>https://example.com/sitemaps/project-slug/sitemap-2.xml</loc>
    <lastmod>2024-01-15</lastmod>
  </sitemap>
</sitemapindex>
```

### sitemap-X.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/artikel/judul-artikel</loc>
    <lastmod>2024-01-15</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <!-- ... more URLs ... -->
</urlset>
```

## 🛠️ Konfigurasi

Edit `config/sitemap.php` untuk mengubah setting default:

```php
return [
    'max_urls_per_file' => 50000,  // Max URL per file
    'max_total_urls' => 1000000,   // Max total URL
    'chunk_size' => 1000,          // Chunk size untuk processing
    'defaults' => [
        'changefreq' => 'weekly',
        'priority' => '0.5',
    ],
    'crawler' => [
        'enabled' => false,         // Disable crawler by default
        'max_depth' => 3,
        'max_pages' => 50000,
    ],
];
```

## 📂 Struktur Folder Project

```
sitemap-generator/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DashboardController.php
│   │       ├── GenerateController.php
│   │       ├── LandingController.php
│   │       └── ProjectController.php
│   ├── Models/
│   │   ├── GenerationProgress.php
│   │   ├── Project.php
│   │   ├── Sitemap.php
│   │   ├── SitemapFile.php
│   │   └── TempUrl.php
│   ├── Providers/
│   └── Services/
│       ├── CrawlerService.php
│       ├── CsvImportService.php
│       ├── DatabaseImportService.php
│       └── SitemapGeneratorService.php
├── bootstrap/
├── config/
│   ├── app.php
│   ├── database.php
│   └── sitemap.php
├── database/
│   └── migrations/
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── sitemaps/
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── dashboard/
│       │   └── index.blade.php
│       ├── projects/
│       │   ├── create.blade.php
│       │   └── show.blade.php
│       └── landing.blade.php
├── routes/
│   └── web.php
├── storage/
│   └── app/
│       └── sample-urls.csv
├── scripts/
│   └── generate-sample-csv.php
├── .env.example
├── composer.json
└── README.md
```

## 📜 License

MIT License - Silakan gunakan untuk project personal maupun komersial.

## 🤝 Contributing

Pull requests are welcome! Untuk perubahan besar, buka issue terlebih dahulu untuk diskusi.

---

Made with ❤️ for handling large-scale sitemaps easily.